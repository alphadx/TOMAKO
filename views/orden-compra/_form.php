<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\OrdenCompra $model */
/** @var array $proveedores */
?>

<div class="orden-compra-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'numero_orden')->textInput(['maxlength' => true, 'readonly' => true]) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'fecha_emision')->input('date') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'proveedor_id')->dropDownList($proveedores, ['prompt' => 'Seleccionar proveedor...'])->label('Proveedor *') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'fecha_entrega_esperada')->input('date') ?>
        </div>
    </div>

    <?= $form->field($model, 'observaciones')->textarea(['rows' => 4]) ?>

    <div class="form-group">
        <?= Html::submitButton('<i class="fas fa-save"></i> Guardar', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
