<?php
/** @var yii\web\View $this */
/** @var app\models\search\AuditLogSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array<string, mixed> $estadisticas */

use app\models\AuditLog;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Auditoria';
$this->params['breadcrumbs'][] = $this->title;

$accionOptions = [
    '' => 'Todas',
    AuditLog::ACTION_CREATE => AuditLog::ACTION_CREATE,
    AuditLog::ACTION_UPDATE => AuditLog::ACTION_UPDATE,
    AuditLog::ACTION_DELETE => AuditLog::ACTION_DELETE,
    AuditLog::ACTION_LOGIN => AuditLog::ACTION_LOGIN,
    AuditLog::ACTION_LOGOUT => AuditLog::ACTION_LOGOUT,
    AuditLog::ACTION_EXPORT => AuditLog::ACTION_EXPORT,
    AuditLog::ACTION_ROLLBACK => AuditLog::ACTION_ROLLBACK,
];

$statsAcciones = (array) ($estadisticas['por_accion'] ?? []);
$statsUltimos = (int) ($estadisticas['ultimos_7_dias'] ?? 0);
$totalLogs = (int) ($estadisticas['total_logs'] ?? 0);
?>

<div class="audit-log-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-journal-text me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-download me-1"></i>Exportar CSV', array_merge(['export-csv'], Yii::$app->request->queryParams), ['class' => 'btn btn-outline-primary']) ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Total de logs</div>
                    <div class="h4 mb-0"><?= number_format((int)$totalLogs, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Ultimos 7 dias</div>
                    <div class="h4 mb-0"><?= number_format((int)$statsUltimos, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="small text-muted">Acciones registradas</div>
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <?php foreach ($statsAcciones as $accion => $cantidad): ?>
                            <span class="badge text-bg-secondary"><?= Html::encode((string) $accion) ?>: <?= (int) $cantidad ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'id')->textInput(['placeholder' => 'ID']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'usuario_id')->textInput(['placeholder' => 'Usuario ID']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'accion')->dropDownList($accionOptions) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'modulo')->textInput(['placeholder' => 'Modulo']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'entidad')->textInput(['placeholder' => 'Entidad']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'registro_id')->textInput(['placeholder' => 'Registro ID']) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_desde')->input('date') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_hasta')->input('date') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'buscar_valor')->textInput(['placeholder' => 'Texto dentro de datos previos/nuevos']) ?>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary ts-action-btn">
                        <i class="bi bi-search"></i><span>Filtrar</span>
                    </button>
                    <?= Html::a('<i class="bi bi-x-circle"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns' => [
            [
                'attribute' => 'id',
                'contentOptions' => ['style' => 'width: 90px;'],
            ],
            [
                'label' => 'Fecha',
                'attribute' => 'created_at',
                'value' => static fn(AuditLog $model): string => date('d/m/Y H:i:s', strtotime((string) $model->created_at)),
            ],
            [
                'label' => 'Usuario',
                'format' => 'raw',
                'value' => static fn(AuditLog $model): string => $model->usuario
                    ? Html::encode($model->usuario->getFullName())
                    : '<span class="text-muted">Sistema</span>',
            ],
            [
                'attribute' => 'accion',
                'format' => 'raw',
                'value' => static function (AuditLog $model): string {
                    $color = match ($model->accion) {
                        AuditLog::ACTION_CREATE => 'success',
                        AuditLog::ACTION_UPDATE => 'warning text-dark',
                        AuditLog::ACTION_DELETE => 'danger',
                        AuditLog::ACTION_LOGIN, AuditLog::ACTION_LOGOUT => 'info text-dark',
                        default => 'secondary',
                    };
                    return '<span class="badge bg-' . $color . '">' . Html::encode($model->accion) . '</span>';
                },
            ],
            'modulo',
            'entidad',
            'registro_id',
            [
                'attribute' => 'ip_address',
                'value' => static fn(AuditLog $model): string => (string) ($model->ip_address ?: '—'),
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'buttons' => [
                    'view' => static fn(string $url, AuditLog $model): string => Html::a(
                        '<i class="bi bi-eye"></i><span>Ver</span>',
                        ['view', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-primary ts-action-btn']
                    ),
                ],
            ],
        ],
    ]); ?>
</div>
