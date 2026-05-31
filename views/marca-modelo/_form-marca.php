<?php
/** @var yii\web\View $this */
/** @var app\models\Marca $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Nueva Marca';
$this->params['breadcrumbs'][] = ['label' => 'Marcas y Modelos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="marca-form">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-car-front-fill me-2"></i><?= Html::encode($this->title) ?></h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'marca-form',
                'options' => ['class' => 'needs-validation'],
            ]); ?>

            <?= $form->field($model, 'nombre')->textInput([
                'maxlength' => true,
                'placeholder' => 'Ej: TOYOTA',
                'autofocus' => true,
            ]) ?>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <small>El nombre de la marca se guardará automáticamente en mayúsculas.</small>
            </div>

            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="bi bi-check-lg me-1"></i>Guardar', [
                    'class' => 'btn btn-success',
                ]) ?>
                <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], [
                    'class' => 'btn btn-secondary',
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
