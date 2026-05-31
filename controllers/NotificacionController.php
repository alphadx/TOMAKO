<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\components\services\NotificacionService;
use app\models\Notificacion;
use app\models\PlantillaNotificacion;
use app\models\PreferenciaNotificacion;
use app\models\EmailLog;
use app\models\search\NotificacionSearch;

class NotificacionController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'marcar-leida' => ['post'],
                    'marcar-todas-leidas' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $searchModel = new NotificacionSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, (int) Yii::$app->user->id);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionMarcarLeida(int $id): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $service = new NotificacionService();
        $service->marcarLeida($id, (int) Yii::$app->user->id);

        return $this->asJson(['ok' => true]);
    }

    public function actionMarcarTodasLeidas(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $service = new NotificacionService();
        $service->marcarTodasLeidas((int) Yii::$app->user->id);

        return $this->asJson(['ok' => true]);
    }

    public function actionContadorJson(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $service = new NotificacionService();

        return $this->asJson([
            'count' => $service->getCountNoLeidas((int) Yii::$app->user->id),
        ]);
    }

    public function actionPreferencias(): string|Response
    {
        $usuarioId = (int) Yii::$app->user->id;
        $model = PreferenciaNotificacion::findOrCreate($usuarioId);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Preferencias guardadas.');
            return $this->redirect(['preferencias']);
        }

        return $this->render('preferencias', ['model' => $model]);
    }

    public function actionPlantillas(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => PlantillaNotificacion::find()->orderBy(['codigo' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('plantillas', ['dataProvider' => $dataProvider]);
    }

    public function actionCrearPlantilla(): string|Response
    {
        $model = new PlantillaNotificacion([
            'activo' => 1,
            'canal' => PlantillaNotificacion::CANAL_EMAIL,
            'variables' => '[]',
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Plantilla creada.');
            return $this->redirect(['plantillas']);
        }

        return $this->render('plantilla-form', ['model' => $model]);
    }

    public function actionEditarPlantilla(int $id): string|Response
    {
        $model = PlantillaNotificacion::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Plantilla no encontrada.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            $model->save(false);
            Yii::$app->session->setFlash('success', 'Plantilla actualizada.');
            return $this->redirect(['plantillas']);
        }

        return $this->render('plantilla-form', ['model' => $model]);
    }

    public function actionEmailLog(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => EmailLog::find()->orderBy(['enviado_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('email-log', ['dataProvider' => $dataProvider]);
    }

    public function actionTestEmail(): string|Response
    {
        $destinatario = trim((string) Yii::$app->request->post('destinatario', ''));
        $plantilla = trim((string) Yii::$app->request->post('plantilla', ''));

        if (Yii::$app->request->isPost) {
            if ($destinatario === '' || $plantilla === '') {
                Yii::$app->session->setFlash('danger', 'Debe indicar destinatario y plantilla.');
            } else {
                $ok = (new NotificacionService())->enviarEmail($destinatario, $plantilla, []);
                Yii::$app->session->setFlash($ok ? 'success' : 'danger', $ok ? 'Email enviado.' : 'No se pudo enviar el email.');
            }
        }

        $plantillas = PlantillaNotificacion::find()
            ->where(['activo' => 1])
            ->orderBy(['codigo' => SORT_ASC])
            ->all();

        return $this->render('test-email', [
            'destinatario' => $destinatario,
            'plantilla' => $plantilla,
            'plantillas' => $plantillas,
        ]);
    }
}
