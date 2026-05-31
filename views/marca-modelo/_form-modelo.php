<?php
/** @var yii\web\View $this */
/** @var app\models\Modelo $model */
/** @var app\models\Marca $marca */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Nuevo Modelo';
$this->params['breadcrumbs'][] = ['label' => 'Marcas y Modelos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $marca->nombre, 'url' => ['view', 'id' => $marca->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="modelo-form">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-car-front-fill me-2"></i><?= Html::encode($this->title) ?></h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'id' => 'modelo-form',
                'options' => ['class' => 'needs-validation'],
            ]); ?>

            <?= $form->field($model, 'marca_id')->hiddenInput()->value($marca->id)->label(false) ?>

            <div class="mb-3">
                <label class="form-label">Marca</label>
                <input type="text" class="form-control" value="<?= Html::encode($marca->nombre) ?>" disabled>
            </div>

            <?= $form->field($model, 'nombre')->textInput([
                'maxlength' => true,
                'placeholder' => 'Ej: COROLLA',
                'autofocus' => true,
            ]) ?>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <small>El nombre del modelo se guardará automáticamente en mayúsculas.</small>
            </div>

            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="bi bi-check-lg me-1"></i>Guardar', [
                    'class' => 'btn btn-success',
                ]) ?>
                <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['view', 'id' => $marca->id], [
                    'class' => 'btn btn-secondary',
                ]) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
