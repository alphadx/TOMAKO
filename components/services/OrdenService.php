<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use app\models\AsignacionOrden;
use app\models\OrdenNota;
use app\models\ChecklistItem;
use app\models\OrdenEstadoLog;
use app\models\Cita;
use app\models\Servicio;
use app\models\Notificacion;

/**
 * OrdenService: lógica de negocio para órdenes de servicio.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class OrdenService extends BaseService
{
    protected string $logCategoria = 'app.orden';

    /**
     * Crea una orden de servicio con detalles y técnicos.
     *
     * @param array    $data           Atributos de la orden.
     * @param array    $servicioItems  [[servicio_id, cantidad, precio_unitario, nota], ...]
     * @param int[]    $tecnicoIds     IDs de técnicos a asignar.
     * @param string[] $checklistItems Lista de items para inicializar checklist (opcional)
     * @return OrdenServicio|null
     */
    public function create(array $data, array $servicioItems, array $tecnicoIds, array $checklistItems = []): ?OrdenServicio
    {
        return $this->executeInTransaction(function () use ($data, $servicioItems, $tecnicoIds, $checklistItems): OrdenServicio {
            $orden = new OrdenServicio();
            $orden->setAttributes($data);

            if (!$orden->validate()) {
                throw new ServiceException(implode('; ', $orden->getFirstErrors()));
            }
            $this->asegurar($orden->save(false), 'Error al guardar la orden de servicio.');

            foreach ($servicioItems as $item) {
                $this->agregarDetalleInterno(
                    $orden,
                    (int) $item['servicio_id'],
                    (int) ($item['cantidad'] ?? 1),
                    (float) ($item['precio_unitario'] ?? 0),
                    isset($item['nota']) ? trim((string) $item['nota']) : null
                );
            }

            foreach (array_unique($tecnicoIds) as $tid) {
                $this->asignarTecnicoInterno($orden, (int) $tid);
            }

            // Inicializar checklist si se proporcionaron items personalizados
            foreach ($checklistItems as $itemStr) {
                $item = trim((string) $itemStr);
                if ($item === '') {
                    continue;
                }
                $check = new ChecklistItem();
                $check->orden_id = $orden->id;
                $check->item = $item;
                $check->completado = false;
                $check->created_at = time();
                if (!$check->save(false)) {
                    throw new ServiceException('Error al guardar item del checklist.');
                }
            }

            $this->registrarLog($orden, null, $orden->estado, null, 'Orden creada');
            $orden->calcularTotal();

            $this->log("Orden creada: #{$orden->id} ({$orden->codigo})");
            return $orden;
        });
    }

    /**
     * Crea una orden pre-llenada desde una cita confirmada.
     *
     * @param int $citaId
     * @return OrdenServicio|null
     */
    public function crearDesdeCita(int $citaId): ?OrdenServicio
    {
        $cita = Cita::findOne($citaId);
        if ($cita === null) {
            $this->agregarError('Cita no encontrada.');
            return null;
        }

        $servicioItems = [];
        foreach ($cita->servicios as $s) {
            $servicioItems[] = [
                'servicio_id'     => $s->id,
                'cantidad'        => 1,
                'precio_unitario' => $s->precio_base,
            ];
        }

        $orden = $this->create([
            'cliente_id'  => $cita->cliente_id,
            'vehiculo_id' => $cita->vehiculo_id,
            'cita_id'     => $cita->id,
            'prioridad'   => 'normal',
        ], $servicioItems, []);

        if ($orden !== null) {
            // Vincular cita con la orden creada
            $cita->orden_servicio_id = $orden->id;
            $cita->save(false, ['orden_servicio_id', 'updated_at']);
        }

        return $orden;
    }

    /**
     * Cambia el estado de la orden validando transiciones.
     *
     * @param OrdenServicio $orden
     * @param string        $nuevoEstado
     * @param string        $comentario
     * @param int|null      $userId
     * @return bool
     */
    public function cambiarEstado(OrdenServicio $orden, string $nuevoEstado, string $comentario = '', ?int $userId = null): bool
    {
        if ($nuevoEstado === 'entregada') {
            $saldoPendiente = (new PagoService())->getSaldoPendiente((int) $orden->id);
            if ($saldoPendiente > 0) {
                $this->agregarError('No se puede entregar la orden: existe saldo pendiente.');
                return false;
            }
        }

        if (!$orden->puedeTransicionar($nuevoEstado)) {
            $this->agregarError("No se puede cambiar de '{$orden->estado}' a '{$nuevoEstado}'.");
            return false;
        }

        $estadoAnterior = $orden->estado;
        $orden->estado  = $nuevoEstado;
        if (!$orden->save(false, ['estado', 'closed_at', 'updated_at'])) {
            $this->agregarError('Error al cambiar el estado de la orden.');
            return false;
        }

        $this->registrarLog($orden, $estadoAnterior, $nuevoEstado, $userId, $comentario);
        $this->log("Orden #{$orden->id}: {$estadoAnterior} → {$nuevoEstado}");

        $this->notificarCambioEstado($orden, $estadoAnterior, $nuevoEstado);

        return true;
    }

    /**
     * Agrega una nota a la orden.
     *
     * @param int    $ordenId
     * @param string $texto
     * @param int    $userId
     * @return OrdenNota|null
     */
    public function agregarNota(int $ordenId, string $texto, int $userId): ?OrdenNota
    {
        return $this->executeInTransaction(function () use ($ordenId, $texto, $userId): OrdenNota {
            $nota             = new OrdenNota();
            $nota->orden_id   = $ordenId;
            $nota->usuario_id = $userId;
            $nota->texto      = $texto;
            $this->asegurar($nota->save(false), 'Error al guardar la nota.');
            $this->log("Nota agregada a orden #{$ordenId}");
            return $nota;
        });
    }

    /**
     * Agrega un detalle de servicio a la orden y recalcula total.
     *
     * @param OrdenServicio $orden
     * @param int           $servicioId
     * @param int           $cantidad
     * @return OrdenServicioDetalle|null
     */
    public function agregarDetalle(OrdenServicio $orden, int $servicioId, int $cantidad, ?float $precioUnitario = null, ?string $nota = null): ?OrdenServicioDetalle
    {
        return $this->executeInTransaction(function () use ($orden, $servicioId, $cantidad, $precioUnitario, $nota): OrdenServicioDetalle {
            $servicio = Servicio::findOne($servicioId);
            $this->asegurar($servicio !== null, 'Servicio no encontrado.');

            $precio = $precioUnitario !== null ? max(0, $precioUnitario) : (float) $servicio->precio_base;
            $detalle = $this->agregarDetalleInterno($orden, $servicioId, $cantidad, $precio, $nota);
            $orden->calcularTotal();
            return $detalle;
        });
    }

    /**
     * Asigna un técnico a la orden y registra una notificación visible en notas.
     *
     * @param OrdenServicio $orden
     * @param int           $tecnicoId
     * @return bool true si la asignación se creó, false si ya existía o falló.
     */
    public function asignarTecnico(OrdenServicio $orden, int $tecnicoId): bool
    {
        $result = $this->executeInTransaction(function () use ($orden, $tecnicoId): bool {
            $creada = $this->asignarTecnicoInterno($orden, $tecnicoId);
            if (!$creada) {
                throw new ServiceException('El técnico ya se encuentra asignado a la orden.');
            }

            $nota             = new OrdenNota();
            $nota->orden_id   = $orden->id;
            $nota->usuario_id = null;
            $nota->texto      = "Notificación enviada al técnico #{$tecnicoId} por asignación de la orden {$orden->codigo}.";
            $this->asegurar($nota->save(false), 'Error al registrar notificación de asignación.');

            $this->log("Técnico #{$tecnicoId} asignado a orden #{$orden->id}");
            return true;
        });

        return $result === true;
    }

    /**
     * Desasigna un técnico de la orden.
     *
     * @param OrdenServicio $orden
     * @param int           $tecnicoId
     * @return bool true si se desasignó correctamente.
     */
    public function desasignarTecnico(OrdenServicio $orden, int $tecnicoId): bool
    {
        $result = $this->executeInTransaction(function () use ($orden, $tecnicoId): bool {
            $asignacion = AsignacionOrden::findOne(['orden_id' => $orden->id, 'tecnico_id' => $tecnicoId]);
            if ($asignacion === null) {
                throw new ServiceException('La asignación del técnico no existe para esta orden.');
            }

            $this->asegurar((bool) $asignacion->delete(), 'No fue posible desasignar el técnico de la orden.');

            $nota             = new OrdenNota();
            $nota->orden_id   = $orden->id;
            $nota->usuario_id = null;
            $nota->texto      = "Técnico #{$tecnicoId} desasignado de la orden {$orden->codigo}.";
            $this->asegurar($nota->save(false), 'Error al registrar evento de desasignación.');

            $this->log("Técnico #{$tecnicoId} desasignado de orden #{$orden->id}");
            return true;
        });

        return $result === true;
    }

    /**
     * Retorna órdenes activas (no entregadas ni canceladas).
     *
     * @return OrdenServicio[]
     */
    public function getOrdenesActivas(): array
    {
        return OrdenServicio::find()
            ->with(['cliente', 'vehiculo'])
            ->where(['not', ['estado' => ['entregada', 'cancelada']]])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();
    }

    /**
     * Retorna KPIs del módulo.
     *
     * @return array{activos: int, en_progreso: int, listo_para_entrega: int, entregadas_hoy: int}
     */
    public function getKpis(): array
    {
        $hoyInicio = mktime(0, 0, 0);
        $hoyFin    = mktime(23, 59, 59);

        return [
            'activos'            => (int) OrdenServicio::find()->where(['not', ['estado' => ['entregada', 'cancelada']]])->count(),
            'en_progreso'        => (int) OrdenServicio::find()->where(['estado' => 'en_progreso'])->count(),
            'listo_para_entrega' => (int) OrdenServicio::find()->where(['estado' => 'listo_para_entrega'])->count(),
            'entregadas_hoy'     => (int) OrdenServicio::find()->where(['estado' => 'entregada'])
                ->andWhere(['between', 'closed_at', $hoyInicio, $hoyFin])->count(),
        ];
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function agregarDetalleInterno(OrdenServicio $orden, int $servicioId, int $cantidad, float $precioUnitario, ?string $nota = null): OrdenServicioDetalle
    {
        $detalle                  = new OrdenServicioDetalle();
        $detalle->orden_id        = $orden->id;
        $detalle->servicio_id     = $servicioId;
        $detalle->cantidad        = $cantidad;
        $detalle->precio_unitario = $precioUnitario;
        $detalle->nota            = $nota !== null && $nota !== '' ? $nota : null;
        if (!$detalle->save()) {
            throw new ServiceException('Error al guardar detalle de la orden.');
        }
        return $detalle;
    }

    private function asignarTecnicoInterno(OrdenServicio $orden, int $tecnicoId): bool
    {
        $existe = AsignacionOrden::find()
            ->where(['orden_id' => $orden->id, 'tecnico_id' => $tecnicoId])
            ->exists();
        if ($existe) {
            return false;
        }

        $asig             = new AsignacionOrden();
        $asig->orden_id   = $orden->id;
        $asig->tecnico_id = $tecnicoId;
        $this->asegurar($asig->save(false), 'Error al guardar asignación de técnico.');
        return true;
    }

    private function registrarLog(OrdenServicio $orden, ?string $anterior, string $nuevo, ?int $userId, string $comentario): void
    {
        $log                   = new OrdenEstadoLog();
        $log->orden_id         = $orden->id;
        $log->estado_anterior  = $anterior;
        $log->estado_nuevo     = $nuevo;
        $log->usuario_id       = $userId;
        $log->comentario       = $comentario ?: null;
        $log->save(false);
    }

    private function notificarCambioEstado(OrdenServicio $orden, string $anterior, string $nuevo): void
    {
        $service = new NotificacionService();
        $uid = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;

        if ($uid !== null) {
            $service->crearNotificacion(
                $uid,
                $nuevo === 'listo_para_entrega' ? Notificacion::TIPO_ORDEN_LISTA : Notificacion::TIPO_SISTEMA,
                "Orden {$orden->codigo}: {$anterior} -> {$nuevo}",
                "La orden {$orden->codigo} cambio de estado a {$nuevo}.",
                '/orden/' . $orden->id
            );
        }

        $email = trim((string) ($orden->cliente->email ?? ''));
        if ($email !== '') {
            $service->enviarEmail($email, 'orden.estado_actualizado', [
                'cliente_nombre' => (string) ($orden->cliente->nombre ?? ''),
                'codigo_orden' => (string) $orden->codigo,
                'estado' => (string) $nuevo,
                'vehiculo' => (string) ($orden->vehiculo->patente ?? ''),
            ]);
        }
    }
}
