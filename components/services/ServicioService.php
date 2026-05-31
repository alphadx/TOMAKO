<?php
declare(strict_types=1);

namespace app\components\services;

use app\models\Servicio;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;

/**
 * ServicioService: lógica de negocio para servicios del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ServicioService extends BaseService
{
    protected string $logCategoria = 'app.servicio';

    /** @var string[] Estados considerados como órdenes activas. */
    private const ESTADOS_ORDEN_ACTIVA = [
        'abierto',
        'en_progreso',
        'esperando_repuestos',
        'listo_para_entrega',
    ];

    /**
     * Crea un nuevo servicio.
     *
     * @param array $data Datos del servicio.
     * @return Servicio|null El servicio creado o null en caso de error.
     */
    public function create(array $data): ?Servicio
    {
        return $this->executeInTransaction(function () use ($data): Servicio {
            $servicio = new Servicio();
            $servicio->codigo            = trim($data['codigo'] ?? Servicio::generarCodigo());
            $servicio->nombre            = trim($data['nombre'] ?? '');
            $servicio->descripcion       = $data['descripcion'] ?? null;
            $servicio->precio_base       = (float) ($data['precio_base'] ?? 0);
            $servicio->duracion_estimada = !empty($data['duracion_estimada']) ? (int) $data['duracion_estimada'] : null;
            $servicio->categoria_id      = (int) ($data['categoria_id'] ?? 0);
            $servicio->status            = (int) ($data['status'] ?? 1);

            if (!$servicio->validate()) {
                throw new ServiceException(implode('; ', $servicio->getFirstErrors()));
            }
            $this->asegurar($servicio->save(false), 'Error al guardar el servicio.');
            $this->log("Servicio creado: #{$servicio->id} ({$servicio->codigo})");
            return $servicio;
        });
    }

    /**
     * Actualiza un servicio existente.
     *
     * @param Servicio $servicio Instancia a actualizar.
     * @param array    $data     Datos a actualizar.
     * @return Servicio|null El servicio actualizado o null en caso de error.
     */
    public function update(Servicio $servicio, array $data): ?Servicio
    {
        return $this->executeInTransaction(function () use ($servicio, $data): Servicio {
            if (isset($data['nombre']))       $servicio->nombre            = trim($data['nombre']);
            if (isset($data['codigo']))       $servicio->codigo            = trim($data['codigo']);
            if (array_key_exists('descripcion', $data)) $servicio->descripcion = $data['descripcion'];
            if (isset($data['precio_base']))  $servicio->precio_base       = (float) $data['precio_base'];
            if (array_key_exists('duracion_estimada', $data)) {
                $servicio->duracion_estimada = !empty($data['duracion_estimada']) ? (int) $data['duracion_estimada'] : null;
            }
            if (isset($data['categoria_id'])) $servicio->categoria_id     = (int) $data['categoria_id'];
            if (isset($data['status']))        $servicio->status           = (int) $data['status'];

            if (!$servicio->validate()) {
                throw new ServiceException(implode('; ', $servicio->getFirstErrors()));
            }
            $this->asegurar($servicio->save(false), 'Error al actualizar el servicio.');
            $this->log("Servicio actualizado: #{$servicio->id}");
            return $servicio;
        });
    }

    /**
     * Desactiva un servicio (soft delete: status=0).
     *
     * @param int $id ID del servicio.
     * @return bool True si se desactivó exitosamente.
     */
    public function deactivate(int $id): bool
    {
        $servicio = Servicio::findOne($id);
        if ($servicio === null) {
            $this->agregarError('Servicio no encontrado.');
            return false;
        }

        if ($this->hasActiveOrders($id)) {
            $this->agregarError('Tiene órdenes asociadas activas.');
            return false;
        }

        $servicio->status = 0;
        if (!$servicio->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar el servicio.');
            return false;
        }
        $this->log("Servicio desactivado: #{$id}");
        return true;
    }

    /**
     * Activa nuevamente un servicio.
     */
    public function activate(int $id): bool
    {
        $servicio = Servicio::findOne($id);
        if ($servicio === null) {
            $this->agregarError('Servicio no encontrado.');
            return false;
        }

        $servicio->status = 1;
        if (!$servicio->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al activar el servicio.');
            return false;
        }

        $this->log("Servicio activado: #{$id}");
        return true;
    }

    /**
     * Determina si el servicio está asociado a órdenes activas.
     */
    public function hasActiveOrders(int $servicioId): bool
    {
        return OrdenServicioDetalle::find()
            ->alias('det')
            ->innerJoin(['ord' => OrdenServicio::tableName()], 'ord.id = det.orden_id')
            ->where(['det.servicio_id' => $servicioId])
            ->andWhere(['ord.estado' => self::ESTADOS_ORDEN_ACTIVA])
            ->exists();
    }

    /**
     * Retorna servicios activos para dropdowns en otros módulos.
     *
     * @return array<int,string>
     */
    public function getServiciosActivos(): array
    {
        return Servicio::find()
            ->where(['status' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }
}
