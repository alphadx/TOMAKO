<?php
/** @var yii\web\View $this */
/** @var app\models\PlantillaNotificacion $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\PlantillaNotificacion;

$this->title = 'Editar Plantilla: ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Notificaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Plantillas', 'url' => ['plantillas']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-plantilla-form" style="max-width:900px;">
    <h1 class="h4 mb-3"><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>
    <div class="row g-3">
        <div class="col-md-4">
            <?= $form->field($model, 'codigo')->textInput(['readonly' => true]) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'canal')->dropDownList([
                PlantillaNotificacion::CANAL_EMAIL => 'Email',
                PlantillaNotificacion::CANAL_INTERNO => 'Interno',
                PlantillaNotificacion::CANAL_AMBOS => 'Ambos',
            ], ['class' => 'form-select']) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'activo')->dropDownList([1 => 'Si', 0 => 'No'], ['class' => 'form-select']) ?>
        </div>
        <div class="col-12">
            <?= $form->field($model, 'asunto')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-12">
            <?= $form->field($model, 'variables')->textarea(['rows' => 2, 'placeholder' => '["cliente_nombre","fecha"]']) ?>
        </div>
        <div class="col-12">
            <?= $form->field($model, 'cuerpo_html')->textarea(['rows' => 12]) ?>
        </div>
    </div>

    <div class="mt-3 d-flex gap-2">
        <?= Html::submitButton('Guardar', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Volver', ['plantillas'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
