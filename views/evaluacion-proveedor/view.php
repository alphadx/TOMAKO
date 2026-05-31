<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/**
 * @var yii\web\View $this
 * @var app\models\EvaluacionProveedor $model
 */

$this->title = $model->proveedor->nombre ?? 'Evaluación #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Evaluaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="evaluacion-proveedor-view">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Eliminar', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-confirm' => '¿Está seguro de eliminar esta evaluación?',
            'data-method' => 'post',
        ]) ?>
        <?= Html::a('Volver al Listado', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <div class="card">
        <div class="card-body">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'id',
                    [
                        'attribute' => 'proveedor_id',
                        'value' => $model->proveedor->nombre ?? '',
                    ],
                    [
                        'attribute' => 'orden_compra_id',
                        'value' => $model->ordenCompra ? $model->ordenCompra->numero_orden : '-',
                    ],
                    'fecha_evaluacion:date',
                    [
                        'attribute' => 'puntualidad',
                        'format' => 'raw',
                        'value' => $model->puntualidad ? "<span class=\"badge bg-" . ($model->puntualidad >= 4 ? 'success' : 'warning') . "\">{$model->puntualidad}/5</span>" : '-',
                    ],
                    [
                        'attribute' => 'calidad_producto',
                        'format' => 'raw',
                        'value' => $model->calidad_producto ? "<span class=\"badge bg-" . ($model->calidad_producto >= 4 ? 'success' : 'warning') . "\">{$model->calidad_producto}/5</span>" : '-',
                    ],
                    [
                        'attribute' => 'atencion_servicio',
                        'format' => 'raw',
                        'value' => $model->atencion_servicio ? "<span class=\"badge bg-" . ($model->atencion_servicio >= 4 ? 'success' : 'warning') . "\">{$model->atencion_servicio}/5</span>" : '-',
                    ],
                    [
                        'attribute' => 'precio_competitividad',
                        'format' => 'raw',
                        'value' => $model->precio_competitividad ? "<span class=\"badge bg-" . ($model->precio_competitividad >= 4 ? 'success' : 'warning') . "\">{$model->precio_competitividad}/5</span>" : '-',
                    ],
                    [
                        'attribute' => 'flexibilidad',
                        'format' => 'raw',
                        'value' => $model->flexibilidad ? "<span class=\"badge bg-" . ($model->flexibilidad >= 4 ? 'success' : 'warning') . "\">{$model->flexibilidad}/5</span>" : '-',
                    ],
                    [
                        'attribute' => 'puntaje_total',
                        'format' => 'decimal',
                    ],
                    [
                        'attribute' => 'puntaje_promedio',
                        'format' => 'raw',
                        'value' => $model->puntaje_promedio ? "<strong>" . number_format($model->puntaje_promedio, 2) . "/5.0</strong>" : '-',
                    ],
                    'comentarios:ntext',
                    [
                        'attribute' => 'evaluado_por',
                        'value' => $model->evaluadoPor ? $model->evaluadoPor->username : '-',
                    ],
                    'created_at:datetime',
                    'updated_at:datetime',
                ],
            ]) ?>
        </div>
    </div>
</div>
