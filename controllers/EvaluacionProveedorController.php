<?php

namespace app\controllers;

use Yii;
use app\models\EvaluacionProveedor;
use app\models\Proveedor;
use app\models\OrdenCompra;
use app\models\search\EvaluacionProveedorSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * EvaluacionProveedorController implementa el CRUD para la gestión de evaluaciones de proveedores.
 */
class EvaluacionProveedorController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'reporte'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['admin', 'gerente', 'jefe_taller'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all EvaluacionProveedor models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new EvaluacionProveedorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // KPIs para el dashboard
        $totalEvaluaciones = EvaluacionProveedor::find()->count();
        $promedioGeneral = EvaluacionProveedor::find()->average('puntaje_promedio') ?? 0;
        
        // Proveedores mejor evaluados del mes
        $mejoresProveedores = EvaluacionProveedor::find()
            ->select(['proveedor_id', 'AVG(puntaje_promedio) as puntaje'])
            ->where(['>=', 'fecha_evaluacion', date('Y-m-01')])
            ->groupBy('proveedor_id')
            ->orderBy(['puntaje' => SORT_DESC])
            ->limit(5)
            ->all();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalEvaluaciones' => $totalEvaluaciones,
            'promedioGeneral' => round($promedioGeneral, 2),
            'mejoresProveedores' => $mejoresProveedores,
        ]);
    }

    /**
     * Displays a single EvaluacionProveedor model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    /**
     * Creates a new EvaluacionProveedor model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new EvaluacionProveedor();
        $model->fecha_evaluacion = date('Y-m-d');

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->evaluado_por = Yii::$app->user->id;
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Evaluación creada exitosamente.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        // Lista de proveedores activos
        $proveedores = Proveedor::getListaParaDropdown();
        
        // Lista de órdenes de compra completadas para seleccionar
        $ordenesCompras = OrdenCompra::find()
            ->where(['estado' => OrdenCompra::ESTADO_RECIBIDA_COMPLETO])
            ->all();
        $listaOrdenes = [];
        foreach ($ordenesCompras as $orden) {
            $listaOrdenes[$orden->id] = "{$orden->numero_orden} - {$orden->proveedor->nombre}";
        }

        return $this->render('create', [
            'model' => $model,
            'proveedores' => $proveedores,
            'listaOrdenes' => $listaOrdenes,
        ]);
    }

    /**
     * Updates an existing EvaluacionProveedor model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->evaluado_por = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Evaluación actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $proveedores = Proveedor::getListaParaDropdown();
        
        $ordenesCompras = OrdenCompra::find()
            ->where(['estado' => OrdenCompra::ESTADO_RECIBIDA_COMPLETO])
            ->all();
        $listaOrdenes = [];
        foreach ($ordenesCompras as $orden) {
            $listaOrdenes[$orden->id] = "{$orden->numero_orden} - {$orden->proveedor->nombre}";
        }

        return $this->render('update', [
            'model' => $model,
            'proveedores' => $proveedores,
            'listaOrdenes' => $listaOrdenes,
        ]);
    }

    /**
     * Deletes an existing EvaluacionProveedor model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        $model->delete();

        Yii::$app->session->setFlash('success', 'Evaluación eliminada exitosamente.');

        return $this->redirect(['index']);
    }

    /**
     * Reporte de evaluaciones por período
     * @return mixed
     */
    public function actionReporte()
    {
        $searchModel = new EvaluacionProveedorSearch();
        $dataProvider = $searchModel->searchReporte(Yii::$app->request->queryParams);

        // Datos para gráficos
        $mesActual = (int)date('n');
        $anioActual = (int)date('Y');
        
        $rankingMensual = EvaluacionProveedor::getRankingProveedores($mesActual, $anioActual);

        return $this->render('reporte', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'rankingMensual' => $rankingMensual,
            'mesActual' => $mesActual,
            'anioActual' => $anioActual,
        ]);
    }

    /**
     * Finds the EvaluacionProveedor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return EvaluacionProveedor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = EvaluacionProveedor::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La evaluación solicitada no existe.');
    }
}
