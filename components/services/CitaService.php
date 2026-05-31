<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\Cita;
use app\models\CitaServicio;
use yii\helpers\Json;

/**
 * CitaService: lógica de negocio para citas del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class CitaService extends BaseService
{
    protected string $logCategoria = 'app.cita';

    /**
     * Crea una nueva cita con sus servicios asociados.
     *
     * @param array $data       Atributos de la cita.
     * @param int[] $servicioIds IDs de servicios a asociar.
     * @return Cita|null
     */
    public function create(array $data, array $servicioIds): ?Cita
    {
        return $this->executeInTransaction(function () use ($data, $servicioIds): Cita {
            if (empty($servicioIds)) {
                throw new ServiceException('Seleccione al menos un servicio para la cita.');
            }

            $cita = new Cita();
            $cita->setAttributes($data);

            if (!$cita->validate()) {
                throw new ServiceException(implode('; ', $cita->getFirstErrors()));
            }
            $this->asegurar($cita->save(false), 'Error al guardar la cita.');

            $this->sincronizarServicios($cita, $servicioIds);
            $this->enviarEmailConfirmacion($cita, false);

            $this->log("Cita creada: #{$cita->id} ({$cita->fecha} {$cita->hora_inicio})");
            return $cita;
        });
    }

    /**
     * Actualiza una cita existente.
     *
     * @param Cita  $cita
     * @param array $data
     * @param int[] $servicioIds
     * @return Cita|null
     */
    public function update(Cita $cita, array $data, array $servicioIds): ?Cita
    {
        return $this->executeInTransaction(function () use ($cita, $data, $servicioIds): Cita {
            if ($cita->estado !== 'pendiente') {
                throw new ServiceException('Solo se pueden editar citas en estado pendiente.');
            }
            if (empty($servicioIds)) {
                throw new ServiceException('Seleccione al menos un servicio para la cita.');
            }

            $cita->setAttributes($data);

            if (!$cita->validate()) {
                throw new ServiceException(implode('; ', $cita->getFirstErrors()));
            }
            $this->asegurar($cita->save(false), 'Error al actualizar la cita.');

            $this->sincronizarServicios($cita, $servicioIds);

            $this->log("Cita actualizada: #{$cita->id}");
            return $cita;
        });
    }

    /**
     * Cambia estado a 'confirmada' (solo desde 'pendiente').
     */
    public function confirmar(int $id): bool
    {
        $cita = $this->findCita($id);
        if ($cita === null) {
            return false;
        }
        if ($cita->estado !== 'pendiente') {
            $this->agregarError('Solo se pueden confirmar citas en estado pendiente.');
            return false;
        }
        $ok = $this->cambiarEstado($cita, 'confirmada');
        if ($ok) {
            $this->enviarEmailConfirmacion($cita, true);
        }
        return $ok;
    }

    /**
     * Cancela una cita (cualquier estado).
     */
    public function cancelar(int $id): bool
    {
        $cita = $this->findCita($id);
        if ($cita === null) {
            return false;
        }
        return $this->cambiarEstado($cita, 'cancelada');
    }

    /**
     * Marca como no_show (solo desde 'confirmada').
     */
    public function marcarNoShow(int $id): bool
    {
        $cita = $this->findCita($id);
        if ($cita === null) {
            return false;
        }
        if ($cita->estado !== 'confirmada') {
            $this->agregarError('Solo se puede marcar como no-show una cita confirmada.');
            return false;
        }
        return $this->cambiarEstado($cita, 'no_show');
    }

    /**
     * Inicia el servicio (confirmada → en_progreso).
     */
    public function iniciarServicio(int $id): bool
    {
        $cita = $this->findCita($id);
        if ($cita === null) {
            return false;
        }
        if ($cita->estado !== 'confirmada') {
            $this->agregarError('Solo se puede iniciar servicio en citas confirmadas.');
            return false;
        }
        return $this->cambiarEstado($cita, 'en_progreso');
    }

    /**
     * Reprograma una cita pendiente/confirmada a una nueva fecha y hora.
     */
    public function reprogramar(int $id, string $nuevaFecha, string $nuevaHoraInicio, string $nuevaHoraFin): bool
    {
        $cita = $this->findCita($id);
        if ($cita === null) {
            return false;
        }

        if (!in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            $this->agregarError('Solo se pueden reprogramar citas pendientes o confirmadas.');
            return false;
        }

        $cita->fecha = $nuevaFecha;
        $cita->hora_inicio = $nuevaHoraInicio;
        $cita->hora_fin = $nuevaHoraFin;

        if (!$cita->validate(['fecha', 'hora_inicio', 'hora_fin'])) {
            $this->agregarError(implode('; ', $cita->getFirstErrors()));
            return false;
        }

        if (!$cita->save(false, ['fecha', 'hora_inicio', 'hora_fin', 'updated_at'])) {
            $this->agregarError('No fue posible reprogramar la cita.');
            return false;
        }

        $this->registrarAuditoria(
            'CITA_REPROGRAMADA',
            $cita,
            ['fecha' => $nuevaFecha, 'hora_inicio' => $nuevaHoraInicio, 'hora_fin' => $nuevaHoraFin]
        );
        return true;
    }

    /**
     * Retorna todas las citas de un día específico ordenadas por hora.
     *
     * @param string $fecha Formato Y-m-d.
     * @return Cita[]
     */
    public function getCitasDelDia(string $fecha): array
    {
        return Cita::find()
            ->with(['cliente', 'vehiculo', 'servicios'])
            ->where(['fecha' => $fecha])
            ->andWhere(['not', ['estado' => ['cancelada', 'no_show']]])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();
    }

    /**
     * Retorna citas de un día con sus estados para mostrar en el calendario.
     * Incluye citas canceladas y no_show para estadísticas completas.
     *
     * @param string $fecha Formato Y-m-d.
     * @return Cita[]
     */
    public function getCitasDelDiaConEstados(string $fecha): array
    {
        return Cita::find()
            ->with(['cliente', 'vehiculo', 'servicios'])
            ->where(['fecha' => $fecha])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();
    }

    /**
     * Cuenta citas activas (pendiente + confirmada).
     */
    public function countCitasActivas(): int
    {
        return (int) Cita::find()
            ->where(['in', 'estado', ['pendiente', 'confirmada', 'en_progreso']])
            ->count();
    }

    /**
     * Cuenta citas activas para una fecha puntual.
     */
    public function countCitasActivasPorFecha(string $fecha): int
    {
        return (int) Cita::find()
            ->where(['fecha' => $fecha])
            ->andWhere(['in', 'estado', ['pendiente', 'confirmada', 'en_progreso']])
            ->count();
    }

    /**
     * Retorna eventos de calendario por mes en formato consumible por JS.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEventosCalendario(string $mes): array
    {
        $inicio = $mes . '-01';
        $fin = date('Y-m-t', strtotime($inicio));

        $rows = Cita::find()
            ->select(['id', 'fecha', 'hora_inicio', 'hora_fin', 'estado'])
            ->where(['between', 'fecha', $inicio, $fin])
            ->andWhere(['not', ['estado' => ['cancelada', 'no_show']]])
            ->orderBy(['fecha' => SORT_ASC, 'hora_inicio' => SORT_ASC])
            ->asArray()
            ->all();

        $eventos = [];
        foreach ($rows as $row) {
            $eventos[] = [
                'id' => (int) $row['id'],
                'title' => substr((string) $row['hora_inicio'], 0, 5) . ' - ' . Cita::getEstadosList()[$row['estado']],
                'start' => $row['fecha'] . 'T' . $row['hora_inicio'],
                'end' => $row['fecha'] . 'T' . $row['hora_fin'],
                'estado' => $row['estado'],
            ];
        }

        return $eventos;
    }

    /**
     * Capacidad de citas activas para un día.
     */
    public function getCapacidadDia(string $fecha): int
    {
        return $this->countCitasActivasPorFecha($fecha);
    }

    /**
     * Retorna estadísticas mensuales para gráficos y reportes.
     *
     * @return array<string, mixed>
     */
    public function getEstadisticasMensuales(string $mes): array
    {
        $inicio = $mes . '-01';
        $diasEnMes = (int) date('t', strtotime($inicio));
        $fin = date('Y-m-t', strtotime($inicio));

        $rows = Cita::find()
            ->select([
                'dia' => 'DAY(fecha)',
                'estado',
                'total' => 'COUNT(*)',
            ])
            ->where(['between', 'fecha', $inicio, $fin])
            ->groupBy(['DAY(fecha)', 'estado'])
            ->asArray()
            ->all();

        $labels = [];
        $series = [
            'totales' => [],
            'confirmadas' => [],
            'canceladas' => [],
            'no_show' => [],
            'pendientes' => [],
            'en_progreso' => [],
            'completadas' => [],
        ];

        for ($d = 1; $d <= $diasEnMes; $d++) {
            $labels[] = str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            foreach ($series as $k => $_) {
                $series[$k][$d] = 0;
            }
        }

        foreach ($rows as $row) {
            $dia = (int) ($row['dia'] ?? 0);
            if ($dia < 1 || $dia > $diasEnMes) {
                continue;
            }
            $estado = (string) ($row['estado'] ?? '');
            $total = (int) ($row['total'] ?? 0);

            // Solo contar citas activas para totales (coherente con el calendario)
            if (!in_array($estado, ['cancelada', 'no_show'], true)) {
                $series['totales'][$dia] += $total;
            }

            if ($estado === 'confirmada') {
                $series['confirmadas'][$dia] += $total;
            }
            if ($estado === 'cancelada') {
                $series['canceladas'][$dia] += $total;
            }
            if ($estado === 'no_show') {
                $series['no_show'][$dia] += $total;
            }
            if ($estado === 'pendiente') {
                $series['pendientes'][$dia] += $total;
            }
            if ($estado === 'en_progreso') {
                $series['en_progreso'][$dia] += $total;
            }
            if ($estado === 'completada') {
                $series['completadas'][$dia] += $total;
            }
        }

        $resumen = [
            'totales' => array_sum($series['totales']),
            'confirmadas' => array_sum($series['confirmadas']),
            'canceladas' => array_sum($series['canceladas']),
            'no_show' => array_sum($series['no_show']),
            'pendientes' => array_sum($series['pendientes']),
            'en_progreso' => array_sum($series['en_progreso']),
            'completadas' => array_sum($series['completadas']),
        ];

        foreach ($series as $key => $values) {
            $series[$key] = array_values($values);
        }

        return [
            'mes' => $mes,
            'labels' => $labels,
            'series' => $series,
            'resumen' => $resumen,
        ];
    }

    // ── Helpers privados ─────────────────────────────────────────────────────

    private function cambiarEstado(Cita $cita, string $nuevoEstado): bool
    {
        if (!$cita->puedeTransicionarA($nuevoEstado)) {
            $this->agregarError("No se puede cambiar de '{$cita->estado}' a '{$nuevoEstado}'.");
            return false;
        }

        $estadoAnterior = $cita->estado;
        $cita->estado = $nuevoEstado;
        if (!$cita->save(false, ['estado', 'updated_at'])) {
            $this->agregarError("Error al cambiar estado de la cita a '{$nuevoEstado}'.");
            return false;
        }

        $this->registrarAuditoria('CITA_CAMBIO_ESTADO', $cita, [
            'anterior' => $estadoAnterior,
            'nuevo' => $nuevoEstado,
        ]);

        $this->log("Cita #{$cita->id} → estado: {$nuevoEstado}");
        return true;
    }

    private function sincronizarServicios(Cita $cita, array $servicioIds): void
    {
        CitaServicio::deleteAll(['cita_id' => $cita->id]);
        foreach (array_unique($servicioIds) as $sid) {
            $cs              = new CitaServicio();
            $cs->cita_id     = $cita->id;
            $cs->servicio_id = (int) $sid;
            $cs->save(false);
        }
    }

    private function findCita(int $id): ?Cita
    {
        $cita = Cita::findOne($id);
        if ($cita === null) {
            $this->agregarError('Cita no encontrada.');
        }
        return $cita;
    }

    private function enviarEmailConfirmacion(Cita $cita, bool $esConfirmacion): void
    {
        $cliente = $cita->cliente;
        $email = $cliente?->email;
        if ($email === null || $email === '') {
            return;
        }

        $asunto = $esConfirmacion ? 'Tu cita fue confirmada' : 'Tu cita fue registrada';
        $mensaje = "Hola {$cliente->nombre}, tu cita quedó "
            . ($esConfirmacion ? 'confirmada' : 'registrada')
            . " para el {$cita->fecha} de {$cita->hora_inicio} a {$cita->hora_fin}.";

        try {
            $enviado = Yii::$app->mailer->compose()
                ->setTo($email)
                ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                ->setSubject($asunto)
                ->setTextBody($mensaje)
                ->send();

            if ($enviado) {
                $this->registrarAuditoria('CITA_EMAIL_ENVIADO', $cita, [
                    'to' => $email,
                    'subject' => $asunto,
                    'tipo' => $esConfirmacion ? 'confirmacion' : 'creacion',
                ]);
            }
        } catch (\Throwable $e) {
            Yii::warning('Error enviando email de cita: ' . $e->getMessage(), $this->logCategoria);
        }
    }

    private function registrarAuditoria(string $accion, Cita $cita, array $datos): void
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%audit_log}}', [
                'tabla' => 'cita',
                'registro_id' => $cita->id,
                'accion' => $accion,
                'usuario_id' => Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id,
                'ip' => Yii::$app->request->userIP,
                'cambios' => Json::encode($datos, JSON_UNESCAPED_UNICODE),
                'created_at' => time(),
            ])->execute();
        } catch (\Throwable $e) {
            Yii::warning('No fue posible registrar auditoría de cita: ' . $e->getMessage(), $this->logCategoria);
        }
    }

    /**
     * HU-018: Calcula los horarios disponibles para un día dado.
     * Considera la duración del servicio y los horarios ya ocupados.
     * 
     * @param string $fecha Fecha en formato Y-m-d
     * @param int $duracionMinutos Duración del servicio en minutos
     * @param string $horaInicioTaller Hora de apertura del taller (HH:MM)
     * @param string $horaFinTaller Hora de cierre del taller (HH:MM)
     * @return array Lista de horarios disponibles ['inicio' => 'HH:MM', 'fin' => 'HH:MM']
     */
    public function calcularHorasDisponibles(
        string $fecha,
        int $duracionMinutos = 60,
        string $horaInicioTaller = '08:00',
        string $horaFinTaller = '18:00'
    ): array {
        // Obtener citas activas del día ordenadas por hora de inicio
        $citasDia = Cita::find()
            ->select(['hora_inicio', 'hora_fin'])
            ->where(['fecha' => $fecha])
            ->andWhere(['in', 'estado', ['pendiente', 'confirmada', 'en_progreso']])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->asArray()
            ->all();
        
        $horariosOcupados = [];
        foreach ($citasDia as $cita) {
            $horariosOcupados[] = [
                'inicio' => $cita['hora_inicio'],
                'fin' => $cita['hora_fin'],
            ];
        }
        
        // Convertir horas a minutos para facilitar cálculos
        $inicioTallerMin = $this->horaAMinutos($horaInicioTaller);
        $finTallerMin = $this->horaAMinutos($horaFinTaller);
        
        // Convertir horarios ocupados a minutos
        $ocupadosEnMinutos = [];
        foreach ($horariosOcupados as $horario) {
            $ocupadosEnMinutos[] = [
                'inicio' => $this->horaAMinutos($horario['inicio']),
                'fin' => $this->horaAMinutos($horario['fin']),
            ];
        }
        
        // Ordenar horarios ocupados por hora de inicio
        usort($ocupadosEnMinutos, fn($a, $b) => $a['inicio'] <=> $b['inicio']);
        
        $horariosDisponibles = [];
        $tiempoActual = $inicioTallerMin;
        
        // Iterar por los horarios ocupados y encontrar huecos disponibles
        foreach ($ocupadosEnMinutos as $ocupado) {
            // Si hay espacio antes del próximo horario ocupado
            if ($tiempoActual + $duracionMinutos <= $ocupado['inicio']) {
                $horariosDisponibles[] = [
                    'inicio' => $this->minutosAHora($tiempoActual),
                    'fin' => $this->minutosAHora($tiempoActual + $duracionMinutos),
                ];
            }
            
            // Avanzar el tiempo actual al fin del horario ocupado
            $tiempoActual = max($tiempoActual, $ocupado['fin']);
        }
        
        // Verificar si hay espacio después del último horario ocupado
        while ($tiempoActual + $duracionMinutos <= $finTallerMin) {
            $horariosDisponibles[] = [
                'inicio' => $this->minutosAHora($tiempoActual),
                'fin' => $this->minutosAHora($tiempoActual + $duracionMinutos),
            ];
            $tiempoActual += $duracionMinutos;
        }
        
        return $horariosDisponibles;
    }
    
    /**
     * Convierte una hora en formato HH:MM a minutos desde medianoche.
     */
    private function horaAMinutos(string $hora): int
    {
        [$horas, $minutos] = explode(':', $hora);
        return ((int) $horas) * 60 + ((int) $minutos);
    }
    
    /**
     * Convierte minutos desde medianoche a formato HH:MM.
     */
    private function minutosAHora(int $minutos): string
    {
        $horas = intdiv($minutos, 60);
        $mins = $minutos % 60;
        return sprintf('%02d:%02d', $horas, $mins);
    }
}
