<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use app\components\services\NotificacionService;
use app\models\Cita;
use app\models\PlantillaNotificacion;

/**
 * Controlador para gestión de recordatorios automáticos de citas.
 * HU-019: Recordatorios Automáticos de Citas
 */
class RecordatorioCitaController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /**
     * Dashboard de recordatorios programados.
     */
    public function actionIndex(): string
    {
        $service = new NotificacionService();
        
        // Citas confirmadas para mañana (recordatorio 24h)
        $citasManana = Cita::find()
            ->where(['fecha' => date('Y-m-d', strtotime('+1 day')), 'estado' => 'confirmada'])
            ->with(['cliente', 'vehiculo'])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();

        // Citas confirmadas para hoy
        $citasHoy = Cita::find()
            ->where(['fecha' => date('Y-m-d'), 'estado' => 'confirmada'])
            ->with(['cliente', 'vehiculo'])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();

        // Estadísticas
        $totalRecordatoriosEnviados = 0;
        $totalPendientes = count($citasManana);

        // Verificar si existe la plantilla
        $plantillaExiste = PlantillaNotificacion::find()
            ->where(['codigo' => 'cita.recordatorio_24h', 'activo' => 1])
            ->exists();

        return $this->render('index', [
            'citasManana' => $citasManana,
            'citasHoy' => $citasHoy,
            'totalRecordatoriosEnviados' => $totalRecordatoriosEnviados,
            'totalPendientes' => $totalPendientes,
            'plantillaExiste' => $plantillaExiste,
        ]);
    }

    /**
     * Enviar recordatorios manualmente para citas de mañana.
     */
    public function actionEnviarRecordatorios(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $service = new NotificacionService();
        $enviados = $service->procesarRecordatoriosCita();

        return [
            'success' => true,
            'message' => "Se enviaron {$enviados} recordatorio(s) exitosamente.",
            'enviados' => $enviados,
        ];
    }

    /**
     * Vista de configuración de recordatorios.
     */
    public function actionConfiguracion(): string
    {
        // Obtener plantillas relacionadas con citas
        $plantillas = PlantillaNotificacion::find()
            ->where(['like', 'codigo', 'cita.'])
            ->orderBy(['codigo' => SORT_ASC])
            ->all();

        return $this->render('configuracion', [
            'plantillas' => $plantillas,
        ]);
    }

    /**
     * Probar envío de recordatorio.
     */
    public function actionProbarRecordatorio(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $email = Yii::$app->request->post('email', '');
        
        if (empty($email)) {
            return ['success' => false, 'message' => 'Email requerido'];
        }

        $service = new NotificacionService();
        $exito = $service->enviarEmail($email, 'cita.recordatorio_24h', [
            'cliente_nombre' => 'Cliente de Prueba',
            'fecha' => date('Y-m-d', strtotime('+1 day')),
            'hora' => '10:00',
        ]);

        return [
            'success' => $exito,
            'message' => $exito ? 'Email de prueba enviado correctamente.' : 'No se pudo enviar el email de prueba.',
        ];
    }

    /**
     * Historial de recordatorios enviados.
     */
    public function actionHistorial(): string
    {
        // Esto requeriría un modelo de historial de recordatorios
        // Por ahora mostramos un placeholder
        
        return $this->render('historial', [
            'recordatorios' => [],
        ]);
    }
}
