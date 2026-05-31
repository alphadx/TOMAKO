<?php
/** @var yii\web\View $this */
/** @var app\models\Pago $model */
/** @var app\models\OrdenServicio[] $ordenes */
/** @var array<int, string> $metodosPago */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\Pago;
?>

<div class="pago-form">
    <?php $form = ActiveForm::begin(['id' => 'pago-form']); ?>

    <div class="row g-3">
        <div class="col-md-6">
            <?= $form->field($model, 'orden_id')->dropDownList(
                ArrayHelper::map($ordenes, 'id', fn($o) => $o->codigo . ' — ' . ($o->cliente ? $o->cliente->nombre : '—')),
                ['prompt' => '— Seleccionar orden —', 'class' => 'form-select']
            ) ?>
        </div>

        <div class="col-md-3">
            <?= $form->field($model, 'monto')->textInput([
                'type'        => 'number',
                'step'        => '0.01',
                'min'         => '0.01',
                'placeholder' => '0.00',
            ]) ?>
        </div>

        <div class="col-md-3">
            <?= $form->field($model, 'metodo_pago_id')->dropDownList($metodosPago, ['prompt' => '— Seleccionar —', 'class' => 'form-select']) ?>
        </div>

        <div class="col-md-6">
            <?= $form->field($model, 'referencia')->textInput(['maxlength' => 100, 'placeholder' => 'N° comprobante, voucher, etc.']) ?>
        </div>

        <div class="col-12">
            <?= $form->field($model, 'notas')->textarea(['rows' => 3, 'placeholder' => 'Observaciones opcionales...']) ?>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <?= Html::submitButton('<i class="bi bi-save me-1"></i>Guardar Pago', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('<i class="bi bi-x-circle me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
