<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Seguimiento;
use app\models\search\SeguimientoSearch;
use app\components\behaviors\AccessControlBehavior;

/**
 * SeguimientoController handles post-service follow-up operations
 * Routes:
 *  GET    /seguimiento              index (list/agenda)
 *  GET    /seguimiento/<id>         view (detail)
 *  POST   /seguimiento/<id>/completar    completar
 *  GET    /seguimiento/<id>/editar      update form
 *  POST   /seguimiento/<id>/editar      update
 *  GET    /seguimiento/pendientes       pendientes del día
 *  GET    /seguimiento/reportes         reportes de satisfacción
 */
class SeguimientoController extends Controller
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
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'completar' => ['post'],
                    'update' => ['get', 'post'],
                    'delete' => ['post'],
                    'pendientes' => ['get'],
                    'reportes' => ['get'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'seguimiento',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'completar' => 'editar',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'pendientes' => 'ver',
                    'reportes' => 'ver',
                ],
            ],
        ];
    }

    /**
     * Lista/agenda de seguimientos
     */
    public function actionIndex()
    {
        $searchModel = new SeguimientoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Ordenar por fecha programada por defecto
        $dataProvider->sort->defaultOrder = ['fecha_programada' => SORT_DESC];

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Detalle de un seguimiento
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Formulario para completar/editar un seguimiento
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->scenario = 'registro';

        if ($model->load(Yii::$app->request->post())) {
            // Calcular NPS automáticamente si hay satisfacción
            if ($model->satisfaccion !== null) {
                $model->calcularNPS();
            }
            
            // Determinar estado basado en si se completó
            if ($model->resultado !== null || $model->satisfaccion !== null) {
                $model->estado = 'completado';
                if ($model->fecha_realizacion === null) {
                    $model->fecha_realizacion = time();
                }
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Seguimiento actualizado correctamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Completa rápidamente un seguimiento con resultado básico
     */
    public function actionCompletar($id)
    {
        $model = $this->findModel($id);
        
        if (Yii::$app->request->isPost) {
            $resultado = Yii::$app->request->post('resultado', '');
            $satisfaccion = Yii::$app->request->post('satisfaccion');
            $observaciones = Yii::$app->request->post('observaciones', '');
            $recomendariamos = Yii::$app->request->post('recomendariamos');

            $model->resultado = $resultado;
            $model->satisfaccion = $satisfaccion;
            $model->observaciones = $observaciones;
            $model->recomendariamos = $recomendariamos;
            $model->estado = 'completado';
            $model->fecha_realizacion = time();
            $model->realizado_por = Yii::$app->user->id;

            if ($model->satisfaccion !== null) {
                $model->calcularNPS();
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Seguimiento completado correctamente.');
                
                // Si viene de la agenda, volver a la agenda
                $referrer = Yii::$app->request->referrer;
                if (strpos($referrer, 'seguimiento/index') !== false) {
                    return $this->redirect(['index']);
                }
                
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Elimina un seguimiento
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Seguimiento eliminado correctamente.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo eliminar el seguimiento.');
        }

        return $this->redirect(['index']);
    }

    /**
     * Muestra los seguimientos pendientes del día
     */
    public function actionPendientes()
    {
        $hoy = strtotime('today');
        
        $dataProvider = new ActiveDataProvider([
            'query' => Seguimiento::findPendientesParaFecha($hoy)
                ->joinWith(['cliente', 'ordenServicio'])
                ->orderBy(['fecha_programada' => SORT_ASC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('pendientes', [
            'dataProvider' => $dataProvider,
            'fecha' => $hoy,
        ]);
    }

    /**
     * Reportes de satisfacción y NPS
     */
    public function actionReportes()
    {
        // Obtener parámetros de fecha
        $inicio = Yii::$app->request->get('inicio', strtotime('first day of this month'));
        $fin = Yii::$app->request->get('fin', strtotime('today'));

        $estadisticas = Seguimiento::getEstadisticasPeriodo((int)$inicio, (int)$fin);

        // Segumientos completados en el período
        $seguimientosProvider = new ActiveDataProvider([
            'query' => Seguimiento::find()
                ->where(['estado' => 'completado'])
                ->andWhere(['>=', 'fecha_realizacion', $inicio])
                ->andWhere(['<=', 'fecha_realizacion', $fin])
                ->orderBy(['fecha_realizacion' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('reportes', [
            'estadisticas' => $estadisticas,
            'seguimientosProvider' => $seguimientosProvider,
            'inicio' => $inicio,
            'fin' => $fin,
        ]);
    }

    /**
     * Finds the Seguimiento model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param int $id ID
     * @return Seguimiento the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id): Seguimiento
    {
        $model = Seguimiento::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Seguimiento no encontrado.');
        }
        return $model;
    }
}
