<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\grid\GridView;
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
                'value' => fn($model) => $model->cliente->nombre ?? 'N/A',
                'filter' => false,
            ],
            [
                'attribute' => 'vehiculo_id',
                'label' => 'Vehículo',
                'value' => fn($model) => ($model->vehiculo->patente ?? 'N/A'),
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
                            ? Html::a(
                                '<i class="bi bi-arrow-right"></i>',
                                '#',
                                ['class' => 'btn btn-sm btn-warning', 'data-bs-toggle' => 'modal', 'data-bs-target' => '#modal-cambio-estado']
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
