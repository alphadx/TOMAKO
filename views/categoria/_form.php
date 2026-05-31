<?php
/** @var yii\web\View $this */
/** @var app\models\Categoria $model */
/** @var array $tree   Array id=>nombre para el dropdown de padre */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Categoria;
?>

<?php $form = ActiveForm::begin(['id' => 'categoria-form']); ?>

<div class="row g-3">
    <div class="col-md-8">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 100, 'placeholder' => 'Nombre de la categoría']) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList(['1' => 'Activo', '0' => 'Inactivo']) ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'descripcion')->textarea(['rows' => 3, 'placeholder' => 'Descripción opcional...']) ?>
    </div>

    <div class="col-md-4">
        <?= $form->field($model, 'padre_id')->dropDownList(
            $tree,
            ['prompt' => '— Sin padre (raíz) —']
        ) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'tipo')->dropDownList(Categoria::getTiposList()) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($model, 'orden')->input('number', ['min' => 0, 'placeholder' => '0']) ?>
    </div>

    <div class="col-md-3">
        <?= $form->field($model, 'icono')->textInput(['maxlength' => 50, 'placeholder' => 'Ej: 🔧 o bi-gear']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'color')->input('color')->hint('Color representativo') ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Categoría' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
