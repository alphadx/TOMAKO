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
use app\models\Etiqueta;
use app\models\search\EtiquetaSearch;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * EtiquetaController: CRUD de etiquetas para segmentación de clientes (HU-006).
 * Requiere usuario autenticado; operaciones de escritura requieren admin u operador.
 */
class EtiquetaController extends BaseController
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
                'permisoBase' => 'etiqueta',
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

    /** Listado paginado de etiquetas con KPI cards. */
    public function actionIndex(): string
    {
        $searchModel  = new EtiquetaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // Estadísticas rápidas
        $totalEtiquetas = Etiqueta::find()->where(['status' => 1])->count();
        $totalClientesConEtiquetas = (new \yii\db\Query())
            ->select('COUNT(DISTINCT cliente_id)')
            ->from('cliente_etiqueta')
            ->innerJoin('etiqueta', 'etiqueta.id = cliente_etiqueta.etiqueta_id')
            ->where(['etiqueta.status' => 1])
            ->scalar();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'totalEtiquetas' => $totalEtiquetas,
            'totalClientesConEtiquetas' => $totalClientesConEtiquetas,
        ]);
    }

    /** Detalle de etiqueta. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $model = new Etiqueta();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Etiqueta', []);
            $model->load($data, '');
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Etiqueta creada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', 'No se pudo crear la etiqueta. Verifique los datos.');
        }

        return $this->render('create', ['model' => $model]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $model = $this->findModel($id);

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Etiqueta', []);
            $model->load($data, '');
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Etiqueta actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            Yii::$app->session->setFlash('error', 'No se pudo actualizar la etiqueta. Verifique los datos.');
        }

        return $this->render('update', ['model' => $model]);
    }

    /** Elimina una etiqueta (soft delete). */
    public function actionDelete(int $id): Response
    {
        $this->requireAdminOrOperador();
        $model = $this->findModel($id);
        $model->status = 0;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Etiqueta eliminada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo eliminar la etiqueta.');
        }

        return $this->redirect(['index']);
    }

    /** API endpoint para obtener etiquetas disponibles (AJAX). */
    public function actionApiList(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $etiquetas = Etiqueta::find()
            ->where(['status' => 1])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();
        
        $results = [];
        foreach ($etiquetas as $etiqueta) {
            $results[] = [
                'id' => $etiqueta->id,
                'nombre' => $etiqueta->nombre,
                'color' => $etiqueta->color,
                'badgeClass' => $etiqueta->badgeClass,
            ];
        }
        
        return $this->asJson($results);
    }

    private function findModel(int $id): Etiqueta
    {
        $model = Etiqueta::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Etiqueta no encontrada.');
        }
        return $model;
    }
}
