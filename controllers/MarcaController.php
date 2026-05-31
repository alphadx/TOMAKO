<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use app\models\Marca;
use app\models\search\MarcaSearch;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * MarcaController: CRUD de marcas de vehículos.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class MarcaController extends BaseController
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
                    'delete' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'marca',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
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
        $searchModel  = new MarcaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de marca. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $model = new Marca();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Marca', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Marca registrada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Marca', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Marca actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /** Elimina una marca. */
    public function actionDelete(int $id): Response
    {
        $this->requireAdminOrOperador();
        
        try {
            $model = $this->findModel($id);
            
            // Verificar si tiene modelos asociados
            $tieneModelos = \app\models\Modelo::find()->where(['marca_id' => $id])->exists();
            if ($tieneModelos) {
                Yii::$app->session->setFlash('error', 'No se puede eliminar la marca porque tiene modelos asociados.');
                return $this->redirect(['index']);
            }
            
            $model->delete();
            Yii::$app->session->setFlash('success', 'Marca eliminada exitosamente.');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error al eliminar la marca: ' . $e->getMessage());
        }

        return $this->redirect(['index']);
    }

    private function findModel(int $id): Marca
    {
        $model = Marca::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Marca no encontrada.');
        }
        return $model;
    }
}
