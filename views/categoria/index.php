<?php
/** @var yii\web\View $this */
/** @var app\models\search\CategoriaSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Categoria;

$this->title = 'Categorías';
$this->params['breadcrumbs'][] = $this->title;

$tiposBadge = [
    'servicio' => '<span class="badge bg-primary">Servicio</span>',
    'insumo'   => '<span class="badge bg-warning text-dark">Insumo</span>',
    'ambos'    => '<span class="badge bg-secondary">Ambos</span>',
];
?>

<div class="categoria-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-tags me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Categoría', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre...'])->label('Nombre') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'tipo')->dropDownList(
                        ['' => 'Todos los tipos'] + Categoria::getTiposList()
                    )->label('Tipo') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Todos', '1' => 'Activo', '0' => 'Inactivo',
                    ])->label('Estado') ?>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
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
            [
                'label'   => 'Nombre',
                'format'  => 'raw',
                'content' => function ($model) {
                    $nivel  = 0;
                    $p = $model->padre;
                    while ($p) { $nivel++; $p = $p->padre; }
                    $indent = str_repeat('<span class="ms-3 text-muted">— </span>', $nivel);
                    $icono  = $model->icono ? '<span class="me-1">' . Html::encode($model->icono) . '</span>' : '';
                    return $indent . $icono . Html::encode($model->nombre);
                },
            ],
            [
                'label'   => 'Tipo',
                'format'  => 'raw',
                'content' => function ($model) use ($tiposBadge) {
                    return $tiposBadge[$model->tipo] ?? Html::encode($model->tipo);
                },
            ],
            [
                'label'   => 'Padre',
                'value'   => fn($model) => $model->padre ? $model->padre->nombre : '—',
            ],
            [
                'label'  => 'Orden',
                'value'  => fn($model) => $model->orden,
            ],
            [
                'label'  => 'Items',
                'format' => 'raw',
                'value'  => function ($model) {
                    $total = (int) $model->getServicios()->count() + (int) $model->getInventoryItems()->count();
                    return $total > 0
                        ? '<span class="badge bg-primary">' . $total . '</span>'
                        : '<span class="badge bg-secondary">0</span>';
                },
            ],
            [
                'label'   => 'Estado',
                'format'  => 'raw',
                'content' => fn($model) => $model->status
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {deactivate} {delete}',
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
                            '<i class="bi bi-x-circle"></i><span>Desactivar</span>',
                            ['deactivate', 'id' => $model->id],
                            [
                                'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                'title'        => 'Desactivar',
                                'data-method'  => 'post',
                                'data-confirm' => '¿Desactivar esta categoría?',
                            ]
                        )
                        : '',
                    'delete' => fn($url, $model) => Html::a(
                        '<i class="bi bi-trash"></i><span>Eliminar</span>',
                        ['delete', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                            'title'        => 'Eliminar',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Eliminar esta categoría? Solo se permite si está vacía.',
                        ]
                    ),
                ],
            ],
        ],
    ]); ?>
</div>
