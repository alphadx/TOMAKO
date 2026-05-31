<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Proveedor */

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Proveedores', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="proveedor-view">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4><i class="fas fa-building"></i> <?= Html::encode($this->title) ?></h4>
                    <div>
                        <?= Html::a('<i class="fas fa-edit"></i> Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?php if (!$model->activo): ?>
                            <?= Html::a('<i class="fas fa-check"></i> Activar', ['activar', 'id' => $model->id], [
                                'class' => 'btn btn-success',
                                'data-method' => 'post',
                                'data-confirm' => '¿Está seguro de activar este proveedor?',
                            ]) ?>
                        <?php else: ?>
                            <?= Html::a('<i class="fas fa-times"></i> Desactivar', ['delete', 'id' => $model->id], [
                                'class' => 'btn btn-warning',
                                'data-method' => 'post',
                                'data-confirm' => '¿Está seguro de desactivar este proveedor? (No se eliminará, solo se marcará como inactivo)',
                            ]) ?>
                        <?php endif; ?>
                        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5><i class="fas fa-info-circle"></i> Información General</h5>
                        </div>
                        <div class="col-md-6 text-right">
                            <?= $model->activo 
                                ? '<span class="badge badge-success">Activo</span>' 
                                : '<span class="badge badge-danger">Inactivo</span>' 
                            ?>
                        </div>
                    </div>

                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'nombre',
                            'rut',
                            'categoria',
                            'email:email',
                            'telefono',
                            'celular',
                            'direccion:ntext',
                            'ciudad',
                            'region',
                            'pais',
                            'codigo_postal',
                            'sitio_web:url',
                        ],
                        'options' => ['class' => 'table table-bordered'],
                    ]); ?>

                    <h5><i class="fas fa-user-tie"></i> Información de Contacto</h5>
                    
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'persona_contacto',
                            'cargo_contacto',
                        ],
                        'options' => ['class' => 'table table-bordered'],
                    ]); ?>

                    <h5><i class="fas fa-chart-line"></i> Información Comercial</h5>
                    
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'tiempo_entrega_promedio',
                            [
                                'attribute' => 'calificacion',
                                'format' => ['decimal', 2],
                                'label' => 'Calificación',
                            ],
                            'activo:boolean',
                            'observaciones:ntext',
                            'created_at:datetime',
                            'updated_at:datetime',
                            [
                                'attribute' => 'created_by',
                                'value' => function($model) {
                                    return $model->createdBy ? $model->createdBy->username : '-';
                                },
                            ],
                            [
                                'attribute' => 'updated_by',
                                'value' => function($model) {
                                    return $model->updatedBy ? $model->updatedBy->username : '-';
                                },
                            ],
                        ],
                        'options' => ['class' => 'table table-bordered'],
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-header h4 {
    margin: 0;
}
.card-header .btn {
    margin-left: 5px;
}
.detail-view .table {
    margin-bottom: 0;
}
</style>
