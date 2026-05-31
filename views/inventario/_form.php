<?php
/** @var yii\web\View $this */
/** @var app\models\InventoryItem $model */

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use app\models\InventoryItem;
use app\models\Categoria;
?>

<?php $form = ActiveForm::begin(['id' => 'inventory-form']); ?>

<div class="row g-3">
    <div class="col-md-4">
        <?= $form->field($model, 'sku')->textInput(['maxlength' => 20, 'placeholder' => 'INS-0001', 'readonly' => true])
            ->hint('Se genera automáticamente y no se puede editar.') ?>
    </div>
    <div class="col-md-5">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 150, 'placeholder' => 'Nombre del ítem']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'status')->dropDownList(InventoryItem::getEstadosList()) ?>
    </div>

    <div class="col-md-12">
        <?= $form->field($model, 'descripcion')->textarea(['rows' => 2, 'placeholder' => 'Descripción opcional...']) ?>
    </div>

    <div class="col-md-4">
        <?= $form->field($model, 'categoria_id')->dropDownList(
            ArrayHelper::map(
                Categoria::find()->where(['status' => 1])->orderBy('nombre')->all(),
                'id', 'nombre'
            ),
            ['prompt' => '— Seleccione categoría —']
        ) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'precio_unitario')->input('number', ['min' => 0, 'step' => '0.01', 'placeholder' => '0.00']) ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($model, 'unidad')->dropDownList(InventoryItem::getUnidadesList()) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'ubicacion')->textInput(['maxlength' => 100, 'placeholder' => 'Estante A-3...']) ?>
    </div>

    <div class="col-md-3">
        <?= $form->field($model, 'cantidad')->input('number', ['min' => 0, 'placeholder' => '0'])->hint('Stock actual.') ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'stock_minimo')->input('number', ['min' => 0, 'placeholder' => '0'])->hint('Alerta cuando llegue a este valor.') ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'stock_maximo')->input('number', ['min' => 0, 'placeholder' => 'Opcional'])->hint('Capacidad máxima de almacenamiento.') ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Ítem' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
