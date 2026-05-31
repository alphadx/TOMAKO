<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\Notificacion;
use app\models\PlantillaNotificacion;
use app\models\EmailLog;
use app\models\PreferenciaNotificacion;
use app\models\Cita;

class NotificacionService extends BaseService
{
    protected string $logCategoria = 'app.notificacion';

    public function crearNotificacion(int $usuarioId, string $tipo, string $titulo, string $mensaje, ?string $url = null): ?Notificacion
    {
        $pref = PreferenciaNotificacion::findOrCreate($usuarioId);
        if (!$this->preferenciaPermiteInterno($pref, $tipo)) {
            return null;
        }

        return $this->executeInTransaction(function () use ($usuarioId, $tipo, $titulo, $mensaje, $url): Notificacion {
            $n = new Notificacion([
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url' => $url,
                'leida' => 0,
            ]);

            if (!$n->validate()) {
                throw new ServiceException(implode('; ', $n->getFirstErrors()));
            }

            $this->asegurar($n->save(false), 'No se pudo crear la notificacion.');
            $this->invalidarBadge($usuarioId);

            return $n;
        });
    }

    public function marcarLeida(int $notificacionId, int $usuarioId): void
    {
        $n = Notificacion::findOne(['id' => $notificacionId, 'usuario_id' => $usuarioId]);
        if ($n === null || (int) $n->leida === 1) {
            return;
        }

        $n->leida = 1;
        $n->leida_at = time();
        $n->save(false, ['leida', 'leida_at', 'updated_at']);
        $this->invalidarBadge($usuarioId);
    }

    public function marcarTodasLeidas(int $usuarioId): void
    {
        Notificacion::updateAll([
            'leida' => 1,
            'leida_at' => time(),
            'updated_at' => time(),
        ], [
            'usuario_id' => $usuarioId,
            'leida' => 0,
        ]);

        $this->invalidarBadge($usuarioId);
    }

    public function getCountNoLeidas(int $usuarioId): int
    {
        $key = 'notif_badge_' . $usuarioId;

        return (int) Yii::$app->cache->getOrSet($key, static function () use ($usuarioId): int {
            return (int) Notificacion::find()->where(['usuario_id' => $usuarioId, 'leida' => 0])->count();
        }, 60);
    }

    /**
     * @return Notificacion[]
     */
    public function getNoLeidas(int $usuarioId, int $limit = 10): array
    {
        return Notificacion::find()
            ->where(['usuario_id' => $usuarioId, 'leida' => 0])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * @param array<string, string> $variables
     */
    public function enviarEmail(string $destinatario, string $plantillaCodigo, array $variables, ?int $usuarioId = null): bool
    {
        if ($usuarioId !== null) {
            $pref = PreferenciaNotificacion::findOrCreate($usuarioId);
            if (!$this->preferenciaPermiteEmail($pref, $plantillaCodigo)) {
                return false;
            }
        }

        $asunto = '';
        $cuerpo = '';
        $error = null;
        $exito = false;

        try {
            $plantilla = PlantillaNotificacion::findActivaOFail($plantillaCodigo);
            $render = $plantilla->render($variables);
            $asunto = $render['asunto'];
            $cuerpo = $render['cuerpo'];

            $exito = Yii::$app->mailer->compose()
                ->setTo($destinatario)
                ->setFrom([Yii::$app->params['adminEmail'] ?? 'no-reply@tallersmart.local' => 'TOMAKO'])
                ->setSubject($asunto)
                ->setHtmlBody($cuerpo)
                ->send();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Yii::error('Error envio email: ' . $error, 'app.notificacion.email');
        }

        $this->registrarEmailLog($destinatario, $asunto, $cuerpo, $exito, $plantillaCodigo, $error);

        return $exito;
    }

    public function registrarEmailLog(
        string $destinatario,
        string $asunto,
        string $cuerpo,
        bool $exito,
        ?string $plantilla = null,
        ?string $error = null
    ): void {
        $log = new EmailLog([
            'destinatario' => $destinatario,
            'asunto' => $asunto,
            'cuerpo_html' => $cuerpo,
            'plantilla' => $plantilla,
            'exito' => $exito ? 1 : 0,
            'error' => $error,
            'enviado_at' => time(),
        ]);
        $log->save(false);
    }

    public function procesarRecordatoriosCita(): int
    {
        $fechaObjetivo = date('Y-m-d', strtotime('+1 day'));

        $citas = Cita::find()
            ->with('cliente')
            ->where(['fecha' => $fechaObjetivo, 'estado' => 'confirmada'])
            ->all();

        $enviadas = 0;
        foreach ($citas as $cita) {
            $email = trim((string) ($cita->cliente->email ?? ''));
            if ($email === '') {
                continue;
            }

            $ok = $this->enviarEmail($email, 'cita.recordatorio_24h', [
                'cliente_nombre' => (string) ($cita->cliente->nombre ?? ''),
                'fecha' => $cita->fecha,
                'hora' => $cita->hora_inicio,
            ]);

            if ($ok) {
                $enviadas++;
            }
        }

        return $enviadas;
    }

    /**
     * Genera recordatorios de mantenciones periódicas pendientes (HU-007)
     * Verifica vehículos con mantenciones vencidas o próximas a vencer
     */
    public function generarRecordatoriosMantencion(): int
    {
        $vehiculos = Vehiculo::find()
            ->with('cliente')
            ->where(['status' => 1])
            ->all();

        $recordatoriosEnviados = 0;
        $now = time();
        $diasUmbral = 30; // Días antes del vencimiento para notificar
        $segundosPorDia = 86400;

        foreach ($vehiculos as $vehiculo) {
            // Calcular días desde la última mantención
            $ultimaMantencion = $vehiculo->ultima_mantencion_at ?? 0;
            
            if ($ultimaMantencion > 0) {
                $diasDesdeMantencion = (int) (($now - $ultimaMantencion) / $segundosPorDia);
                $diasParaProxima = 90 - $diasDesdeMantencion; // Mantención cada 90 días

                // Si está próxima a vencer (menos de 30 días) o ya vencida
                if ($diasParaProxima <= $diasUmbral) {
                    $usuarioId = (int) ($vehiculo->cliente->id ?? 0);
                    if ($usuarioId <= 0) {
                        continue;
                    }

                    $titulo = $diasParaProxima < 0 
                        ? 'Mantención Vencida' 
                        : 'Mantención Próxima a Vencer';
                    
                    $mensaje = $diasParaProxima < 0
                        ? "El vehículo {$vehiculo->marca_modelo} (patente {$vehiculo->patente}) tiene una mantención vencida hace " . abs($diasParaProxima) . " días."
                        : "El vehículo {$vehiculo->marca_modelo} (patente {$vehiculo->patente}) necesita mantención en {$diasParaProxima} días.";

                    $this->crearNotificacion(
                        $usuarioId,
                        Notificacion::TIPO_MANTENCION_PENDIENTE,
                        $titulo,
                        $mensaje,
                        '/vehiculo/view?id=' . $vehiculo->id
                    );

                    $recordatoriosEnviados++;
                }
            }
        }

        return $recordatoriosEnviados;
    }

    public function guardarPreferencias(int $usuarioId, PreferenciaNotificacion $pref): bool
    {
        $model = PreferenciaNotificacion::findOrCreate($usuarioId);
        $model->email_cita = (int) $pref->email_cita;
        $model->email_orden_estado = (int) $pref->email_orden_estado;
        $model->interno_stock = (int) $pref->interno_stock;
        $model->interno_orden = (int) $pref->interno_orden;

        return (bool) $model->save(false);
    }

    private function preferenciaPermiteInterno(PreferenciaNotificacion $pref, string $tipo): bool
    {
        return match ($tipo) {
            Notificacion::TIPO_STOCK_BAJO => (bool) $pref->interno_stock,
            Notificacion::TIPO_ORDEN_LISTA => (bool) $pref->interno_orden,
            default => true,
        };
    }

    private function preferenciaPermiteEmail(PreferenciaNotificacion $pref, string $codigo): bool
    {
        if (str_starts_with($codigo, 'cita.')) {
            return (bool) $pref->email_cita;
        }

        if (str_starts_with($codigo, 'orden.')) {
            return (bool) $pref->email_orden_estado;
        }

        return true;
    }

    private function invalidarBadge(int $usuarioId): void
    {
        Yii::$app->cache->delete('notif_badge_' . $usuarioId);
    }
}
