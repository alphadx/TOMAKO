<?php
/** @var yii\web\View $this */
/** @var app\models\Tecnico $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Tecnico;
use app\models\Especialidad;
?>

<?php $form = ActiveForm::begin(['id' => 'tecnico-form']); ?>

<div class="row g-3">
    <div class="col-md-5">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 100, 'placeholder' => 'Nombre']) ?>
    </div>
    <div class="col-md-5">
        <?= $form->field($model, 'apellido')->textInput(['maxlength' => 100, 'placeholder' => 'Apellido']) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($model, 'status')->dropDownList(Tecnico::getEstadosList()) ?>
    </div>

    <div class="col-md-3">
        <?= $form->field($model, 'rut')->textInput(['maxlength' => 15, 'placeholder' => '12.345.678-9'])
            ->hint('RUT chileno (opcional).') ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'email')->input('email', ['maxlength' => 150, 'placeholder' => 'correo@ejemplo.com']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'telefono')->textInput(['maxlength' => 25, 'placeholder' => '+56 9 1234 5678']) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($model, 'costo_hora')->input('number', ['min' => 0, 'step' => '0.01', 'placeholder' => '0.00']) ?>
    </div>

    <div class="col-md-6">
        <?= $form->field($model, 'especialidad_id')->dropDownList(
            Especialidad::getEspecialidadesList(),
            ['prompt' => '— Sin especialidad —']
        ) ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Registrar Técnico' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
