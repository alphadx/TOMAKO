<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Proveedor */
/* @var $form ActiveForm */

$this->title = 'Actualizar Proveedor: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Proveedores', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Actualizar';
?>

<div class="proveedor-update">
    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-edit"></i> Editar Proveedor</h4>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin(); ?>

            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'rut')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'categoria')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'type' => 'email']) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'telefono')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($model, 'celular')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'direccion')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'ciudad')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'region')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'pais')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'codigo_postal')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($model, 'sitio_web')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <hr>

            <h5><i class="fas fa-user-tie"></i> Información de Contacto</h5>
            
            <div class="row">
                <div class="col-md-6">
                    <?= $form->field($model, 'persona_contacto')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'cargo_contacto')->textInput(['maxlength' => true]) ?>
                </div>
            </div>

            <hr>

            <h5><i class="fas fa-chart-line"></i> Información Comercial</h5>

            <div class="row">
                <div class="col-md-3">
                    <?= $form->field($model, 'tiempo_entrega_promedio')->textInput(['type' => 'number', 'min' => 0]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'calificacion')->textInput(['type' => 'number', 'min' => 0, 'max' => 5, 'step' => 0.1]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'activo')->checkbox() ?>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <?= $form->field($model, 'observaciones')->textarea(['rows' => 4]) ?>
                </div>
            </div>

            <div class="form-group mt-4">
                <?= Html::submitButton('<i class="fas fa-save"></i> Actualizar Proveedor', ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="fas fa-times"></i> Cancelar', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
