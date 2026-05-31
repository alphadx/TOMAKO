<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use app\models\EvaluacionProveedor;

/**
 * @var yii\web\View $this
 * @var app\models\search\EvaluacionProveedorSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var int $totalEvaluaciones
 * @var float $promedioGeneral
 * @var array $mejoresProveedores
 */

$this->title = 'Evaluaciones de Proveedores';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="evaluacion-proveedor-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Crear Evaluación', ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a('Reporte por Período', ['reporte'], ['class' => 'btn btn-info']) ?>
    </p>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Evaluaciones</h5>
                    <h2 class="mb-0"><?= $totalEvaluaciones ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Promedio General</h5>
                    <h2 class="mb-0"><?= number_format($promedioGeneral, 2) ?>/5.0</h2>
                </div>
            </div>
        </div>
        <div class="col-lg-6 col-md-12">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Mejores Proveedores del Mes</h5>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($mejoresProveedores as $index => $prov): ?>
                            <li>
                                <strong>#<?= $index + 1 ?>:</strong> 
                                <?= $prov->proveedor->nombre ?? 'N/A' ?> - 
                                <span class="badge bg-warning"><?= number_format($prov->puntaje, 2) ?>/5.0</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php Pjax::begin(); ?>
    
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            
            [
                'attribute' => 'proveedor_id',
                'label' => 'Proveedor',
                'value' => function($model) {
                    return $model->proveedor->nombre ?? '';
                },
                'filter' => \app\models\Proveedor::getListaParaDropdown(),
            ],
            
            [
                'attribute' => 'orden_compra_id',
                'label' => 'Orden Compra',
                'value' => function($model) {
                    return $model->ordenCompra ? $model->ordenCompra->numero_orden : '-';
                },
            ],
            
            'fecha_evaluacion:date',
            
            [
                'attribute' => 'puntualidad',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->puntualidad === null) return '-';
                    $class = $model->puntualidad >= 4 ? 'success' : ($model->puntualidad >= 3 ? 'warning' : 'danger');
                    return "<span class=\"badge bg-{$class}\">{$model->puntualidad}/5</span>";
                },
            ],
            
            [
                'attribute' => 'calidad_producto',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->calidad_producto === null) return '-';
                    $class = $model->calidad_producto >= 4 ? 'success' : ($model->calidad_producto >= 3 ? 'warning' : 'danger');
                    return "<span class=\"badge bg-{$class}\">{$model->calidad_producto}/5</span>";
                },
            ],
            
            [
                'attribute' => 'puntaje_promedio',
                'format' => 'raw',
                'value' => function($model) {
                    if ($model->puntaje_promedio === null) return '-';
                    $class = $model->puntaje_promedio >= 4 ? 'success' : ($model->puntaje_promedio >= 3 ? 'warning' : 'danger');
                    return "<strong><span class=\"text-{$class}\">" . number_format($model->puntaje_promedio, 2) . "/5.0</span></strong>";
                },
                'filter' => false,
            ],
            
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, [
                            'title' => 'Ver',
                            'class' => 'btn btn-sm btn-outline-primary',
                        ]);
                    },
                    'update' => function($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-pencil"></span>', $url, [
                            'title' => 'Editar',
                            'class' => 'btn btn-sm btn-outline-warning',
                        ]);
                    },
                    'delete' => function($url, $model) {
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, [
                            'title' => 'Eliminar',
                            'class' => 'btn btn-sm btn-outline-danger',
                            'data-confirm' => '¿Está seguro de eliminar esta evaluación?',
                            'data-method' => 'post',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>
    
    <?php Pjax::end(); ?>
</div>
