<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use Exception;
use yii\db\Transaction;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use app\models\OrdenServicioRepuesto;
use app\models\AsignacionOrden;
use app\models\OrdenNota;
use app\models\OrdenEstadoLog;
use app\models\ChecklistItem;
use app\models\PlantillaChecklist;
use app\models\Servicio;
use app\models\Tecnico;
use app\models\Cita;
use app\models\Vehiculo;
use app\models\InventoryItem;
use app\models\InventoryMovement;

/**
 * OrdenServicioService
 *
 * Service layer for work order management.
 * Handles: CRUD, state transitions, technician assignments, inventory consumption, closures.
 */
class OrdenServicioService
{
    /**
     * Create a new work order
     *
     * @param array $data Attributes: cliente_id, vehiculo_id, cita_id (optional), prioridad
     * @param int $usuarioId User creating the order
     * @return OrdenServicio
     * @throws Exception
     */
    public function create(array $data, int $usuarioId): OrdenServicio
    {
        $clienteId = filter_var($data['cliente_id'] ?? null, FILTER_VALIDATE_INT);
        $vehiculoId = filter_var($data['vehiculo_id'] ?? null, FILTER_VALIDATE_INT);

        if ($clienteId === false || $vehiculoId === false) {
            throw new Exception('Cliente y vehículo son requeridos.');
        }

        $orden = new OrdenServicio();
        $orden->attributes = $data;
        $orden->cliente_id = (int)$clienteId;
        $orden->vehiculo_id = (int)$vehiculoId;

        // Validate vehicle belongs to client
        if (!$this->validarVehiculoDelCliente((int)$clienteId, (int)$vehiculoId)) {
            throw new Exception('El vehículo no pertenece a este cliente.');
        }

        if (!$orden->save()) {
            throw new Exception('Error al crear orden: ' . implode(', ', $orden->getErrorSummary(true)));
        }

        // Aplicar plantillas de checklist por servicio
        $this->aplicarPlantillasChecklist($orden);

        // Log state transition
        $this->registrarCambioEstado($orden->id, null, OrdenServicio::ESTADO_ABIERTO, 'Orden creada', $usuarioId);

        return $orden;
    }

    /**
     * Create order from existing appointment
     *
     * @param int $citaId ID of the appointment
     * @param int $usuarioId User creating the order
     * @return OrdenServicio
     * @throws Exception
     */
    public function createDesdeCita(int $citaId, int $usuarioId): OrdenServicio
    {
        $cita = Cita::findOne($citaId);
        if (!$cita) {
            throw new Exception('Cita no encontrada.');
        }

        $tx = Yii::$app->db->beginTransaction(Transaction::READ_COMMITTED);
        try {
            // Create order with cita data
            $orden = new OrdenServicio();
            $orden->cliente_id = $cita->cliente_id;
            $orden->vehiculo_id = $cita->vehiculo_id;
            $orden->cita_id = $cita->id;
            $orden->prioridad = $cita->prioridad ?? OrdenServicio::PRIORIDAD_NORMAL;

            if (!$orden->save()) {
                throw new Exception('Error al crear orden desde cita.');
            }

            // Copy services from cita
            if ($cita->citaServicios) {
                foreach ($cita->citaServicios as $citaServicio) {
                    $this->agregarServicio(
                        $orden->id,
                        $citaServicio->servicio_id,
                        $citaServicio->cantidad ?? 1,
                        $usuarioId
                    );
                }
            }

            // Apply checklist templates by service
            $this->aplicarPlantillasChecklist($orden);

            $tx->commit();

            // Log transition
            $this->registrarCambioEstado($orden->id, null, OrdenServicio::ESTADO_ABIERTO, 'Orden creada desde cita #' . $cita->id, $usuarioId);

            return $orden;
        } catch (Exception $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * Add a service/line item to the order
     *
     * @param int $ordenId
     * @param int $servicioId
     * @param int $cantidad
     * @param int $usuarioId
     * @return OrdenServicioDetalle
     * @throws Exception
     */
    public function agregarServicio(int $ordenId, int $servicioId, int $cantidad = 1, int $usuarioId = 0): OrdenServicioDetalle
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        $servicio = Servicio::findOne($servicioId);
        if (!$servicio) {
            throw new Exception('Servicio no encontrado.');
        }

        $detalle = new OrdenServicioDetalle();
        $detalle->orden_id = $orden->id;
        $detalle->servicio_id = $servicio->id;
        $detalle->cantidad = $cantidad;
        $detalle->precio_unitario = $servicio->precio_base;
        $detalle->subtotal = $servicio->precio_base * $cantidad;

        if (!$detalle->save()) {
            throw new Exception('Error al agregar servicio: ' . implode(', ', $detalle->getErrorSummary(true)));
        }

        // Recalculate order total
        $this->calcularTotal($orden->id);

        return $detalle;
    }

    /**
     * Calculate total from all line items
     *
     * @param int $ordenId
     * @return float
     */
    public function calcularTotal(int $ordenId): float
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        $suma = (float)OrdenServicioDetalle::find()
            ->where(['orden_id' => $ordenId])
            ->sum('subtotal') ?? 0;

        $orden->total = $suma;
        $orden->save(false, ['total']);

        return $suma;
    }

    /**
     * Assign technician to order
     *
     * @param int $ordenId
     * @param int $tecnicoId
     * @param int $usuarioId
     * @return AsignacionOrden
     * @throws Exception
     */
    public function asignarTecnico(int $ordenId, int $tecnicoId, int $usuarioId = 0): AsignacionOrden
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        $tecnico = Tecnico::findOne($tecnicoId);
        if (!$tecnico) {
            throw new Exception('Técnico no encontrado.');
        }

        // Check if already assigned
        $existing = AsignacionOrden::findOne(['orden_id' => $ordenId, 'tecnico_id' => $tecnicoId]);
        if ($existing) {
            return $existing;
        }

        $asignacion = new AsignacionOrden();
        $asignacion->orden_id = $ordenId;
        $asignacion->tecnico_id = $tecnicoId;
        $asignacion->asignado_at = time();

        if (!$asignacion->save()) {
            throw new Exception('Error al asignar técnico.');
        }

        return $asignacion;
    }

    /**
     * Change order state with validation
     *
     * @param int $ordenId
     * @param string $nuevoEstado
     * @param string $motivo
     * @param int $usuarioId
     * @return OrdenServicio
     * @throws Exception
     */
    public function cambiarEstado(int $ordenId, string $nuevoEstado, string $motivo = '', int $usuarioId = 0): OrdenServicio
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        // Validate transition
        if (!$orden->puedeTransicionar($nuevoEstado)) {
            throw new Exception(
                "No se puede transicionar de '{$orden->estado}' a '{$nuevoEstado}'."
            );
        }

        $estadoAnterior = $orden->estado;
        $orden->estado = $nuevoEstado;

        if (!$orden->save()) {
            throw new Exception('Error al cambiar estado: ' . implode(', ', $orden->getErrorSummary(true)));
        }

        // Log the transition
        $this->registrarCambioEstado($orden->id, $estadoAnterior, $nuevoEstado, $motivo, $usuarioId);

        return $orden;
    }

    /**
     * Cancel an open order
     *
     * @param int $ordenId
     * @param string $motivo Reason for cancellation
     * @param int $usuarioId
     * @return OrdenServicio
     * @throws Exception
     */
    public function cancelarOrden(int $ordenId, string $motivo = '', int $usuarioId = 0): OrdenServicio
    {
        return $this->cambiarEstado($ordenId, OrdenServicio::ESTADO_CANCELADA, $motivo ?: 'Cancelada por usuario', $usuarioId);
    }

    /**
     * Consume inventory item (call InventarioService)
     *
     * @param int $ordenId
     * @param int $itemId Inventory item ID
     * @param int $cantidad
     * @param int $usuarioId
     * @throws Exception
     */
    public function consumirInsumo(int $ordenId, int $itemId, int $cantidad, int $usuarioId = 0): void
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        // Call InventarioService
        $inventarioService = Yii::$app->get('inventarioService');
        if (!$inventarioService) {
            throw new Exception('InventarioService no configurado.');
        }

        try {
            $inventarioService->consumir($itemId, $cantidad, 'Consumo orden #' . $orden->codigo);
        } catch (Exception $e) {
            throw new Exception('Error al consumir insumo: ' . $e->getMessage());
        }
    }

    /**
     * Add note to order
     *
     * @param int $ordenId
     * @param string $texto
     * @param int $usuarioId
     * @return OrdenNota
     * @throws Exception
     */
    public function agregarNota(int $ordenId, string $texto, int $usuarioId = 0): OrdenNota
    {
        $orden = OrdenServicio::findOne($ordenId);
        if (!$orden) {
            throw new Exception('Orden no encontrada.');
        }

        $nota = new OrdenNota();
        $nota->orden_id = $ordenId;
        $nota->usuario_id = $usuarioId ?: Yii::$app->user->id;
        $nota->texto = $texto;
        $nota->created_at = time();

        if (!$nota->save()) {
            throw new Exception('Error al agregar nota.');
        }

        return $nota;
    }

    /**
     * Close order (mark as entregada) with checklist validation
     *
     * @param int $ordenId
     * @param array $checklistIds Checked checklist item IDs
     * @param int $usuarioId
     * @return OrdenServicio
     * @throws Exception
     */
    public function cerrarOrden(int $ordenId, array $checklistIds = [], int $usuarioId = 0): OrdenServicio
    {
        $tx = Yii::$app->db->beginTransaction(Transaction::READ_COMMITTED);
        try {
            $orden = OrdenServicio::findOne($ordenId);
            if (!$orden) {
                throw new Exception('Orden no encontrada.');
            }

            // Check state
            if ($orden->estado !== OrdenServicio::ESTADO_LISTO_PARA_ENTREGA) {
                throw new Exception('Orden debe estar en estado "Listo para Entrega" para poder cerrar.');
            }

            // Validate checklist
            $checklistItems = ChecklistItem::find()->where(['orden_id' => $ordenId])->all();
            if ($checklistItems) {
                $allChecked = true;
                foreach ($checklistItems as $item) {
                    if (!in_array($item->id, $checklistIds, true)) {
                        $allChecked = false;
                        break;
                    }
                    $item->completado = true;
                    $item->save(false);
                }

                if (!$allChecked) {
                    throw new Exception('Todos los items del checklist deben estar completados.');
                }
            }

            // Validate payment balance (placeholder for Hito 10)
            // $saldoPendiente = $orden->getSaldoPendiente();
            // if ($saldoPendiente > 0) {
            //     throw new Exception('La orden tiene saldo pendiente: ' . $saldoPendiente);
            // }

            // Transition to entregada
            $orden = $this->cambiarEstado($ordenId, OrdenServicio::ESTADO_ENTREGADA, 'Orden entregada', $usuarioId);

            $tx->commit();

            return $orden;
        } catch (Exception $e) {
            $tx->rollBack();
            throw $e;
        }
    }

    /**
     * Get KPIs for dashboard
     *
     * @return array {activos: int, listos: int, pendientes: int}
     */
    public function getKPIs(): array
    {
        $activos = OrdenServicio::find()
            ->where(['IN', 'estado', [
                OrdenServicio::ESTADO_ABIERTO,
                OrdenServicio::ESTADO_EN_PROGRESO,
                OrdenServicio::ESTADO_ESPERANDO_REPUESTOS,
            ]])
            ->count();

        $listos = OrdenServicio::find()
            ->where(['estado' => OrdenServicio::ESTADO_LISTO_PARA_ENTREGA])
            ->count();

        $pendientes = OrdenServicio::find()
            ->where(['estado' => OrdenServicio::ESTADO_ENTREGADA])
            ->count();

        return [
            'activos' => $activos,
            'listos' => $listos,
            'pendientes' => $pendientes,
        ];
    }

    /**
     * Validate that vehicle belongs to client.
     * Accepts string IDs from raw POST payloads to avoid strict_types TypeError.
     *
     * @param int|string $clienteId
     * @param int|string $vehiculoId
     * @return bool
     */
    public function validarVehiculoDelCliente($clienteId, $vehiculoId): bool
    {
        $clienteId = filter_var($clienteId, FILTER_VALIDATE_INT);
        $vehiculoId = filter_var($vehiculoId, FILTER_VALIDATE_INT);

        if ($clienteId === false || $vehiculoId === false) {
            return false;
        }

        $vehiculo = Vehiculo::findOne((int)$vehiculoId);
        return $vehiculo !== null && (int)$vehiculo->cliente_id === (int)$clienteId;
    }

    // ─── Private Helper Methods ───────────────────────────────────────

    /**
     * Register state transition in log
     */
    private function registrarCambioEstado(int $ordenId, ?string $estadoAnterior, string $estadoNuevo, string $comentario = '', int $usuarioId = 0): void
    {
        $log = new OrdenEstadoLog();
        $log->orden_id = $ordenId;
        $log->estado_anterior = $estadoAnterior;
        $log->estado_nuevo = $estadoNuevo;
        $log->usuario_id = $usuarioId ?: (Yii::$app->user->isGuest ? null : Yii::$app->user->id);
        $log->comentario = $comentario;
        $log->created_at = time();
        $log->save(false);
    }

    /**
     * Aplica plantillas de checklist según los servicios en la orden
     */
    private function aplicarPlantillasChecklist(OrdenServicio $orden): void
    {
        // Obtener todos los servicios únicos en la orden
        $serviciosIds = array_unique(
            OrdenServicioDetalle::find()
                ->select('servicio_id')
                ->where(['orden_id' => $orden->id])
                ->column()
        );

        if (empty($serviciosIds)) {
            return;
        }

        // Buscar plantillas activas para cada servicio
        $plantillas = PlantillaChecklist::find()
            ->where(['servicio_id' => $serviciosIds, 'activa' => true])
            ->all();

        $itemsCreados = 0;
        foreach ($plantillas as $plantilla) {
            $itemsCreados += $plantilla->aplicarAOrden($orden);
        }

        // Si no hay plantillas específicas, usar checklist genérico por defecto
        if ($itemsCreados === 0) {
            $this->inicializarChecklist($orden->id);
        }
    }

    /**
     * Initialize default checklist items
     */
    private function inicializarChecklist(int $ordenId): void
    {
        $items = [
            'Vehículo limpiado',
            'Repuestos instalados correctamente',
            'Prueba de funcionamiento completada',
            'Documentación entregada',
            'Cliente notificado',
        ];

        foreach ($items as $item) {
            $checklist = new ChecklistItem();
            $checklist->orden_id = $ordenId;
            $checklist->item = $item;
            $checklist->completado = false;
            $checklist->created_at = time();
            $checklist->save(false);
        }
    }

    /**
     * Agregar repuesto a orden (HU-013)
     * Descuenta stock automáticamente y crea movimiento de inventario
     *
     * @param int $ordenId ID de la orden
     * @param int $repuestoId ID del repuesto/insumo
     * @param int $cantidad Cantidad a utilizar
     * @param int $usuarioId Usuario que realiza la acción
     * @return OrdenServicioRepuesto
     * @throws Exception
     */
    public function agregarRepuesto(int $ordenId, int $repuestoId, int $cantidad, int $usuarioId): OrdenServicioRepuesto
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            // Validar que la orden existe y no está cerrada
            $orden = OrdenServicio::findOne($ordenId);
            if (!$orden) {
                throw new Exception('Orden no encontrada.');
            }
            if (in_array($orden->estado, ['entregada', 'cancelada'], true)) {
                throw new Exception('No se pueden agregar repuestos a una orden cerrada o cancelada.');
            }

            // Validar repuesto
            $repuesto = InventoryItem::findOne($repuestoId);
            if (!$repuesto) {
                throw new Exception('Repuesto no encontrado.');
            }

            // Validar stock disponible
            if ($repuesto->cantidad < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: {$repuesto->cantidad}, Solicitado: {$cantidad}");
            }

            // Crear relación orden-repuesto
            $ordenRepuesto = new OrdenServicioRepuesto();
            $ordenRepuesto->orden_id = $ordenId;
            $ordenRepuesto->repuesto_id = $repuestoId;
            $ordenRepuesto->cantidad = $cantidad;
            $ordenRepuesto->precio_unitario_aplicado = $repuesto->precio_unitario;
            $ordenRepuesto->save(false);

            // Descontar stock
            $stockAnterior = $repuesto->cantidad;
            $repuesto->cantidad -= $cantidad;
            $repuesto->save(false, ['cantidad', 'updated_at']);

            // Registrar movimiento de inventario
            $movimiento = new InventoryMovement();
            $movimiento->item_id = $repuestoId;
            $movimiento->tipo = 'salida';
            $movimiento->cantidad_delta = -$cantidad;
            $movimiento->cantidad_anterior = $stockAnterior;
            $movimiento->cantidad_nueva = $repuesto->cantidad;
            $movimiento->usuario_id = $usuarioId;
            $movimiento->referencia = "Orden #{$orden->codigo} - Repuesto utilizado";
            $movimiento->save(false);

            $transaction->commit();

            return $ordenRepuesto;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Eliminar repuesto de orden (HU-013)
     * Reintegra stock automáticamente
     *
     * @param int $ordenId ID de la orden
     * @param int $repuestoId ID del repuesto
     * @param int $usuarioId Usuario que realiza la acción
     * @throws Exception
     */
    public function eliminarRepuesto(int $ordenId, int $repuestoId, int $usuarioId): void
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            // Buscar la relación
            $ordenRepuesto = OrdenServicioRepuesto::findOne([
                'orden_id' => $ordenId,
                'repuesto_id' => $repuestoId,
            ]);

            if (!$ordenRepuesto) {
                throw new Exception('El repuesto no está asignado a esta orden.');
            }

            $cantidad = $ordenRepuesto->cantidad;
            $repuesto = $ordenRepuesto->repuesto;

            // Reintegrar stock
            $stockAnterior = $repuesto->cantidad;
            $repuesto->cantidad += $cantidad;
            $repuesto->save(false, ['cantidad', 'updated_at']);

            // Registrar movimiento de inventario
            $movimiento = new InventoryMovement();
            $movimiento->item_id = $repuesto->id;
            $movimiento->tipo = 'entrada';
            $movimiento->cantidad_delta = $cantidad;
            $movimiento->cantidad_anterior = $stockAnterior;
            $movimiento->cantidad_nueva = $repuesto->cantidad;
            $movimiento->usuario_id = $usuarioId;
            $movimiento->referencia = "Reintegro - Orden #{$ordenRepuesto->orden->codigo}";
            $movimiento->save(false);

            // Eliminar relación
            $ordenRepuesto->delete();

            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Actualizar cantidad de repuesto en orden (HU-013)
     *
     * @param int $id ID del registro orden_repuesto
     * @param int $nuevaCantidad Nueva cantidad
     * @param int $usuarioId Usuario que realiza la acción
     * @return OrdenServicioRepuesto
     * @throws Exception
     */
    public function actualizarCantidadRepuesto(int $id, int $nuevaCantidad, int $usuarioId): OrdenServicioRepuesto
    {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction(Transaction::READ_COMMITTED);

        try {
            $ordenRepuesto = OrdenServicioRepuesto::findOne($id);
            if (!$ordenRepuesto) {
                throw new Exception('Registro no encontrado.');
            }

            if ($nuevaCantidad <= 0) {
                throw new Exception('La cantidad debe ser mayor a cero.');
            }

            $diferencia = $nuevaCantidad - $ordenRepuesto->cantidad;
            $repuesto = $ordenRepuesto->repuesto;

            // Validar stock si se aumenta la cantidad
            if ($diferencia > 0 && $repuesto->cantidad < $diferencia) {
                throw new Exception("Stock insuficiente para aumentar la cantidad.");
            }

            // Ajustar stock
            $stockAnterior = $repuesto->cantidad;
            $repuesto->cantidad -= $diferencia;
            $repuesto->save(false, ['cantidad', 'updated_at']);

            // Registrar movimiento
            $movimiento = new InventoryMovement();
            $movimiento->item_id = $repuesto->id;
            $movimiento->tipo = $diferencia > 0 ? 'salida' : 'entrada';
            $movimiento->cantidad_delta = -$diferencia;
            $movimiento->cantidad_anterior = $stockAnterior;
            $movimiento->cantidad_nueva = $repuesto->cantidad;
            $movimiento->usuario_id = $usuarioId;
            $movimiento->referencia = "Ajuste cantidad - Orden #{$ordenRepuesto->orden->codigo}";
            $movimiento->save(false);

            // Actualizar registro
            $ordenRepuesto->cantidad = $nuevaCantidad;
            $ordenRepuesto->save(false, ['cantidad', 'subtotal', 'updated_at']);

            $transaction->commit();

            return $ordenRepuesto;
        } catch (Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }
}
