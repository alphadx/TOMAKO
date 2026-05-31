<?php
/** @var yii\web\View $this */
/** @var app\models\search\OrdenServicioSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array $kpis */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\OrdenServicio;

$this->title = 'Órdenes de Servicio';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-file-text me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Orden', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card text-center border-secondary shadow-sm">
                <div class="card-body py-2">
                    <div class="display-6 fw-bold text-secondary"><?= $kpis['activos'] ?></div>
                    <small class="text-muted">Activas</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-info shadow-sm">
                <div class="card-body py-2">
                    <div class="display-6 fw-bold text-info"><?= $kpis['en_progreso'] ?></div>
                    <small class="text-muted">En Progreso</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-primary shadow-sm">
                <div class="card-body py-2">
                    <div class="display-6 fw-bold text-primary"><?= $kpis['listo_para_entrega'] ?></div>
                    <small class="text-muted">Listos para Entregar</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-center border-success shadow-sm">
                <div class="card-body py-2">
                    <div class="display-6 fw-bold text-success"><?= $kpis['entregadas_hoy'] ?></div>
                    <small class="text-muted">Entregadas Hoy</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'buscar')->textInput(['placeholder' => 'Código, cliente...'])->label('Buscar') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'estado')->dropDownList(
                        array_merge(['' => 'Todos'], OrdenServicio::getEstadosList())
                    )->label('Estado') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'prioridad')->dropDownList(
                        array_merge(['' => 'Todas'], OrdenServicio::getPrioridadesList())
                    )->label('Prioridad') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_desde')->input('date')->label('Desde') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_hasta')->input('date')->label('Hasta') ?>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1 ts-filter-actions">
                    <button type="submit" class="btn btn-primary ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'attribute' => 'codigo',
                'format'    => 'raw',
                'value'     => fn($m) => Html::a('<span class="badge bg-dark">' . Html::encode($m->codigo) . '</span>', ['view', 'id' => $m->id]),
            ],
            [
                'label'  => 'Cliente',
                'format' => 'raw',
                'value'  => fn($m) => $m->cliente
                    ? Html::a(Html::encode($m->cliente->nombre), ['/cliente/view', 'id' => $m->cliente_id])
                    : '—',
            ],
            [
                'label'  => 'Vehículo',
                'format' => 'raw',
                'value'  => fn($m) => $m->vehiculo
                    ? '<span class="badge bg-secondary me-1">' . Html::encode($m->vehiculo->patente) . '</span>'
                    : '—',
            ],
            [
                'attribute' => 'estado',
                'format'    => 'raw',
                'value'     => fn($m) => '<span class="badge ' . $m->getEstadoBadgeClass() . '">' . Html::encode(OrdenServicio::getEstadosList()[$m->estado] ?? $m->estado) . '</span>',
            ],
            [
                'attribute' => 'prioridad',
                'format'    => 'raw',
                'value'     => fn($m) => '<span class="badge ' . $m->getPrioridadBadgeClass() . '">' . Html::encode(OrdenServicio::getPrioridadesList()[$m->prioridad] ?? $m->prioridad) . '</span>',
            ],
            [
                'attribute' => 'total',
                'value'     => fn($m) => '$ ' . number_format($m->total, 0, ',', '.'),
            ],
            [
                'attribute' => 'created_at',
                'value'     => fn($m) => $m->created_at ? date('d/m/Y', $m->created_at) : '—',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
                'buttons'  => [
                    'view'   => fn($url, $m) => Html::a('<i class="bi bi-eye"></i><span>Ver</span>',    ['view',   'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']),
                    'update' => fn($url, $m) => $m->estado === 'abierto'
                        ? Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar'])
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>
