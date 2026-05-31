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
use app\models\Categoria;
use app\models\search\CategoriaSearch;
use app\components\services\CategoriaService;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * CategoriaController: CRUD de categorías.
 * Requiere usuario autenticado; operaciones de escritura requieren admin u operador.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class CategoriaController extends BaseController
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
                    'delete' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'categoria',
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

    /** Verifica que el usuario sea admin u operador (rol_id 1 o 2). */
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
        $searchModel  = new CategoriaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de categoría. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new CategoriaService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Categoria', []);
            $cat  = $service->create($data);
            if ($cat !== null) {
                Yii::$app->session->setFlash('success', 'Categoría creada exitosamente.');
                return $this->redirect(['view', 'id' => $cat->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new Categoria();
        return $this->render('create', [
            'model' => $model,
            'tree'  => Categoria::getTree(),
        ]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $cat     = $this->findModel($id);
        $service = new CategoriaService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Categoria', []);
            $updated = $service->update($cat, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Categoría actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $cat->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', [
            'model' => $cat,
            'tree'  => Categoria::getTree(),
        ]);
    }

    /** Desactiva una categoría (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new CategoriaService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Categoría desactivada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Elimina una categoría vacía (POST). */
    public function actionDelete(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new CategoriaService();

        if ($service->deleteIfEmpty($id)) {
            Yii::$app->session->setFlash('success', 'Categoría eliminada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Categoria
    {
        $model = Categoria::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Categoría no encontrada.');
        }
        return $model;
    }
}
