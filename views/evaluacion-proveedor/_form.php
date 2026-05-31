<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/**
 * @var yii\web\View $this
 * @var app\models\EvaluacionProveedor $model
 * @var array $proveedores
 * @var array $listaOrdenes
 */
?>

<div class="evaluacion-proveedor-form">

    <?php $form = ActiveForm::begin(); ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'proveedor_id')->dropDownList($proveedores, ['prompt' => 'Seleccione...']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'orden_compra_id')->dropDownList($listaOrdenes, ['prompt' => 'Seleccione (opcional)...']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'fecha_evaluacion')->widget(DatePicker::classname(), [
                'language' => 'es',
                'dateFormat' => 'yyyy-MM-dd',
                'options' => ['class' => 'form-control'],
            ]) ?>
        </div>
    </div>

    <h4 class="mt-4">Métricas de Desempeño (1-5)</h4>
    
    <div class="row">
        <div class="col-md-4">
            <?= $form->field($model, 'puntualidad')->dropDownList([
                1 => '1 - Muy Deficiente',
                2 => '2 - Deficiente',
                3 => '3 - Regular',
                4 => '4 - Bueno',
                5 => '5 - Excelente',
            ], ['prompt' => 'Seleccione...']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'calidad_producto')->dropDownList([
                1 => '1 - Muy Deficiente',
                2 => '2 - Deficiente',
                3 => '3 - Regular',
                4 => '4 - Bueno',
                5 => '5 - Excelente',
            ], ['prompt' => 'Seleccione...']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'atencion_servicio')->dropDownList([
                1 => '1 - Muy Deficiente',
                2 => '2 - Deficiente',
                3 => '3 - Regular',
                4 => '4 - Bueno',
                5 => '5 - Excelente',
            ], ['prompt' => 'Seleccione...']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'precio_competitividad')->dropDownList([
                1 => '1 - Muy Deficiente',
                2 => '2 - Deficiente',
                3 => '3 - Regular',
                4 => '4 - Bueno',
                5 => '5 - Excelente',
            ], ['prompt' => 'Seleccione...']) ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'flexibilidad')->dropDownList([
                1 => '1 - Muy Deficiente',
                2 => '2 - Deficiente',
                3 => '3 - Regular',
                4 => '4 - Bueno',
                5 => '5 - Excelente',
            ], ['prompt' => 'Seleccione...']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <?= $form->field($model, 'comentarios')->textarea(['rows' => 4]) ?>
        </div>
    </div>

    <div class="form-group mt-3">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
