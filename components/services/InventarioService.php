<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\InventoryItem;
use app\models\InventoryMovement;

/**
 * InventarioService: lógica de negocio para gestión de inventario.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class InventarioService extends BaseService
{
    protected string $logCategoria = 'app.inventario';

    /**
     * Crea un nuevo ítem de inventario.
     *
     * @param array $data
     * @return InventoryItem|null
     */
    public function create(array $data): ?InventoryItem
    {
        return $this->executeInTransaction(function () use ($data): InventoryItem {
            $item = new InventoryItem();
            $item->nombre          = $data['nombre']          ?? '';
            $item->descripcion     = !empty($data['descripcion'])    ? $data['descripcion']    : null;
            $item->categoria_id    = (int) ($data['categoria_id']    ?? 0);
            $item->precio_unitario = (float) ($data['precio_unitario'] ?? 0);
            $item->cantidad        = (int) ($data['cantidad']        ?? 0);
            $item->stock_minimo    = (int) ($data['stock_minimo']    ?? 0);
            $item->stock_maximo    = !empty($data['stock_maximo'])    ? (int) $data['stock_maximo']  : null;
            $item->unidad          = $data['unidad']          ?? 'unidad';
            $item->ubicacion       = !empty($data['ubicacion'])       ? $data['ubicacion']       : null;
            $item->status          = (int) ($data['status'] ?? 1);

            if (!$item->validate()) {
                throw new ServiceException(implode('; ', $item->getFirstErrors()));
            }
            $this->asegurar($item->save(false), 'Error al guardar el ítem.');

            // Si hay stock inicial, registrar movimiento de entrada
            if ($item->cantidad > 0) {
                $this->crearMovimiento($item->id, 'entrada', $item->cantidad, 0, $item->cantidad, null, 'Stock inicial');
            }

            $this->log("InventoryItem creado: #{$item->id} ({$item->sku})");
            return $item;
        });
    }

    /**
     * Actualiza un ítem existente.
     *
     * @param InventoryItem $item
     * @param array         $data
     * @return InventoryItem|null
     */
    public function update(InventoryItem $item, array $data): ?InventoryItem
    {
        return $this->executeInTransaction(function () use ($item, $data): InventoryItem {
            if (isset($data['nombre']))          $item->nombre          = $data['nombre'];
            if (array_key_exists('descripcion', $data)) $item->descripcion = !empty($data['descripcion']) ? $data['descripcion'] : null;
            if (isset($data['categoria_id']))    $item->categoria_id    = (int) $data['categoria_id'];
            if (isset($data['precio_unitario'])) $item->precio_unitario = (float) $data['precio_unitario'];
            if (isset($data['stock_minimo']))    $item->stock_minimo    = (int) $data['stock_minimo'];
            if (array_key_exists('stock_maximo', $data)) $item->stock_maximo = !empty($data['stock_maximo']) ? (int) $data['stock_maximo'] : null;
            if (isset($data['unidad']))          $item->unidad          = $data['unidad'];
            if (array_key_exists('ubicacion', $data)) $item->ubicacion  = !empty($data['ubicacion']) ? $data['ubicacion'] : null;
            if (isset($data['status']))          $item->status          = (int) $data['status'];

            if (!$item->validate()) {
                throw new ServiceException(implode('; ', $item->getFirstErrors()));
            }
            $this->asegurar($item->save(false), 'Error al actualizar el ítem.');
            $this->log("InventoryItem actualizado: #{$item->id}");
            return $item;
        });
    }

    /**
     * Desactiva un ítem de inventario.
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $item = InventoryItem::findOne($id);
        if ($item === null) {
            $this->agregarError('Ítem no encontrado.');
            return false;
        }
        $item->status = 0;
        if (!$item->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar el ítem.');
            return false;
        }
        $this->log("InventoryItem desactivado: #{$id}");
        return true;
    }

    /**
     * Registra una entrada de stock.
     *
     * @param int    $itemId
     * @param int    $cantidad
     * @param string $referencia
     * @param int    $userId
     */
    public function registrarEntrada(int $itemId, int $cantidad, string $referencia, int $userId): void
    {
        $this->executeInTransaction(function () use ($itemId, $cantidad, $referencia, $userId): void {
            $item = $this->findItemOrFail($itemId);
            $anterior = $item->cantidad;
            $nueva    = $anterior + $cantidad;
            $item->cantidad = $nueva;
            $this->asegurar($item->save(false, ['cantidad', 'updated_at']), 'Error al actualizar stock.');
            $this->crearMovimiento($itemId, 'entrada', $cantidad, $anterior, $nueva, $userId, $referencia);
            $this->notificarStockBajoSiAplica($item);
            $this->log("Entrada registrada: #{$itemId} +{$cantidad}");
        });
    }

    /**
     * Registra una salida de stock. Valida que no quede negativo.
     *
     * @param int    $itemId
     * @param int    $cantidad
     * @param string $referencia
     * @param int    $userId
     */
    public function registrarSalida(int $itemId, int $cantidad, string $referencia, int $userId): void
    {
        $this->executeInTransaction(function () use ($itemId, $cantidad, $referencia, $userId): void {
            $item = $this->findItemOrFail($itemId);
            if ($item->cantidad < $cantidad) {
                throw new ServiceException("Stock insuficiente. Disponible: {$item->cantidad}, solicitado: {$cantidad}.");
            }
            $anterior = $item->cantidad;
            $nueva    = $anterior - $cantidad;
            $item->cantidad = $nueva;
            $this->asegurar($item->save(false, ['cantidad', 'updated_at']), 'Error al actualizar stock.');
            $this->crearMovimiento($itemId, 'salida', -$cantidad, $anterior, $nueva, $userId, $referencia);
            $this->notificarStockBajoSiAplica($item);
            $this->log("Salida registrada: #{$itemId} -{$cantidad}");
        });
    }

    /**
     * Registra un ajuste directo de stock.
     *
     * @param int    $itemId
     * @param int    $cantidadNueva
     * @param string $motivo
     * @param int    $userId
     */
    public function registrarAjuste(int $itemId, int $cantidadNueva, string $motivo, int $userId): void
    {
        $this->executeInTransaction(function () use ($itemId, $cantidadNueva, $motivo, $userId): void {
            $item     = $this->findItemOrFail($itemId);
            $anterior = $item->cantidad;
            $delta    = $cantidadNueva - $anterior;
            $item->cantidad = $cantidadNueva;
            $this->asegurar($item->save(false, ['cantidad', 'updated_at']), 'Error al ajustar stock.');
            $this->crearMovimiento($itemId, 'ajuste', $delta, $anterior, $cantidadNueva, $userId, $motivo);
            $this->notificarStockBajoSiAplica($item);
            $this->log("Ajuste de stock: #{$itemId} {$anterior} → {$cantidadNueva}");
        });
    }

    /**
     * Retorna ítems con stock bajo o sin stock.
     *
     * @return InventoryItem[]
     */
    public function getItemsConStockBajo(): array
    {
        return InventoryItem::find()
            ->where(['status' => 1])
            ->andWhere(['<=', 'cantidad', new \yii\db\Expression('stock_minimo')])
            ->orderBy('nombre')
            ->all();
    }

    /**
     * Alias semántico para el plan del módulo.
     *
     * @return InventoryItem[]
     */
    public function evaluarAlertas(): array
    {
        return $this->getItemsConStockBajo();
    }

    /**
     * KPIs del inventario.
     *
     * @return array{total:int,alertas:int,valor_total:float}
     */
    public function getKpis(): array
    {
        $baseQuery = InventoryItem::find()->where(['status' => 1]);

        $total = (int) (clone $baseQuery)->count();
        $alertas = (int) (clone $baseQuery)
            ->andWhere(['<=', 'cantidad', new \yii\db\Expression('stock_minimo')])
            ->count();
        $valor = (float) (clone $baseQuery)
            ->select(new \yii\db\Expression('COALESCE(SUM(cantidad * precio_unitario), 0)'))
            ->scalar();

        return [
            'total'       => $total,
            'alertas'     => $alertas,
            'valor_total' => $valor,
        ];
    }

    /**
     * Sincroniza la API del servicio con el plan del módulo.
     */
    public function getEstadisticas(): array
    {
        return $this->getKpis();
    }

    /**
     * Exporta el inventario activo a CSV.
     */
    public function exportarCsv(): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new ServiceException('No se pudo preparar la exportación CSV.');
        }

        fputcsv($handle, ['SKU', 'Nombre', 'Categoría', 'Precio Unitario', 'Cantidad', 'Stock Mínimo', 'Stock Máximo', 'Estado']);

        foreach (InventoryItem::find()->where(['status' => 1])->with('categoria')->orderBy(['nombre' => SORT_ASC])->all() as $item) {
            fputcsv($handle, [
                $item->sku,
                $item->nombre,
                $item->categoria ? $item->categoria->nombre : '',
                number_format((float) $item->precio_unitario, 2, '.', ''),
                (string) $item->cantidad,
                (string) $item->stock_minimo,
                $item->stock_maximo === null ? '' : (string) $item->stock_maximo,
                $item->status ? 'Activo' : 'Inactivo',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }

    // ── Métodos privados ──────────────────────────────────────────────────────

    private function findItemOrFail(int $id): InventoryItem
    {
        $item = InventoryItem::findOne($id);
        if ($item === null) {
            throw new ServiceException("Ítem de inventario #{$id} no encontrado.");
        }
        return $item;
    }

    private function crearMovimiento(
        int     $itemId,
        string  $tipo,
        int     $delta,
        int     $anterior,
        int     $nueva,
        ?int    $userId,
        string  $referencia
    ): void {
        $mov                   = new InventoryMovement();
        $mov->item_id          = $itemId;
        $mov->tipo             = $tipo;
        $mov->cantidad_delta   = $delta;
        $mov->cantidad_anterior= $anterior;
        $mov->cantidad_nueva   = $nueva;
        $mov->usuario_id       = $userId;
        $mov->referencia       = $referencia;
        $mov->created_at       = time();
        $this->asegurar($mov->save(false), 'Error al registrar movimiento.');
    }

    private function notificarStockBajoSiAplica(InventoryItem $item): void
    {
        if ((int) $item->cantidad > (int) $item->stock_minimo) {
            return;
        }

        $adminId = (int) (Yii::$app->user->id ?? 1);
        $service = new NotificacionService();
        $service->crearNotificacion(
            $adminId,
            \app\models\Notificacion::TIPO_STOCK_BAJO,
            'Stock bajo: ' . $item->nombre,
            'El item ' . $item->nombre . ' quedo con stock ' . $item->cantidad . ' (minimo: ' . $item->stock_minimo . ').',
            '/inventario/' . $item->id
        );
    }
}
