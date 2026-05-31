<?php
/** @var yii\web\View $this */
/** @var app\models\AuditLog $model */
/** @var array<string, mixed>|null $diff */

use app\models\AuditLog;
use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

$this->title = 'Detalle de Auditoria #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Auditoria', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$cambios = (array) ($diff['cambios'] ?? []);
?>

<div class="audit-log-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-file-earmark-text me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-download me-1"></i>Exportar CSV', ['export-csv', 'AuditLogSearch[id]' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'label' => 'Usuario',
                        'format' => 'raw',
                        'value' => $model->usuario
                            ? Html::encode($model->usuario->getFullName())
                            : '<span class="text-muted">Sistema</span>',
                    ],
                    [
                        'attribute' => 'accion',
                        'format' => 'raw',
                        'value' => static function (AuditLog $audit): string {
                            $color = match ($audit->accion) {
                                AuditLog::ACTION_CREATE => 'success',
                                AuditLog::ACTION_UPDATE => 'warning text-dark',
                                AuditLog::ACTION_DELETE => 'danger',
                                AuditLog::ACTION_LOGIN, AuditLog::ACTION_LOGOUT => 'info text-dark',
                                default => 'secondary',
                            };
                            return '<span class="badge bg-' . $color . '">' . Html::encode($audit->accion) . '</span>';
                        },
                    ],
                    'modulo',
                    'entidad',
                    'registro_id',
                    [
                        'attribute' => 'ip_address',
                        'value' => $model->ip_address ?: '—',
                    ],
                    [
                        'attribute' => 'duracion_ms',
                        'value' => (int) $model->duracion_ms . ' ms',
                    ],
                    [
                        'attribute' => 'created_at',
                        'value' => date('d/m/Y H:i:s', strtotime((string) $model->created_at)),
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Datos previos</strong></div>
                <div class="card-body">
                    <pre class="small mb-0" style="white-space: pre-wrap;"><?= Html::encode($model->datos_previos ?: '{}') ?></pre>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><strong>Datos nuevos</strong></div>
                <div class="card-body">
                    <pre class="small mb-0" style="white-space: pre-wrap;"><?= Html::encode($model->datos_nuevos ?: '{}') ?></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><strong>Cambios detectados</strong></div>
        <div class="card-body p-0">
            <?php if ($cambios === []): ?>
                <div class="p-3 text-muted">No hay diferencias de campos para este evento.</div>
            <?php else: ?>
                <?php
                $cambiosData = [];
                foreach ($cambios as $campo => $valor) {
                    $cambiosData[] = [
                        'campo' => $campo,
                        'anterior' => $valor['anterior'] ?? null,
                        'nuevo' => $valor['nuevo'] ?? null,
                        'tipo' => $valor['tipo'] ?? '',
                    ];
                }
                $cambiosProvider = new ArrayDataProvider([
                    'allModels' => $cambiosData,
                    'pagination' => false,
                ]);
                ?>
                <?= GridView::widget([
                    'dataProvider' => $cambiosProvider,
                    'tableOptions' => ['class' => 'table table-striped table-hover mb-0 align-middle'],
                    'layout' => '{items}',
                    'columns' => [
                        [
                            'label' => 'Campo',
                            'attribute' => 'campo',
                            'format' => 'raw',
                            'value' => fn($model) => '<code>' . Html::encode((string) $model['campo']) . '</code>',
                            'contentOptions' => ['style' => 'width: 24%;'],
                        ],
                        [
                            'label' => 'Anterior',
                            'value' => fn($model) => Html::encode(is_scalar($model['anterior']) ? (string) $model['anterior'] : json_encode($model['anterior'], JSON_UNESCAPED_UNICODE)),
                        ],
                        [
                            'label' => 'Nuevo',
                            'value' => fn($model) => Html::encode(is_scalar($model['nuevo']) ? (string) $model['nuevo'] : json_encode($model['nuevo'], JSON_UNESCAPED_UNICODE)),
                        ],
                        [
                            'label' => 'Tipo',
                            'format' => 'raw',
                            'value' => fn($model) => '<span class="badge text-bg-light">' . Html::encode((string) $model['tipo']) . '</span>',
                            'contentOptions' => ['style' => 'width: 12%;'],
                        ],
                    ],
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
