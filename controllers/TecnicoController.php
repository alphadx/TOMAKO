<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use app\models\Tecnico;
use app\models\search\TecnicoSearch;
use app\components\services\TecnicoService;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * TecnicoController: CRUD de técnicos del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class TecnicoController extends BaseController
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
                    'deactivate'        => ['post'],
                    'add-certificacion' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'tecnico',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'editar',
                    'add-certificacion' => 'editar',
                ],
            ],
        ];
    }

    private function requireAdminOrOperador(): void
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !in_array($identity->rol_id, [1, 2], true)) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para esta acción.'));
        }
    }

    /** Listado con búsqueda. */
    public function actionIndex(): string
    {
        $searchModel  = new TecnicoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de técnico con certificaciones. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new TecnicoService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Tecnico', []);
            $tecnico = $service->create($data);
            if ($tecnico !== null) {
                Yii::$app->session->setFlash('success', 'Técnico registrado exitosamente.');
                return $this->redirect(['view', 'id' => $tecnico->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('create', ['model' => new Tecnico()]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $tecnico = $this->findModel($id);
        $service = new TecnicoService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Tecnico', []);
            $updated = $service->update($tecnico, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Técnico actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $tecnico->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', ['model' => $tecnico]);
    }

    /** Desactiva un técnico (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new TecnicoService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Técnico desactivado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Agrega una certificación al técnico (POST). */
    public function actionAddCertificacion(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new TecnicoService();
        $data    = Yii::$app->request->post('Certificacion', []);

        $cert = $service->addCertificacion($id, $data);
        if ($cert !== null) {
            Yii::$app->session->setFlash('success', 'Certificación agregada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function findModel(int $id): Tecnico
    {
        $model = Tecnico::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Técnico no encontrado.');
        }
        return $model;
    }
}
