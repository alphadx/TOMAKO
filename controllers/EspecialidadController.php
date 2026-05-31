<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\models\Especialidad;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * EspecialidadController: CRUD de especialidades de técnicos.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class EspecialidadController extends BaseController
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
                'class'   => VerbFilter::class,
                'actions' => [
                    'deactivate' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'especialidad',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'eliminar',
                ],
            ],
        ];
    }

    private function requireAdmin(): void
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || $identity->rol_id !== 1) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para esta acción.'));
        }
    }

    /** Listado de especialidades. */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Especialidad::find()->orderBy('nombre'),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdmin();
        $model = new Especialidad();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Especialidad creada exitosamente.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdmin();
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Especialidad actualizada exitosamente.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    /** Desactiva una especialidad (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdmin();
        $model = $this->findModel($id);
        $model->status = 0;

        if ($model->save(false, ['status'])) {
            Yii::$app->session->setFlash('success', 'Especialidad desactivada.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo desactivar la especialidad.');
        }

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Especialidad
    {
        $model = Especialidad::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Especialidad no encontrada.');
        }
        return $model;
    }
}
