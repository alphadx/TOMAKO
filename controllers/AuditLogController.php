<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\services\AuditLogService;
use app\models\AuditLog;
use app\models\search\AuditLogSearch;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * AuditLogController: consulta de registros de auditoria.
 *
 * Se limita a listado, detalle y exportacion CSV. Los registros son inmutables.
 */
class AuditLogController extends BaseController
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
                'class' => VerbFilter::class,
                'actions' => [
                    'export-csv' => ['get'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $this->requireAdmin();

        $searchModel = new AuditLogSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $estadisticas = (new AuditLogService())->getEstadisticas();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'estadisticas' => $estadisticas,
        ]);
    }

    public function actionView(int $id): string
    {
        $this->requireAdmin();

        $service = new AuditLogService();
        $model = $service->getDetalle($id);

        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Registro de auditoria no encontrado.'));
        }

        return $this->render('view', [
            'model' => $model,
            'diff' => $service->getDiff($id),
        ]);
    }

    public function actionExportCsv(): Response
    {
        $this->requireAdmin();

        $filtros = Yii::$app->request->getQueryParam('AuditLogSearch', []);
        $csv = (new AuditLogService())->exportarCsv((array) $filtros);

        return Yii::$app->response->sendContentAsFile(
            $csv,
            'audit-log-' . date('Ymd-His') . '.csv',
            [
                'mimeType' => 'text/csv; charset=UTF-8',
                'inline' => false,
            ]
        );
    }

    private function requireAdmin(): void
    {
        /** @var User|null $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !$identity->isAdmin()) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para acceder a esta seccion.'));
        }
    }
}
