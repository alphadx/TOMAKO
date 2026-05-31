<?php
/** @var yii\web\View $this */
/** @var app\models\Especialidad $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Especialidad;
?>

<?php $form = ActiveForm::begin(['id' => 'especialidad-form']); ?>

<div class="row g-3">
    <div class="col-md-8">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 100, 'placeholder' => 'Nombre de la especialidad']) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList(Especialidad::getEstadosList()) ?>
    </div>
    <div class="col-md-12">
        <?= $form->field($model, 'descripcion')->textarea(['rows' => 3, 'placeholder' => 'Descripción de la especialidad...']) ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Especialidad' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
