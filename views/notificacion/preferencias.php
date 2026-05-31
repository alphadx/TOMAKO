<?php
/** @var yii\web\View $this */
/** @var app\models\PreferenciaNotificacion $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Preferencias de Notificacion';
$this->params['breadcrumbs'][] = ['label' => 'Notificaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-preferencias" style="max-width:680px;">
    <h1 class="h4 mb-3"><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>
    <div class="card mb-3">
        <div class="card-header"><strong>Emails</strong></div>
        <div class="card-body">
            <?= $form->field($model, 'email_cita')->checkbox() ?>
            <?= $form->field($model, 'email_orden_estado')->checkbox() ?>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Internas</strong></div>
        <div class="card-body">
            <?= $form->field($model, 'interno_stock')->checkbox() ?>
            <?= $form->field($model, 'interno_orden')->checkbox() ?>
        </div>
    </div>

    <div>
        <?= Html::submitButton('Guardar preferencias', ['class' => 'btn btn-primary']) ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
