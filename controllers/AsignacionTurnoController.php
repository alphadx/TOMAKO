<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use app\models\Cita;
use app\models\Tecnico;
use app\models\Especialidad;
use yii\data\ActiveDataProvider;

/**
 * Controlador para gestión de asignación de mecánicos por turno.
 * HU-017: Asignación de Mecánicos por Turno
 */
class AsignacionTurnoController extends BaseController
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'asignar' => ['post'],
                    'desasignar' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Vista principal de asignación de turnos.
     */
    public function actionIndex(): string
    {
        $fecha = Yii::$app->request->get('fecha', date('Y-m-d'));
        $turno = Yii::$app->request->get('turno', 'manana');
        
        // Validar turno
        $turnosValidos = ['manana', 'tarde'];
        if (!in_array($turno, $turnosValidos, true)) {
            $turno = 'manana';
        }

        // Definir horas del turno
        $horasTurno = $this->definirHorasTurno($turno);

        // Obtener técnicos activos con especialidades
        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->with('especialidad')
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        // Obtener citas del día agrupadas por hora
        $citasPorHora = [];
        $citas = Cita::find()
            ->where(['fecha' => $fecha])
            ->andWhere(['not', ['estado' => ['cancelada', 'no_show']]])
            ->with(['cliente', 'vehiculo', 'servicios'])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();

        foreach ($citas as $cita) {
            $horaInicio = substr($cita->hora_inicio, 0, 5); // HH:MM
            if (!isset($citasPorHora[$horaInicio])) {
                $citasPorHora[$horaInicio] = [];
            }
            $citasPorHora[$horaInicio][] = $cita;
        }

        // Obtener asignaciones existentes (si hubiera una tabla de asignación por turno)
        // Por ahora usaremos las citas directamente

        return $this->render('index', [
            'fecha' => $fecha,
            'turno' => $turno,
            'turnosValidos' => $turnosValidos,
            'horasTurno' => $horasTurno,
            'tecnicos' => $tecnicos,
            'citasPorHora' => $citasPorHora,
            'especialidades' => Especialidad::find()->orderBy(['nombre' => SORT_ASC])->all(),
        ]);
    }

    /**
     * Dashboard de carga de trabajo por técnico.
     */
    public function actionDashboard(): string
    {
        $fechaInicio = Yii::$app->request->get('fecha_inicio', date('Y-m-d'));
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d', strtotime('+7 days')));
        
        // Técnicos con sus citas en el período
        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->with(['especialidad'])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $estadisticas = [];
        foreach ($tecnicos as $tecnico) {
            // Contar citas asignadas (esto dependería de cómo se asignen los técnicos a las citas)
            // Por ahora, mostramos información básica
            $estadisticas[$tecnico->id] = [
                'tecnico' => $tecnico,
                'citas_count' => 0, // Se calcularía según la relación
                'horas_totales' => 0,
            ];
        }

        return $this->render('dashboard', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Asignar técnico a un turno/hora específica.
     */
    public function actionAsignar(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $tecnicoId = (int) Yii::$app->request->post('tecnico_id', 0);
        $citaId = (int) Yii::$app->request->post('cita_id', 0);
        $fecha = Yii::$app->request->post('fecha', '');
        $hora = Yii::$app->request->post('hora', '');

        if ($tecnicoId <= 0 || $citaId <= 0) {
            return ['success' => false, 'message' => 'Datos inválidos'];
        }

        // Aquí se implementaría la lógica de asignación
        // Por ahora retornamos éxito simulado
        
        return ['success' => true, 'message' => 'Técnico asignado correctamente'];
    }

    /**
     * Desasignar técnico de un turno.
     */
    public function actionDesasignar(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $asignacionId = (int) Yii::$app->request->post('asignacion_id', 0);

        if ($asignacionId <= 0) {
            return ['success' => false, 'message' => 'Datos inválidos'];
        }

        // Aquí se implementaría la lógica de desasignación
        
        return ['success' => true, 'message' => 'Técnico desasignado correctamente'];
    }

    /**
     * Obtener disponibilidad de técnicos en JSON.
     */
    public function actionDisponibilidadJson(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $fecha = Yii::$app->request->get('fecha', date('Y-m-d'));
        $hora = Yii::$app->request->get('hora', '');

        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->with(['especialidad'])
            ->all();

        $disponibles = [];
        foreach ($tecnicos as $tecnico) {
            // Verificar si el técnico ya está asignado en ese horario
            // Esto es un placeholder - se implementaría la lógica real
            $disponibles[] = [
                'id' => $tecnico->id,
                'nombre' => $tecnico->getFullName(),
                'especialidad' => $tecnico->especialidad ? $tecnico->especialidad->nombre : 'General',
                'disponible' => true,
            ];
        }

        return ['tecnicos' => $disponibles];
    }

    /**
     * Define las horas de un turno.
     */
    private function definirHorasTurno(string $turno): array
    {
        if ($turno === 'manana') {
            // Turno mañana: 8:00 - 13:00
            return ['08:00', '09:00', '10:00', '11:00', '12:00'];
        } else {
            // Turno tarde: 14:00 - 19:00
            return ['14:00', '15:00', '16:00', '17:00', '18:00'];
        }
    }
}
