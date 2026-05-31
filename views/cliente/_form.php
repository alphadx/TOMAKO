<?php
/** @var yii\web\View $this */
/** @var app\models\Cliente $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Cliente;
?>

<?php $form = ActiveForm::begin(['id' => 'cliente-form']); ?>

<div class="row g-3">
    <div class="col-md-8">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 150, 'placeholder' => 'Nombre del cliente']) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList(Cliente::getEstadosList()) ?>
    </div>

    <div class="col-md-6">
        <?= $form->field($model, 'email')->input('email', ['maxlength' => 150, 'placeholder' => 'correo@ejemplo.com']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'telefono')->textInput(['maxlength' => 25, 'placeholder' => '+56 9 1234 5678']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'rut')->textInput([
            'maxlength' => 15, 
            'placeholder' => '12.345.678-9',
            'id' => 'cliente-rut',
            'class' => 'form-control rut-input'
        ])->hint('RUT chileno con dígito verificador') ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'direccion')->textarea(['rows' => 2, 'placeholder' => 'Dirección del cliente...']) ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'notas')->textarea(['rows' => 3, 'placeholder' => 'Notas internas...']) ?>
    </div>

    <div class="col-md-6">
        <?= $form->field($model, 'cumpleanos')->input('date', ['max' => date('Y-m-d')]) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'fuente')->dropDownList(Cliente::getFuentesList(), ['prompt' => 'Seleccione...']) ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'preferencias')->textarea(['rows' => 2, 'placeholder' => 'Preferencias de contacto, horarios, etc...']) ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Cliente' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
