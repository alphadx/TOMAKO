<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use yii\widgets\Pjax;
use app\models\OrdenServicio;

/** @var yii\web\View $this */
/** @var app\models\search\OrdenServicioSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $kpis */

$this->title = 'Órdenes de Servicio';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-servicio-index">
    <?php Pjax::begin(['enablePushState' => false]) ?>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Trabajos Activos</h5>
                    <p class="card-text display-4"><?= $kpis['activos'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Listos para Entrega</h5>
                    <p class="card-text display-4"><?= $kpis['listos'] ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Entregadas</h5>
                    <p class="card-text display-4"><?= $kpis['pendientes'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <?= Html::textInput('codigo', $searchModel->codigo, [
                        'class' => 'form-control',
                        'placeholder' => 'Buscar por código JOB...',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= Html::dropDownList('estado', $searchModel->estado, [
                        '' => 'Todos los Estados',
                        'abierto' => 'Abierto',
                        'en_progreso' => 'En Progreso',
                        'esperando_repuestos' => 'Esperando Repuestos',
                        'listo_para_entrega' => 'Listo para Entrega',
                        'entregada' => 'Entregada',
                        'cancelada' => 'Cancelada',
                    ], ['class' => 'form-control']) ?>
                </div>
                <div class="col-md-3">
                    <?= Html::dropDownList('prioridad', $searchModel->prioridad, [
                        '' => 'Todas las Prioridades',
                        'baja' => 'Baja',
                        'normal' => 'Normal',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ], ['class' => 'form-control']) ?>
                </div>
                <div class="col-md-2">
                    <?= Html::button('Buscar', [
                        'class' => 'btn btn-primary w-100',
                        'onclick' => 'jQuery("#form").submit();',
                    ]) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'codigo',
                'format' => 'raw',
                'value' => fn($model) => Html::a($model->codigo, ['view', 'id' => $model->id], ['class' => 'text-decoration-none']),
            ],
            [
                'attribute' => 'cliente_id',
                'label' => 'Cliente',
                'value' => fn($model) => $model->cliente?->nombre ?? 'N/A',
                'filter' => false,
            ],
            [
                'attribute' => 'vehiculo_id',
                'label' => 'Vehículo',
                'value' => fn($model) => $model->vehiculo?->patente ?? 'N/A',
                'filter' => false,
            ],
            [
                'attribute' => 'estado',
                'format' => 'raw',
                'value' => fn($model) => $model->getEstadoBadgeClass(),
            ],
            [
                'attribute' => 'prioridad',
                'format' => 'raw',
                'value' => fn($model) => $model->getPrioridadBadge(),
            ],
            [
                'attribute' => 'total',
                'format' => ['currency', 'COP'],
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {cambiar} {cerrar}',
                'buttons' => [
                    'view' => fn($url, $model) => Html::a(
                        '<i class="bi bi-eye"></i>',
                        ['view', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-info']
                    ),
                    'cambiar' => fn($url, $model) => (
                        in_array($model->estado, ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega'])
                            ? Html::button(
                                '<i class="bi bi-arrow-right"></i>',
                                [
                                    'type' => 'button',
                                    'class' => 'btn btn-sm btn-warning',
                                    'data-bs-toggle' => 'modal',
                                    'data-bs-target' => '#modal-cambio-estado',
                                    'data-orden-id' => $model->id,
                                    'data-orden-codigo' => $model->codigo,
                                    'data-change-url' => Url::to(['cambiar-estado', 'id' => $model->id]),
                                    'data-estados' => Json::encode($model->getEstadosDisponibles()),
                                    'title' => 'Cambiar estado',
                                ]
                            )
                            : ''
                    ),
                    'cerrar' => fn($url, $model) => (
                        $model->estado === 'listo_para_entrega'
                            ? Html::a(
                                '<i class="bi bi-check-circle"></i>',
                                ['ver-cerrar', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-success']
                            )
                            : ''
                    ),
                ],
            ],
        ],
    ]) ?>

    <?php Pjax::end() ?>
</div>

<!-- Add Button -->
<div class="mt-3">
    <?= Html::a('+ Nueva Orden', ['create'], ['class' => 'btn btn-primary btn-lg']) ?>
</div>

<div class="modal fade" id="modal-cambio-estado" tabindex="-1" aria-labelledby="modal-cambio-estado-label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <?php $form = ActiveForm::begin([
                'id' => 'form-cambio-estado',
                'action' => ['cambiar-estado', 'id' => 0],
                'method' => 'post',
            ]); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-cambio-estado-label">Cambiar estado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3" id="modal-cambio-estado-info">Selecciona el nuevo estado para la orden.</p>

                    <div class="mb-3">
                        <label class="form-label" for="modal-cambio-estado-estado">Estado</label>
                        <select class="form-select" name="estado" id="modal-cambio-estado-estado" required>
                            <option value="">Selecciona un estado</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="modal-cambio-estado-motivo">Motivo</label>
                        <textarea class="form-control" name="motivo" id="modal-cambio-estado-motivo" rows="3" placeholder="Motivo del cambio de estado"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?= Html::submitButton('Guardar cambio', ['class' => 'btn btn-warning']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$js = <<<'JS'
(() => {
    const modal = document.getElementById('modal-cambio-estado');
    if (!modal) {
        return;
    }

    const form = modal.querySelector('#form-cambio-estado');
    const title = modal.querySelector('#modal-cambio-estado-label');
    const info = modal.querySelector('#modal-cambio-estado-info');
    const select = modal.querySelector('#modal-cambio-estado-estado');
    const motivo = modal.querySelector('#modal-cambio-estado-motivo');

    modal.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) {
            return;
        }

        const actionUrl = trigger.getAttribute('data-change-url') || '';
        const ordenCodigo = trigger.getAttribute('data-orden-codigo') || '';
        const estados = JSON.parse(trigger.getAttribute('data-estados') || '{}');

        form.action = actionUrl;
        title.textContent = ordenCodigo ? 'Cambiar estado de ' + ordenCodigo : 'Cambiar estado';
        info.textContent = ordenCodigo
            ? 'Selecciona el nuevo estado para la orden ' + ordenCodigo + '.'
            : 'Selecciona el nuevo estado para la orden.';

        select.innerHTML = '<option value="">Selecciona un estado</option>';
        Object.entries(estados).forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            select.appendChild(option);
        });

        select.value = '';
        motivo.value = '';
    });
})();
JS;

$this->registerJs($js);
?>
