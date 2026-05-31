<?php
/** @var yii\web\View $this */
/** @var app\models\search\ClienteSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array{total:int,activos:int,nuevos_mes:int} $stats */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Cliente;

$this->title = 'Clientes';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cliente-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-download me-1"></i>Exportar CSV', ['export'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Cliente', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-primary"><?= $stats['total'] ?></div>
                    <div class="text-muted small">Total Clientes</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-success"><?= $stats['activos'] ?></div>
                    <div class="text-muted small">Clientes Activos</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-info"><?= $stats['nuevos_mes'] ?></div>
                    <div class="text-muted small">Nuevos este Mes</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de búsqueda -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre...'])->label('Nombre') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'email')->textInput(['placeholder' => 'correo@...'])->label('Correo') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'telefono')->textInput(['placeholder' => 'Teléfono...'])->label('Teléfono') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Todos', '1' => 'Activo', '0' => 'Inactivo',
                    ])->label('Estado') ?>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary w-100 ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'nombre',
            'email:email',
            'telefono',
            [
                'label' => 'RUT',
                'value' => fn($model) => $model->rut ?: '—',
            ],
            [
                'label'   => 'Estado',
                'format'  => 'raw',
                'content' => fn($model) => $model->status
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>',
            ],
            [
                'label' => 'Creado',
                'value' => fn($model) => $model->created_at ? date('d/m/Y', $model->created_at) : '—',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {deactivate}',
                'buttons'  => [
                    'view' => fn($url, $model) => Html::a(
                        '<i class="bi bi-eye"></i><span>Ver</span>',
                        ['view', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']
                    ),
                    'update' => fn($url, $model) => Html::a(
                        '<i class="bi bi-pencil"></i><span>Editar</span>',
                        ['update', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']
                    ),
                    'deactivate' => fn($url, $model) => $model->status
                        ? Html::a(
                            '<i class="bi bi-person-x"></i><span>Desactivar</span>',
                            ['deactivate', 'id' => $model->id],
                            [
                                'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                'title'        => 'Desactivar',
                                'data-method'  => 'post',
                                'data-confirm' => '¿Desactivar este cliente?',
                            ]
                        )
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>
