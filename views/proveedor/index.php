<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\Proveedor;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ProveedorSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $totalProveedores integer */
/* @var $proveedoresActivos integer */
/* @var $proveedoresInactivos integer */
/* @var $calificacionPromedio float */

$this->title = 'Gestión de Proveedores';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="proveedor-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Proveedores</h5>
                    <h2 class="mb-0"><?= $totalProveedores ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Proveedores Activos</h5>
                    <h2 class="mb-0"><?= $proveedoresActivos ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Proveedores Inactivos</h5>
                    <h2 class="mb-0"><?= $proveedoresInactivos ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Calificación Promedio</h5>
                    <h2 class="mb-0"><?= number_format($calificacionPromedio, 2) ?> / 5.0</h2>
                </div>
            </div>
        </div>
    </div>

    <p>
        <?= Html::a('<i class="fas fa-plus"></i> Nuevo Proveedor', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php Pjax::begin(); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'nombre',
                'format' => 'raw',
                'value' => function($model) {
                    return Html::a(Html::encode($model->nombre), ['view', 'id' => $model->id]);
                },
            ],
            'rut',
            'email:email',
            'telefono',
            [
                'attribute' => 'categoria',
                'filter' => Html::activeDropDownList(
                    $searchModel, 
                    'categoria', 
                    array_merge(['' => 'Todas'], Proveedor::find()->select('categoria')->distinct()->column()),
                    ['class' => 'form-control']
                ),
            ],
            [
                'attribute' => 'calificacion',
                'format' => ['decimal', 2],
                'contentOptions' => ['class' => 'text-center'],
            ],
            [
                'attribute' => 'activo',
                'format' => 'boolean',
                'filter' => ['No', 'Sí'],
                'contentOptions' => ['class' => 'text-center'],
            ],
            'created_at:datetime',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete} {activar}',
                'buttons' => [
                    'activar' => function($url, $model) {
                        if (!$model->activo) {
                            return Html::a('<i class="fas fa-check"></i>', ['activar', 'id' => $model->id], [
                                'class' => 'btn btn-sm btn-success',
                                'title' => 'Activar proveedor',
                                'data-confirm' => '¿Está seguro de activar este proveedor?',
                                'data-method' => 'post',
                            ]);
                        }
                        return '';
                    },
                ],
                'visibleButtons' => [
                    'delete' => Yii::$app->user->can('admin') || Yii::$app->user->can('gerente'),
                ],
            ],
        ],
        'pager' => [
            'class' => \yii\bootstrap4\LinkPager::class,
        ],
        'tableOptions' => ['class' => 'table table-striped table-hover'],
    ]); ?>

    <?php Pjax::end(); ?>
</div>

<style>
.card {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.card-body h5 {
    font-size: 0.9rem;
    opacity: 0.9;
}
.card-body h2 {
    font-weight: bold;
}
</style>
