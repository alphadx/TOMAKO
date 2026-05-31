<?php
/** @var yii\web\View $this */
/** @var app\models\Servicio $model */
/** @var array $categorias */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<?php $form = ActiveForm::begin(['id' => 'servicio-form']); ?>

<div class="row g-3">
    <div class="col-md-3">
        <?= $form->field($model, 'codigo')->textInput(['maxlength' => 20, 'placeholder' => 'S-0001']) ?>
    </div>
    <div class="col-md-7">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 150, 'placeholder' => 'Nombre del servicio']) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($model, 'status')->dropDownList(['1' => 'Activo', '0' => 'Inactivo']) ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'descripcion')->textarea([
            'rows' => 3,
            'maxlength' => 500,
            'placeholder' => 'Descripción opcional...',
        ])->hint('Máximo 500 caracteres') ?>
    </div>

    <div class="col-md-4">
        <?= $form->field($model, 'categoria_id')->dropDownList(
            $categorias,
            ['prompt' => 'Seleccione categoría...']
        ) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'precio_base')->input('number', [
            'min' => '0', 'step' => '0.01', 'placeholder' => '0.00',
        ])->hint('Precio en pesos chilenos') ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'duracion_estimada')->input('number', [
            'min' => '0', 'placeholder' => 'Minutos',
        ])->hint('Duración estimada en minutos') ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Servicio' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
