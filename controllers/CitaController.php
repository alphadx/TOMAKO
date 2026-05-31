<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\helpers\Json;
use app\models\Cita;
use app\models\Servicio;
use app\models\Cliente;
use app\models\Vehiculo;
use app\models\search\CitaSearch;
use app\components\services\CitaService;
use app\components\behaviors\AccessControlBehavior;

/**
 * CitaController: gestión de citas del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class CitaController extends BaseController
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
                'class'   => VerbFilter::class,
                'actions' => [
                    'confirmar'        => ['post'],
                    'cancelar'         => ['post'],
                    'no-show'          => ['post'],
                    'iniciar-servicio' => ['post'],
                    'reprogramar'      => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'cita',
                'actionMap' => [
                    'index' => 'ver',
                    'calendario' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'confirmar' => 'editar',
                    'cancelar' => 'editar',
                    'no-show' => 'editar',
                    'iniciar-servicio' => 'editar',
                    'reprogramar' => 'editar',
                    'disponibilidad' => 'ver',
                    'horarios-disponibles' => 'ver',
                    'vehiculos-por-cliente' => 'ver',
                ],
            ],
        ];
    }

    /** Listado de citas con filtros y contador de activas. */
    public function actionIndex(): string
    {
        $searchModel  = new CitaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $service      = new CitaService();
        $fechaHoy     = date('Y-m-d');

        return $this->render('index', [
            'searchModel'    => $searchModel,
            'dataProvider'   => $dataProvider,
            'citasActivas'   => $service->countCitasActivasPorFecha($fechaHoy),
            'fechaActiva'    => $fechaHoy,
        ]);
    }

    /** Vista de calendario mensual. */
    public function actionCalendario(): string
    {
        $mes  = (int) Yii::$app->request->get('mes',  (int) date('m'));
        $anio = (int) Yii::$app->request->get('anio', (int) date('Y'));
        $service = new CitaService();

        // Normalizar mes
        if ($mes < 1) { $mes = 12; $anio--; }
        if ($mes > 12) { $mes = 1;  $anio++; }

        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $diasEnMes   = (int) date('t', strtotime($fechaInicio));
        $fechaFin    = sprintf('%04d-%02d-%02d', $anio, $mes, $diasEnMes);

        // Contar citas por día (SOLO activas, coherente con estadísticas)
        $rows = Cita::find()
            ->select(['fecha', 'COUNT(*) AS total'])
            ->where(['between', 'fecha', $fechaInicio, $fechaFin])
            ->andWhere(['in', 'estado', ['pendiente', 'confirmada', 'en_progreso', 'completada']])
            ->groupBy('fecha')
            ->asArray()
            ->all();

        $citasPorDia = [];
        foreach ($rows as $row) {
            $citasPorDia[$row['fecha']] = (int) $row['total'];
        }

        $fechaSeleccionada = (string) Yii::$app->request->get('fecha', date('Y-m-d'));
        if (substr($fechaSeleccionada, 0, 7) !== sprintf('%04d-%02d', $anio, $mes)) {
            $fechaSeleccionada = $fechaInicio;
        }

        $citasDia = $service->getCitasDelDia($fechaSeleccionada);
        $citasActivasDia = $service->countCitasActivasPorFecha($fechaSeleccionada);

        // Obtener citas por día con estados para el calendario (TODOS los estados)
        // Necesitamos las citas individuales ordenadas por hora para mostrar los colores en orden cronológico
        $citasTodas = Cita::find()
            ->select(['fecha', 'estado'])
            ->where(['between', 'fecha', $fechaInicio, $fechaFin])
            ->orderBy(['fecha' => SORT_ASC, 'hora_inicio' => SORT_ASC])
            ->asArray()
            ->all();
        
        // Construir array de estados en orden cronológico por día
        $citasConEstadoPorDia = [];
        foreach ($citasTodas as $cita) {
            $fecha = $cita['fecha'];
            if (!isset($citasConEstadoPorDia[$fecha])) {
                $citasConEstadoPorDia[$fecha] = [];
            }
            $citasConEstadoPorDia[$fecha][] = $cita['estado'];
        }
        
        // También calcular conteos por estado para cada día (para badges)
        $conteosPorDia = [];
        foreach ($citasTodas as $cita) {
            $fecha = $cita['fecha'];
            $estado = $cita['estado'];
            if (!isset($conteosPorDia[$fecha])) {
                $conteosPorDia[$fecha] = [];
            }
            if (!isset($conteosPorDia[$fecha][$estado])) {
                $conteosPorDia[$fecha][$estado] = 0;
            }
            $conteosPorDia[$fecha][$estado]++;
        }

        return $this->render('calendario', [
            'mes'          => $mes,
            'anio'         => $anio,
            'diasEnMes'    => $diasEnMes,
            'citasPorDia'  => $citasPorDia,
            'citasConEstadoPorDia' => $conteosPorDia,  // Conteos para badges
            'citasOrdenadasPorDia' => $citasConEstadoPorDia,  // Estados en orden cronológico
            'primerDiaSemana' => (int) date('N', strtotime($fechaInicio)),
            'fechaSeleccionada' => $fechaSeleccionada,
            'citasDia' => $citasDia,
            'citasActivasDia' => $citasActivasDia,
            'mesIso' => sprintf('%04d-%02d', $anio, $mes),
            'service' => $service,
        ]);
    }

    /** Eventos del calendario en JSON para UI/AJAX. */
    public function actionEventos(string $mes): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $service = new CitaService();
        return $this->asJson($service->getEventosCalendario($mes));
    }

    /** Estadísticas mensuales en JSON para dashboard/gráfico de citas. */
    public function actionEstadisticas(string $mes): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $mesParts = explode('-', $mes);
        $anio = (int) ($mesParts[0] ?? date('Y'));
        $mesNum = (int) ($mesParts[1] ?? date('m'));
        
        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mesNum);
        $diasEnMes   = (int) date('t', strtotime($fechaInicio));
        $fechaFin    = sprintf('%04d-%02d-%02d', $anio, $mesNum, $diasEnMes);
        
        // Obtener TODAS las citas agrupadas por día y estado (incluyendo canceladas y no_show)
        $rows = Cita::find()
            ->select(['fecha', 'estado', 'COUNT(*) as total'])
            ->where(['between', 'fecha', $fechaInicio, $fechaFin])
            ->groupBy(['fecha', 'estado'])
            ->asArray()
            ->all();
        
        // Construir estructura por día
        $dias = [];
        for ($d = 1; $d <= $diasEnMes; $d++) {
            $fecha = sprintf('%04d-%02d-%02d', $anio, $mesNum, $d);
            $dias[$fecha] = [
                'label' => str_pad((string) $d, 2, '0', STR_PAD_LEFT),
                'pendiente' => 0,
                'confirmada' => 0,
                'en_progreso' => 0,
                'completada' => 0,
                'cancelada' => 0,
                'no_show' => 0,
            ];
        }
        
        foreach ($rows as $row) {
            $fecha = $row['fecha'];
            if (!isset($dias[$fecha])) {
                continue;
            }
            $estado = $row['estado'];
            if (isset($dias[$fecha][$estado])) {
                $dias[$fecha][$estado] += (int) $row['total'];
            }
        }
        
        // Calcular totales activos (coherente con calendario)
        $series = [
            'pendientes' => [],
            'confirmadas' => [],
            'en_progreso' => [],
            'completadas' => [],
            'canceladas' => [],
            'no_show' => [],
        ];
        $labels = [];
        $totalesActivos = [];
        
        foreach ($dias as $fecha => $data) {
            $labels[] = $data['label'];
            $series['pendientes'][] = $data['pendiente'];
            $series['confirmadas'][] = $data['confirmada'];
            $series['en_progreso'][] = $data['en_progreso'];
            $series['completadas'][] = $data['completada'];
            $series['canceladas'][] = $data['cancelada'];
            $series['no_show'][] = $data['no_show'];
            
            // Total activo = pendiente + confirmada + en_progreso + completada
            $totalesActivos[] = $data['pendiente'] + $data['confirmada'] + $data['en_progreso'] + $data['completada'];
        }
        
        $resumen = [
            'totales' => array_sum($totalesActivos),
            'confirmadas' => array_sum($series['confirmadas']),
            'canceladas' => array_sum($series['canceladas']),
            'no_show' => array_sum($series['no_show']),
            'pendientes' => array_sum($series['pendientes']),
            'en_progreso' => array_sum($series['en_progreso']),
            'completadas' => array_sum($series['completadas']),
        ];
        
        return $this->asJson([
            'mes' => $mes,
            'labels' => $labels,
            'series' => $series,
            'totales_activos' => $totalesActivos,
            'resumen' => $resumen,
        ]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $model    = new Cita();
        $service  = new CitaService();
        $clientes = Cliente::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $servicios = Servicio::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $data        = Yii::$app->request->post('Cita', []);
            $servicioIds = (array) Yii::$app->request->post('servicio_ids', []);

            $cita = $service->create($data, $servicioIds);
            if ($cita !== null) {
                Yii::$app->session->setFlash('success', 'Cita registrada exitosamente.');
                return $this->redirect(['view', 'id' => $cita->id]);
            }
            $model->setAttributes($data);
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('create', [
            'model'     => $model,
            'clientes'  => $clientes,
            'servicios' => $servicios,
        ]);
    }

    /** Formulario de edición (solo estado pendiente). */
    public function actionUpdate(int $id): Response|string
    {
        $model = $this->findModel($id);

        if ($model->estado !== 'pendiente') {
            Yii::$app->session->setFlash('error', 'Solo se pueden editar citas en estado pendiente.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $service   = new CitaService();
        $clientes  = Cliente::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $servicios = Servicio::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $data        = Yii::$app->request->post('Cita', []);
            $servicioIds = (array) Yii::$app->request->post('servicio_ids', []);

            $updated = $service->update($model, $data, $servicioIds);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Cita actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', [
            'model'     => $model,
            'clientes'  => $clientes,
            'servicios' => $servicios,
        ]);
    }

    /** Detalle de cita con línea de tiempo de estado. */
    public function actionView(int $id): string
    {
        $model = $this->findModel($id);
        $model->refresh();

        $auditoria = Yii::$app->db->createCommand(
            'SELECT accion, cambios, created_at FROM {{%audit_log}} WHERE tabla = :tabla AND registro_id = :id ORDER BY id DESC LIMIT 12',
            [':tabla' => 'cita', ':id' => $model->id]
        )->queryAll();

        foreach ($auditoria as &$item) {
            $item['cambios_decoded'] = Json::decode((string) ($item['cambios'] ?? '{}'));
        }

        return $this->render('view', [
            'model' => $model,
            'auditoria' => $auditoria,
        ]);
    }

    /** Confirma una cita (POST). */
    public function actionConfirmar(int $id): Response
    {
        $service = new CitaService();
        if ($service->confirmar($id)) {
            Yii::$app->session->setFlash('success', 'Cita confirmada.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Cancela una cita (POST). */
    public function actionCancelar(int $id): Response
    {
        $service = new CitaService();
        if ($service->cancelar($id)) {
            Yii::$app->session->setFlash('success', 'Cita cancelada.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Marca como no-show (POST). */
    public function actionNoShow(int $id): Response
    {
        $service = new CitaService();
        if ($service->marcarNoShow($id)) {
            Yii::$app->session->setFlash('success', 'Cita marcada como No Show.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Inicia el servicio: cambia estado y redirige a crear orden (POST). */
    public function actionIniciarServicio(int $id): Response
    {
        $service = new CitaService();
        if ($service->iniciarServicio($id)) {
            Yii::$app->session->setFlash('success', 'Servicio iniciado. Cree la orden de servicio.');
            return $this->redirect(['/orden/desde-cita', 'citaId' => $id]);
        }
        Yii::$app->session->setFlash('error', $service->getPrimerError());
        return $this->redirect(['view', 'id' => $id]);
    }

    /** Reprograma una cita (POST). */
    public function actionReprogramar(int $id): Response
    {
        $service = new CitaService();
        $fecha = (string) Yii::$app->request->post('fecha', '');
        $horaInicio = (string) Yii::$app->request->post('hora_inicio', '');
        $horaFin = (string) Yii::$app->request->post('hora_fin', '');

        if ($service->reprogramar($id, $fecha, $horaInicio, $horaFin)) {
            Yii::$app->session->setFlash('success', 'Cita reprogramada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Retorna vehículos de un cliente en formato JSON (AJAX). */
    public function actionVehiculosPorCliente(int $clienteId): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $vehiculos = Vehiculo::find()
            ->where(['cliente_id' => $clienteId, 'status' => 1])
            ->orderBy(['patente' => SORT_ASC])
            ->all();

        return $this->asJson(array_map(
            fn($v) => ['id' => $v->id, 'text' => "{$v->patente} – {$v->marca} {$v->modelo}"],
            $vehiculos
        ));
    }

    /**
     * HU-018: Verifica disponibilidad de horarios para un tipo de servicio.
     * Retorna los horarios bloqueados según la duración del servicio.
     */
    public function actionDisponibilidad(string $fecha, int $servicioId): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $servicio = Servicio::findOne($servicioId);
        if ($servicio === null) {
            return $this->asJson(['error' => 'Servicio no encontrado']);
        }
        
        $service = new CitaService();
        $duracion = $servicio->duracion_estimada ?? 60; // minutos por defecto
        
        // Obtener citas del día para calcular horarios ocupados
        $citasDia = Cita::find()
            ->where(['fecha' => $fecha])
            ->andWhere(['in', 'estado', ['pendiente', 'confirmada', 'en_progreso']])
            ->orderBy(['hora_inicio' => SORT_ASC])
            ->all();
        
        $horariosOcupados = [];
        foreach ($citasDia as $cita) {
            $horariosOcupados[] = [
                'inicio' => $cita->hora_inicio,
                'fin' => $cita->hora_fin,
                'estado' => $cita->estado,
            ];
        }
        
        // Horario de atención del taller (configurable)
        $horaInicioTaller = '08:00';
        $horaFinTaller = '18:00';
        
        return $this->asJson([
            'fecha' => $fecha,
            'servicio' => [
                'id' => $servicio->id,
                'nombre' => $servicio->nombre,
                'duracion_estimada' => $duracion,
            ],
            'horario_taller' => [
                'inicio' => $horaInicioTaller,
                'fin' => $horaFinTaller,
            ],
            'horarios_ocupados' => $horariosOcupados,
            'horas_disponibles' => $service->calcularHorasDisponibles($fecha, $duracion, $horaInicioTaller, $horaFinTaller),
        ]);
    }

    /**
     * HU-018: Retorna horarios disponibles para agendar un servicio.
     * Considera la duración del servicio y los horarios ya ocupados.
     */
    public function actionHorariosDisponibles(string $fecha, int $servicioId): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $servicio = Servicio::findOne($servicioId);
        if ($servicio === null) {
            return $this->asJson(['error' => 'Servicio no encontrado']);
        }
        
        $service = new CitaService();
        $duracion = $servicio->duracion_estimada ?? 60; // minutos
        
        // Horario de atención del taller
        $horaInicioTaller = '08:00';
        $horaFinTaller = '18:00';
        
        $horariosDisponibles = $service->calcularHorasDisponibles($fecha, $duracion, $horaInicioTaller, $horaFinTaller);
        
        return $this->asJson([
            'fecha' => $fecha,
            'servicio_id' => $servicioId,
            'duracion_requerida' => $duracion,
            'horarios_disponibles' => $horariosDisponibles,
            'total_disponibles' => count($horariosDisponibles),
        ]);
    }

    private function findModel(int $id): Cita
    {
        $model = Cita::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Cita no encontrada.');
        }
        return $model;
    }
}
