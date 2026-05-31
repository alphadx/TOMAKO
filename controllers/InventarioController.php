<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use app\models\InventoryItem;
use app\models\search\InventoryItemSearch;
use app\components\services\InventarioService;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * InventarioController: gestión de inventario de insumos del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class InventarioController extends BaseController
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
                    'entrada'    => ['post'],
                    'salida'     => ['post'],
                    'ajuste'     => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'inventario',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'eliminar',
                    'entrada' => 'editar',
                    'salida' => 'editar',
                    'ajuste' => 'editar',
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

    /** Listado con KPI cards y filtros. */
    public function actionIndex(): string
    {
        $searchModel  = new InventoryItemSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $service = new InventarioService();
        $kpis    = $service->getKpis();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'kpis'         => $kpis,
        ]);
    }

    /** Detalle de ítem con historial de movimientos. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('InventoryItem', []);
            $item = $service->create($data);
            if ($item !== null) {
                Yii::$app->session->setFlash('success', 'Ítem creado exitosamente.');
                return $this->redirect(['view', 'id' => $item->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new InventoryItem();
        $model->sku = InventoryItem::generarSku();

        return $this->render('create', ['model' => $model]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $item    = $this->findModel($id);
        $service = new InventarioService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('InventoryItem', []);
            $updated = $service->update($item, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Ítem actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $item->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', ['model' => $item]);
    }

    /** Desactiva un ítem (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Ítem desactivado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Exporta el inventario activo como CSV. */
    public function actionExportCsv(): Response
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();
        $csv = $service->exportarCsv();

        return Yii::$app->response->sendContentAsFile(
            $csv,
            'inventario-' . date('Y-m-d-His') . '.csv',
            ['mimeType' => 'text/csv; charset=UTF-8']
        );
    }

    /** Registra entrada de stock (POST). */
    public function actionEntrada(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service  = new InventarioService();
        $post     = Yii::$app->request->post();
        $cantidad = (int) ($post['cantidad'] ?? 0);
        $ref      = (string) ($post['referencia'] ?? 'Entrada manual');
        $userId   = (int) Yii::$app->user->id;

        if ($cantidad <= 0) {
            Yii::$app->session->setFlash('error', 'La cantidad debe ser mayor a cero.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $service->registrarEntrada($id, $cantidad, $ref, $userId);
            Yii::$app->session->setFlash('success', "Entrada de {$cantidad} unidades registrada.");
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Registra ajuste de stock (POST). */
    public function actionAjuste(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service  = new InventarioService();
        $post     = Yii::$app->request->post();
        $cantidad = (int) ($post['cantidad_nueva'] ?? -1);
        $motivo   = (string) ($post['motivo'] ?? 'Ajuste manual');
        $userId   = (int) Yii::$app->user->id;

        if ($cantidad < 0) {
            Yii::$app->session->setFlash('error', 'La cantidad no puede ser negativa.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $service->registrarAjuste($id, $cantidad, $motivo, $userId);
            Yii::$app->session->setFlash('success', "Stock ajustado a {$cantidad} unidades.");
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    private function findModel(int $id): InventoryItem
    {
        $model = InventoryItem::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Ítem de inventario no encontrado.');
        }
        return $model;
    }
}
