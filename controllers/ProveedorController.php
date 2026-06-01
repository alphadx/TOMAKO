<?php

namespace app\controllers;

use Yii;
use app\models\Proveedor;
use app\models\search\ProveedorSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use app\components\behaviors\AccessControlBehavior;

/**
 * ProveedorController implementa el CRUD para la gestión de proveedores.
 */
class ProveedorController extends BaseController
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
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'proveedor',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'activar' => 'editar',
                ],
            ],
        ];
    }

    /**
     * Lists all Proveedor models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ProveedorSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // KPIs para el dashboard
        $totalProveedores = Proveedor::find()->count();
        $proveedoresActivos = Proveedor::find()->where(['activo' => true])->count();
        $proveedoresInactivos = $totalProveedores - $proveedoresActivos;
        $calificacionPromedio = Proveedor::find()->average('calificacion') ?? 0;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalProveedores' => $totalProveedores,
            'proveedoresActivos' => $proveedoresActivos,
            'proveedoresInactivos' => $proveedoresInactivos,
            'calificacionPromedio' => $calificacionPromedio,
        ]);
    }

    /**
     * Displays a single Proveedor model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Proveedor model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Proveedor();

        if ($this->request->isPost) {
            if ($model->load($this->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', 'Proveedor creado exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        } else {
            $model->loadDefaultValues();
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Proveedor model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($this->request->isPost && $model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Proveedor actualizado exitosamente.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Proveedor model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Soft delete: desactivar en lugar de eliminar
        $model->activo = false;
        $model->save(false);
        
        Yii::$app->session->setFlash('success', 'Proveedor desactivado exitosamente.');

        return $this->redirect(['index']);
    }

    /**
     * Activa un proveedor previamente desactivado
     * @param integer $id
     * @return mixed
     */
    public function actionActivar($id)
    {
        $model = $this->findModel($id);
        $model->activo = true;
        $model->save(false);
        
        Yii::$app->session->setFlash('success', 'Proveedor activado exitosamente.');
        
        return $this->redirect(['index']);
    }

    /**
     * Finds the Proveedor model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Proveedor the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Proveedor::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('El proveedor solicitado no existe.');
    }
}
