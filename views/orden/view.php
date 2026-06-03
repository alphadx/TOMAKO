<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */
/** @var app\models\Tecnico[] $tecnicos */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use yii\bootstrap5\Tabs;
use app\models\OrdenServicio;

$this->title = 'Orden ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$transicionesLabels = OrdenServicio::getEstadosList();
$posiblesEstados    = [];
$todos              = OrdenServicio::ESTADOS;
foreach ($todos as $e) {
    if ($model->puedeTransicionar($e)) {
        $posiblesEstados[] = $e;
    }
}

$tabNotasUrl = Url::to(['tab-notas', 'id' => $model->id]);
$tabPagosUrl = Url::to(['tab-pagos', 'id' => $model->id]);
$tabChecklistUrl = Url::to(['tab-checklist', 'id' => $model->id]);
?>

<div class="orden-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-file-text me-2"></i><?= Html::encode($this->title) ?>
            <span class="badge <?= $model->getEstadoBadgeClass() ?> ms-2"><?= Html::encode($transicionesLabels[$model->estado] ?? $model->estado) ?></span>
            <span class="badge <?= $model->getPrioridadBadgeClass() ?> ms-1"><?= Html::encode(OrdenServicio::getPrioridadesList()[$model->prioridad] ?? $model->prioridad) ?></span>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($model->estado === 'abierto'): ?>
                <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Columna izquierda: info + detalles + notas -->
        <div class="col-md-8">

            <!-- Información general -->
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="bi bi-info-circle me-1"></i>Información General</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'options'    => ['class' => 'table table-bordered mb-0'],
                        'attributes' => [
                            'codigo',
                            ['label' => 'Cliente', 'format' => 'raw',
                             'value' => $model->cliente
                                ? Html::a(Html::encode($model->cliente->nombre), ['/cliente/view', 'id' => $model->cliente_id])
                                : '—'],
                            ['label' => 'Vehículo', 'format' => 'raw',
                             'value' => $model->vehiculo
                                ? '<span class="badge bg-dark me-1">' . Html::encode($model->vehiculo->patente) . '</span>' . Html::encode($model->vehiculo->marca . ' ' . $model->vehiculo->modelo)
                                : '—'],
                            ['label' => 'Cita', 'format' => 'raw',
                             'value' => $model->cita
                                ? Html::a('Cita #' . $model->cita_id, ['/cita/view', 'id' => $model->cita_id])
                                : '—'],
                            ['label' => 'Notas', 'value' => $model->notas_generales ?: '—'],
                            ['label' => 'Abierta',  'value' => $model->opened_at ? date('d/m/Y H:i', $model->opened_at) : '—'],
                            ['label' => 'Cerrada',  'value' => $model->closed_at  ? date('d/m/Y H:i', $model->closed_at) : '—'],
                            ['label' => 'Duración total estimada', 'value' => $model->getDuracionTotalLabel()],
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Servicios / Detalles -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-wrench me-1"></i>Servicios</strong>
                    <span class="badge bg-success">Total: $ <?= number_format((float)$model->total, 0, ',', '.') ?></span>
                </div>
                <?php if (empty($model->detalles)): ?>
                    <div class="card-body text-muted text-center">Sin servicios registrados.</div>
                <?php else: ?>
                    <?php
                    $detallesProvider = new ArrayDataProvider([
                        'allModels' => $model->detalles,
                        'pagination' => false,
                    ]);
                    ?>
                    <?= GridView::widget([
                        'dataProvider' => $detallesProvider,
                        'tableOptions' => ['class' => 'table table-sm table-striped mb-0'],
                        'headerRowOptions' => ['class' => 'table-dark'],
                        'layout' => '{items}',
                        'columns' => [
                            [
                                'label' => 'Servicio',
                                'format' => 'raw',
                                'value' => function ($d) {
                                    $name = Html::encode($d->servicio ? $d->servicio->nombre : "Svc #{$d->servicio_id}");
                                    if (!empty($d->nota)) {
                                        $name .= '<small class="text-muted d-block"><i class="bi bi-sticky me-1"></i>' . Html::encode($d->nota) . '</small>';
                                    }
                                    return $name;
                                },
                            ],
                            [
                                'attribute' => 'cantidad',
                                'contentOptions' => ['class' => 'text-center'],
                                'headerOptions' => ['class' => 'text-center'],
                            ],
                            [
                                'label' => 'P. Unit.',
                                'value' => fn($d) => '$ ' . number_format((float)$d->precio_unitario, 0, ',', '.'),
                                'contentOptions' => ['class' => 'text-end'],
                                'headerOptions' => ['class' => 'text-end'],
                            ],
                            [
                                'label' => 'Subtotal',
                                'value' => fn($d) => '$ ' . number_format((float)$d->subtotal, 0, ',', '.'),
                                'contentOptions' => ['class' => 'text-end fw-bold'],
                                'headerOptions' => ['class' => 'text-end'],
                            ],
                        ],
                    ]) ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 table-secondary">
                            <tr><td class="text-end fw-bold">Total</td><td class="text-end fw-bold">$ <?= number_format((float)$model->total, 0, ',', '.') ?></td></tr>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <?= Tabs::widget([
                'items' => [
                    [
                        'label' => 'Notas (' . count($model->notas) . ')',
                        'content' => '<div class="tab-lazy-content" data-loaded="0"><div class="text-muted small">Cargando notas...</div></div>',
                        'linkOptions' => ['data-lazy-url' => $tabNotasUrl],
                        'active' => true,
                    ],
                    [
                        'label' => 'Pagos',
                        'content' => '<div class="tab-lazy-content" data-loaded="0"><div class="text-muted small">Cargando pagos...</div></div>',
                        'linkOptions' => ['data-lazy-url' => $tabPagosUrl],
                    ],
                    [
                        'label' => 'Checklist (' . count($model->checklistItems) . ')',
                        'content' => '<div class="tab-lazy-content" data-loaded="0"><div class="text-muted small">Cargando checklist...</div></div>',
                        'linkOptions' => ['data-lazy-url' => $tabChecklistUrl],
                    ],
                ],
                'options' => ['id' => 'orden-tabs'],
            ]) ?>
        </div>

        <!-- Columna derecha: técnicos + cambio estado + timeline -->
        <div class="col-md-4">

            <!-- Técnicos asignados -->
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="bi bi-person-gear me-1"></i>Técnicos Asignados</strong></div>
                <?php if (!empty($model->asignaciones)): ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($model->asignaciones as $asig): ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <?= Html::encode($asig->tecnico ? $asig->tecnico->getFullName() : "Téc. #{$asig->tecnico_id}") ?>
                                    <small class="text-muted d-block"><?= $asig->asignado_at ? date('d/m/Y', $asig->asignado_at) : '' ?></small>
                                </div>
                                <form method="post" action="<?= \yii\helpers\Url::to(['desasignar-tecnico', 'id' => $model->id]) ?>">
                                    <?= \yii\helpers\Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken) ?>
                                    <input type="hidden" name="tecnico_id" value="<?= (int) $asig->tecnico_id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Desasignar técnico">
                                        <i class="bi bi-person-dash"></i>
                                    </button>
                                </form>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="card-body text-muted text-center small">Sin técnicos asignados.</div>
                <?php endif; ?>
                <!-- Asignar técnico -->
                <div class="card-footer">
                    <form method="post" action="<?= \yii\helpers\Url::to(['asignar-tecnico', 'id' => $model->id]) ?>">
                        <?= \yii\helpers\Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken) ?>
                        <div class="input-group input-group-sm">
                            <select name="tecnico_id" class="form-select form-select-sm">
                                <option value="">— Asignar técnico —</option>
                                <?php foreach ($tecnicos as $t): ?>
                                <option value="<?= $t->id ?>"><?= Html::encode($t->getFullName()) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cambiar estado -->
            <?php if (!empty($posiblesEstados)): ?>
            <div class="card shadow-sm mb-3 border-primary">
                <div class="card-header bg-primary text-white"><strong><i class="bi bi-arrow-repeat me-1"></i>Cambiar Estado</strong></div>
                <div class="card-body">
                    <form method="post" action="<?= \yii\helpers\Url::to(['cambiar-estado', 'id' => $model->id]) ?>">
                        <?= \yii\helpers\Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken) ?>
                        <div class="mb-2">
                            <select name="nuevo_estado" class="form-select form-select-sm" required>
                                <option value="">— Nuevo estado —</option>
                                <?php foreach ($posiblesEstados as $e): ?>
                                <option value="<?= $e ?>"><?= Html::encode($transicionesLabels[$e] ?? $e) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <input type="text" name="comentario" class="form-control form-control-sm" placeholder="Comentario (opcional)">
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-check-lg me-1"></i>Aplicar Cambio
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Timeline de estados -->
            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="bi bi-activity me-1"></i>Historial de Estados</strong></div>
                <div class="card-body p-2">
                    <?php if (empty($model->estadoLogs)): ?>
                        <p class="text-muted text-center small mb-0">Sin historial.</p>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                        <?php foreach ($model->estadoLogs as $log): ?>
                            <li class="border-start border-2 border-primary ps-2 mb-2">
                                <small class="text-muted d-block"><?= $log->created_at ? date('d/m/Y H:i', $log->created_at) : '' ?></small>
                                <span class="fw-bold"><?= Html::encode($transicionesLabels[$log->estado_nuevo] ?? $log->estado_nuevo) ?></span>
                                <?php if ($log->comentario): ?>
                                    <small class="text-muted d-block"><?= Html::encode($log->comentario) ?></small>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
(function () {
    const tabsRoot = document.getElementById('orden-tabs');
    if (!tabsRoot) {
        return;
    }

    const loadTab = async (triggerEl) => {
        const lazyUrl = triggerEl.getAttribute('data-lazy-url');
        if (!lazyUrl) {
            return;
        }

        const targetSelector = triggerEl.getAttribute('data-bs-target') || triggerEl.getAttribute('href');
        if (!targetSelector || !targetSelector.startsWith('#')) {
            return;
        }

        const pane = document.querySelector(targetSelector);
        if (!pane) {
            return;
        }

        const container = pane.querySelector('.tab-lazy-content');
        if (!container || container.dataset.loaded === '1') {
            return;
        }

        try {
            const response = await fetch(lazyUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            container.innerHTML = await response.text();
            container.dataset.loaded = '1';
        } catch (error) {
            container.innerHTML = '<div class="alert alert-danger py-2 mb-0">No se pudo cargar el contenido del tab.</div>';
            console.error('Error cargando tab:', error);
        }
    };

    tabsRoot.querySelectorAll('[data-bs-toggle="tab"]').forEach((triggerEl) => {
        triggerEl.addEventListener('shown.bs.tab', () => loadTab(triggerEl));
    });

    const firstActive = tabsRoot.querySelector('[data-bs-toggle="tab"].active');
    if (firstActive) {
        loadTab(firstActive);
    }
})();
JS
);
?>
