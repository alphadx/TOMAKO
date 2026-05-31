<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\Cliente;
use app\models\OrdenServicio;

/**
 * ClienteService: lógica de negocio para clientes del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ClienteService extends BaseService
{
    protected string $logCategoria = 'app.cliente';
    private string $warning = '';

    public function getWarning(): string
    {
        return $this->warning;
    }

    /**
     * Crea un nuevo cliente.
     *
     * @param array $data Datos del cliente.
     * @return Cliente|null El cliente creado o null en caso de error.
     */
    public function create(array $data): ?Cliente
    {
        return $this->executeInTransaction(function () use ($data): Cliente {
            $cliente = new Cliente();
            $cliente->nombre    = $data['nombre']    ?? '';
            $cliente->email     = !empty($data['email'])    ? $data['email']    : null;
            $cliente->telefono  = !empty($data['telefono']) ? $data['telefono'] : null;
            $cliente->direccion = !empty($data['direccion'])? $data['direccion']: null;
            $cliente->rut       = !empty($data['rut'])      ? $data['rut']      : null;
            $cliente->notas     = !empty($data['notas'])    ? $data['notas']    : null;
            $cliente->status    = (int) ($data['status'] ?? 1);

            if (!$cliente->validate()) {
                throw new ServiceException(implode('; ', $cliente->getFirstErrors()));
            }
            $this->asegurar($cliente->save(false), 'Error al guardar el cliente.');
            $this->log("Cliente creado: #{$cliente->id} ({$cliente->nombre})");
            return $cliente;
        });
    }

    /**
     * Actualiza un cliente existente.
     *
     * @param Cliente $cliente Instancia a actualizar.
     * @param array   $data    Datos a actualizar.
     * @return Cliente|null El cliente actualizado o null en caso de error.
     */
    public function update(Cliente $cliente, array $data): ?Cliente
    {
        return $this->executeInTransaction(function () use ($cliente, $data): Cliente {
            if (isset($data['nombre']))                     $cliente->nombre    = $data['nombre'];
            if (array_key_exists('email', $data))          $cliente->email     = !empty($data['email'])     ? $data['email']     : null;
            if (array_key_exists('telefono', $data))       $cliente->telefono  = !empty($data['telefono'])  ? $data['telefono']  : null;
            if (array_key_exists('direccion', $data))      $cliente->direccion = !empty($data['direccion']) ? $data['direccion'] : null;
            if (array_key_exists('rut', $data))            $cliente->rut       = !empty($data['rut'])       ? $data['rut']       : null;
            if (array_key_exists('notas', $data))          $cliente->notas     = !empty($data['notas'])     ? $data['notas']     : null;
            if (isset($data['status']))                    $cliente->status    = (int) $data['status'];

            if (!$cliente->validate()) {
                throw new ServiceException(implode('; ', $cliente->getFirstErrors()));
            }
            $this->asegurar($cliente->save(false), 'Error al actualizar el cliente.');
            $this->log("Cliente actualizado: #{$cliente->id}");
            return $cliente;
        });
    }

    /**
     * Desactiva un cliente (status=0).
     *
     * @param int $id ID del cliente.
     * @return bool True si se desactivó exitosamente.
     */
    public function deactivate(int $id): bool
    {
        $this->warning = '';
        $cliente = Cliente::findOne($id);
        if ($cliente === null) {
            $this->agregarError('Cliente no encontrado.');
            return false;
        }

        $ordenesActivas = (int) OrdenServicio::find()
            ->where(['cliente_id' => $id])
            ->andWhere(['estado' => ['abierto', 'en_progreso']])
            ->count();

        if ($ordenesActivas > 0) {
            $this->warning = 'El cliente tenía órdenes activas al momento de la desactivación.';
        }

        $cliente->status = 0;
        if (!$cliente->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar el cliente.');
            return false;
        }
        $this->log("Cliente desactivado: #{$id}");
        return true;
    }

    /**
     * Retorna clientes activos para dropdowns en otros módulos.
     *
     * @return array<int,string>
     */
    public function getClientesActivos(): array
    {
        return Cliente::find()
            ->where(['status' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }

    /**
     * Estadísticas básicas de clientes.
     *
     * @return array{total: int, activos: int, nuevos_mes: int}
     */
    public function getEstadisticas(): array
    {
        $inicioMes = mktime(0, 0, 0, (int) date('n'), 1, (int) date('Y'));

        try {
            return [
                'total'      => (int) Cliente::find()->count(),
                'activos'    => (int) Cliente::find()->where(['status' => 1])->count(),
                'nuevos_mes' => (int) Cliente::find()->where(['>=', 'created_at', $inicioMes])->count(),
            ];
        } catch (\Throwable $exception) {
            return [
                'total'      => 0,
                'activos'    => 0,
                'nuevos_mes' => 0,
            ];
        }
    }
}
