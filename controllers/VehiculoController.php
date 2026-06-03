<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\Vehiculo;
use app\models\Cliente;
use app\models\search\VehiculoSearch;
use app\components\services\VehiculoService;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * VehiculoController: CRUD de vehículos del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class VehiculoController extends BaseController
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
                    'create-ajax' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'vehiculo',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'eliminar',
                    'create-ajax' => 'crear',
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
        $searchModel  = new VehiculoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de vehículo. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new VehiculoService();

        if (Yii::$app->request->isPost) {
            $data  = Yii::$app->request->post('Vehiculo', []);
            $foto  = UploadedFile::getInstanceByName('Vehiculo[foto]');
            $v     = $service->create($data, $foto ?: null);
            if ($v !== null) {
                Yii::$app->session->setFlash('success', 'Vehículo registrado exitosamente.');
                return $this->redirect(['view', 'id' => $v->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new Vehiculo();
        // Pre-cargar atributos desde query params (ej: ?Vehiculo[cliente_id]=21)
        $model->load(Yii::$app->request->get());

        return $this->render('create', [
            'model'    => $model,
            'clientes' => $this->getClientesList(),
        ]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $vehiculo = $this->findModel($id);
        $service  = new VehiculoService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Vehiculo', []);
            $foto    = UploadedFile::getInstanceByName('Vehiculo[foto]');
            $updated = $service->update($vehiculo, $data, $foto ?: null);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Vehículo actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $vehiculo->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', [
            'model'    => $vehiculo,
            'clientes' => $this->getClientesList(),
        ]);
    }

    /** Desactiva un vehículo (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new VehiculoService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Vehículo desactivado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /**
     * Retorna vehículos de un cliente como JSON (para AJAX dropdowns).
     *
     * @param int $clienteId
     * @return array<array{id:int,patente:string,marca:string,modelo:string}>
     */
    public function actionPorCliente(int $clienteId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $service = new VehiculoService();
        return $service->getVehiculosPorCliente($clienteId);
    }

    /**
     * Creación rápida de vehículo desde modal AJAX (HU-001).
     */
    public function actionCreateAjax(): Response
    {
        $this->requireAdminOrOperador();
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            Yii::$app->response->statusCode = 405;
            return $this->asJson([
                'success' => false,
                'message' => 'Método no permitido.',
            ]);
        }

        $service = new VehiculoService();
        $data = Yii::$app->request->post('Vehiculo', []);
        
        try {
            $vehiculo = $service->create($data);
        } catch (\app\components\services\ServiceException $e) {
            Yii::$app->response->statusCode = 422;
            return $this->asJson([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        if ($vehiculo === null) {
            Yii::$app->response->statusCode = 422;
            return $this->asJson([
                'success' => false,
                'message' => $service->getPrimerError() ?: 'No fue posible crear el vehículo.',
            ]);
        }

        return $this->asJson([
            'success' => true,
            'id' => $vehiculo->id,
            'patente' => $vehiculo->patente,
            'text' => "{$vehiculo->patente} - {$vehiculo->marca} {$vehiculo->modelo}",
        ]);
    }

    private function findModel(int $id): Vehiculo
    {
        $model = Vehiculo::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Vehículo no encontrado.');
        }
        return $model;
    }

    /** @return array<int,string> */
    private function getClientesList(): array
    {
        return Cliente::find()
            ->where(['status' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }
}
