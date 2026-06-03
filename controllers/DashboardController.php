<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\services\DashboardService;
use app\models\DashboardPreference;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Response;
use yii\web\BadRequestHttpException;

class DashboardController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::class,
                'actions' => [
                    'guardar-preferencias' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $this->ensureDashboardAccess();

        $service = new DashboardService();
        $userId = Yii::$app->user->id;

        // Obtener preferencias del usuario
        $preferencias = DashboardPreference::getUserPreferences($userId);
        $widgetsDisponibles = DashboardPreference::getAvailableWidgets();

        return $this->render('index', [
            'kpis' => $service->getKpis(),
            'citasHoy' => $service->getCitasHoy(),
            'ordenesPrioritarias' => $service->getOrdenesPrioritarias(),
            'alertasStock' => $service->getAlertasStock(),
            'ordenesActivas' => $service->getOrdenesActivas(),
            'accesosRapidos' => $service->getAccesosRapidos(),
            'preferencias' => $preferencias,
            'widgetsDisponibles' => $widgetsDisponibles,
        ]);
    }

    public function actionConfigurar(): string
    {
        $this->ensureDashboardAccess();

        $userId = Yii::$app->user->id;
        $preferencias = DashboardPreference::getUserPreferences($userId);
        $widgetsDisponibles = DashboardPreference::getAvailableWidgets();

        return $this->render('configurar', [
            'preferencias' => $preferencias,
            'widgetsDisponibles' => $widgetsDisponibles,
        ]);
    }

    public function actionGuardarPreferencias()
    {
        $this->ensureDashboardAccess();
        
        // Obtenemos los IDs de los widgets marcados (array simple: ['widget-1', 'widget-3', ...])
        $widgetsMarcados = Yii::$app->request->post('widgets', []);
        
        $userId = Yii::$app->user->id;

        // ESTRATEGIA SIMPLE: Borrar todas las preferencias actuales y crear solo las marcadas
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // 1. Borrar todas las preferencias del usuario
            DashboardPreference::deleteAll(['user_id' => $userId]);

            // 2. Insertar solo los que vienen marcados usando batchInsert
            if (!empty($widgetsMarcados)) {
                $rows = [];
                foreach ($widgetsMarcados as $widgetId) {
                    $rows[] = [
                        'user_id' => $userId,
                        'widget_id' => $widgetId,
                        'is_visible' => 1,
                        'sort_order' => 0,
                        'is_collapsed' => 0,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                }
                Yii::$app->db->createCommand()->batchInsert(
                    DashboardPreference::tableName(),
                    ['user_id', 'widget_id', 'is_visible', 'sort_order', 'is_collapsed', 'created_at', 'updated_at'],
                    $rows
                )->execute();
                
                Yii::$app->session->setFlash('success', 'Configuración guardada correctamente.');
            } else {
                // Si no hay widgets marcados, dejamos el dashboard vacío (por defecto)
                Yii::$app->session->setFlash('info', 'Se ha restablecido la configuración predeterminada (sin widgets).');
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Error al guardar: ' . $e->getMessage());
        }

        return $this->redirect(['dashboard/configurar']);
    }

    public function actionRefreshKpi(string $kpi = 'all'): Response
    {
        $this->ensureDashboardAccess();
        Yii::$app->response->format = Response::FORMAT_JSON;

        $service = new DashboardService();

        if ($kpi === 'all') {
            return $this->asJson([
                'success' => true,
                'data' => $service->getKpis(),
            ]);
        }

        $allowed = [
            'servicios_activos',
            'citas_hoy',
            'stock_bajo',
            'ingresos_mes',
            'trabajos_listos',
            'clientes_nuevos',
            'valor_inventario',
            // Primeros 5 indicadores
            'tasa_entrega_tiempo',
            'tiempo_promedio_resolucion',
            'tasa_cancelacion',
            'rotacion_inventario',
            'tasa_no_show',
            // Nuevos 5 indicadores (6-10)
            'ingreso_promedio_orden',
            'distribucion_prioridad',
            'tasa_retencion_clientes',
            'productividad_tecnico',
            'tasa_quiebre_stock',
            // Terceros 5 indicadores (11-15)
            'frecuencia_servicio_vehiculo',
            'margen_bruto_servicio',
            'ingresos_por_metodo_pago',
            'tasa_morosidad',
            'ocupacion_agenda',
        ];

        if (!in_array($kpi, $allowed, true)) {
            return $this->asJson([
                'success' => false,
                'message' => 'KPI no soportado.',
            ]);
        }

        return $this->asJson([
            'success' => true,
            'data' => [
                $kpi => $service->refreshKpi($kpi),
            ],
        ]);
    }

    private function ensureDashboardAccess(): void
    {
        $identity = Yii::$app->user->identity;
        if ($identity === null) {
            return;
        }

        if ((int) $identity->rol_id === 1) {
            return;
        }

        $query = (new \yii\db\Query())
            ->from('{{%rol_permiso}} rp')
            ->innerJoin('{{%permiso}} p', 'p.id = rp.permiso_id')
            ->where(['rp.rol_id' => (int) $identity->rol_id]);

        $hasAnyPermission = (clone $query)->exists();
        if (!$hasAnyPermission) {
            return;
        }

        $hasPermission = (clone $query)
            ->andWhere(['in', 'p.nombre', ['dashboard.view', 'dashboard.ver']])
            ->exists();

        if (!$hasPermission) {
            throw new \yii\web\ForbiddenHttpException('No tiene permisos para acceder al dashboard.');
        }
    }
}
