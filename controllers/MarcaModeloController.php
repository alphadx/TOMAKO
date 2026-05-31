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
use app\models\Modelo;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * MarcaModeloController: Mantenedor integrado de marcas y modelos de vehículos.
 * Permite gestionar marcas y sus modelos en una sola interfaz.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class MarcaModeloController extends BaseController
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
                    'delete-marca' => ['post'],
                    'delete-modelo' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'marca',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create-marca' => 'crear',
                    'update-marca' => 'editar',
                    'delete-marca' => 'eliminar',
                    'create-modelo' => 'crear',
                    'update-modelo' => 'editar',
                    'delete-modelo' => 'eliminar',
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

    /** Listado integrado de marcas con sus modelos. */
    public function actionIndex(): string
    {
        $marcas = Marca::find()
            ->orderBy('nombre')
            ->all();

        return $this->render('index', [
            'marcas' => $marcas,
        ]);
    }

    /** Detalle de marca con sus modelos. */
    public function actionView(int $id): string
    {
        $marca = $this->findMarcaModel($id);
        $modelos = Modelo::find()
            ->where(['marca_id' => $id])
            ->orderBy('nombre')
            ->all();

        return $this->render('view', [
            'marca' => $marca,
            'modelos' => $modelos,
        ]);
    }

    /** Formulario de creación de marca. */
    public function actionCreateMarca(): Response|string
    {
        $this->requireAdminOrOperador();
        $model = new Marca();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Marca', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Marca registrada exitosamente.');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('_form-marca', [
            'model' => $model,
        ]);
    }

    /** Formulario de edición de marca. */
    public function actionUpdateMarca(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $model = $this->findMarcaModel($id);

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Marca', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Marca actualizada exitosamente.');
                return $this->redirect(['index']);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('_form-marca', [
            'model' => $model,
        ]);
    }

    /** Elimina una marca. */
    public function actionDeleteMarca(int $id): Response
    {
        $this->requireAdminOrOperador();
        
        try {
            $model = $this->findMarcaModel($id);
            
            // Verificar si tiene modelos asociados
            $tieneModelos = Modelo::find()->where(['marca_id' => $id])->exists();
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

    /** Formulario de creación de modelo. */
    public function actionCreateModelo(int $marcaId): Response|string
    {
        $this->requireAdminOrOperador();
        $marca = $this->findMarcaModel($marcaId);
        $model = new Modelo();
        $model->marca_id = $marcaId;

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Modelo', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Modelo registrado exitosamente.');
                return $this->redirect(['view', 'id' => $marcaId]);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('_form-modelo', [
            'model' => $model,
            'marca' => $marca,
        ]);
    }

    /** Formulario de edición de modelo. */
    public function actionUpdateModelo(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $model = $this->findModeloModel($id);
        $marca = $model->marca;

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Modelo', []);
            $model->nombre = $data['nombre'] ?? '';
            
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Modelo actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $model->marca_id]);
            }
            Yii::$app->session->setFlash('error', $model->getPrimerError());
        }

        return $this->render('_form-modelo', [
            'model' => $model,
            'marca' => $marca,
        ]);
    }

    /** Elimina un modelo. */
    public function actionDeleteModelo(int $id): Response
    {
        $this->requireAdminOrOperador();
        
        try {
            $model = $this->findModeloModel($id);
            $marcaId = $model->marca_id;
            
            // Verificar si hay vehículos asociados
            $tieneVehiculos = \app\models\Vehiculo::find()->where(['modelo_id' => $id])->exists();
            if ($tieneVehiculos) {
                Yii::$app->session->setFlash('error', 'No se puede eliminar el modelo porque tiene vehículos asociados.');
                return $this->redirect(['view', 'id' => $marcaId]);
            }
            
            $model->delete();
            Yii::$app->session->setFlash('success', 'Modelo eliminado exitosamente.');
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', 'Error al eliminar el modelo: ' . $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $marcaId ?? 0]);
    }

    private function findMarcaModel(int $id): Marca
    {
        $model = Marca::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Marca no encontrada.');
        }
        return $model;
    }

    private function findModeloModel(int $id): Modelo
    {
        $model = Modelo::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Modelo no encontrado.');
        }
        return $model;
    }
}
