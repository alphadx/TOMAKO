<?php
declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\PlantillaChecklist $model */
/** @var array $servicios */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->id ? 'Editar Plantilla' : 'Nueva Plantilla de Checklist';
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['/servicio/index']];
$this->params['breadcrumbs'][] = ['label' => 'Plantillas de Checklist', 'url' => ['plantillas-index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="plantilla-checklist-form">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['plantillas-index'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['id' => 'plantilla-form']); ?>

            <div class="row">
                <div class="col-md-8">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Información General</h5>
                    
                    <?= $form->field($model, 'servicio_id')->dropDownList(
                        \yii\helpers\ArrayHelper::map($servicios, 'id', 'nombre'),
                        ['prompt' => 'Seleccione un servicio...', 'class' => 'form-select']
                    ) ?>

                    <?= $form->field($model, 'nombre')->textInput(['maxlength' => true, 'placeholder' => 'Ej: Checklist Mantenimiento Básico']) ?>

                    <?= $form->field($model, 'descripcion')->textarea(['rows' => 3, 'placeholder' => 'Descripción opcional de la plantilla...']) ?>

                    <?= $form->field($model, 'activa')->checkbox([
                        'label' => 'Plantilla activa (disponible para uso en órdenes)',
                        'checked' => $model->activa ?? true,
                    ]) ?>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-lightbulb me-2"></i>Consejos</h6>
                            <ul class="small mb-0 ps-3">
                                <li>Los items obligatorios deben completarse antes de cerrar la orden</li>
                                <li>El orden determina la secuencia de verificación</li>
                                <li>Puede duplicar plantillas existentes para ahorrar tiempo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Items del Checklist</h5>
            
            <div id="items-container" class="mb-3">
                <?php if (!empty($model->items)): ?>
                    <?php foreach ($model->items as $index => $item): ?>
                        <div class="item-row card mb-2 p-2">
                            <div class="row g-2 align-items-center">
                                <div class="col-md-1">
                                    <label class="form-label small">Orden</label>
                                    <?= Html::input('number', "PlantillaChecklistItem[orden][]", $item->orden, [
                                        'class' => 'form-control form-control-sm item-orden',
                                        'min' => 0,
                                        'step' => 1,
                                    ]) ?>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small">Descripción del Item</label>
                                    <?= Html::input('text', "PlantillaChecklistItem[descripcion][]", $item->descripcion, [
                                        'class' => 'form-control form-control-sm item-descripcion',
                                        'placeholder' => 'Describa el item a verificar...',
                                        'required' => true,
                                    ]) ?>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small">&nbsp;</label>
                                    <div class="form-check">
                                        <?= Html::checkbox("PlantillaChecklistItem[obligatorio][]", $item->obligatorio, [
                                            'class' => 'form-check-input item-obligatorio',
                                            'value' => 1,
                                            'uncheck' => 0,
                                        ]) ?>
                                        <?= Html::label('Obligatorio', "PlantillaChecklistItem[obligatorio][]", ['class' => 'form-check-label']) ?>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <label class="form-label small">&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Eliminar item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="item-row card mb-2 p-2">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-1">
                                <label class="form-label small">Orden</label>
                                <?= Html::input('number', "PlantillaChecklistItem[orden][]", 0, [
                                    'class' => 'form-control form-control-sm item-orden',
                                    'min' => 0,
                                    'step' => 1,
                                    'value' => 0,
                                ]) ?>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small">Descripción del Item</label>
                                <?= Html::input('text', "PlantillaChecklistItem[descripcion][]", '', [
                                    'class' => 'form-control form-control-sm item-descripcion',
                                    'placeholder' => 'Describa el item a verificar...',
                                    'required' => true,
                                ]) ?>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">&nbsp;</label>
                                <div class="form-check">
                                    <?= Html::checkbox("PlantillaChecklistItem[obligatorio][]", false, [
                                        'class' => 'form-check-input item-obligatorio',
                                        'value' => 1,
                                        'uncheck' => 0,
                                    ]) ?>
                                    <?= Html::label('Obligatorio', "PlantillaChecklistItem[obligatorio][]", ['class' => 'form-check-label']) ?>
                                </div>
                            </div>
                            <div class="col-md-1 text-end">
                                <label class="form-label small">&nbsp;</label>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Eliminar item">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" id="add-item" class="btn btn-outline-primary btn-sm mb-4">
                <i class="fas fa-plus-circle"></i> Agregar Item
            </button>

            <hr>

            <div class="d-flex gap-2">
                <?= Html::submitButton('<i class="fas fa-save"></i> Guardar Plantilla', ['class' => 'btn btn-success']) ?>
                <?= Html::a('<i class="fas fa-times"></i> Cancelar', ['plantillas-index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
$addItemJs = <<<JS
document.getElementById('add-item').addEventListener('click', function() {
    const container = document.getElementById('items-container');
    const index = container.querySelectorAll('.item-row').length;
    
    const newRow = document.createElement('div');
    newRow.className = 'item-row card mb-2 p-2';
    newRow.innerHTML = `
        <div class="row g-2 align-items-center">
            <div class="col-md-1">
                <label class="form-label small">Orden</label>
                <input type="number" name="PlantillaChecklistItem[orden][]" class="form-control form-control-sm item-orden" min="0" value="${index}">
            </div>
            <div class="col-md-8">
                <label class="form-label small">Descripción del Item</label>
                <input type="text" name="PlantillaChecklistItem[descripcion][]" class="form-control form-control-sm item-descripcion" placeholder="Describa el item a verificar..." required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">&nbsp;</label>
                <div class="form-check">
                    <input type="checkbox" name="PlantillaChecklistItem[obligatorio][]" class="form-check-input item-obligatorio" value="1">
                    <label class="form-check-label">Obligatorio</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <label class="form-label small">&nbsp;</label>
                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Eliminar item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(newRow);
    updateRemoveButtons();
});

function updateRemoveButtons() {
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('.item-row');
            const container = document.getElementById('items-container');
            if (container.querySelectorAll('.item-row').length > 1) {
                row.remove();
            } else {
                alert('Debe haber al menos un item en la plantilla.');
            }
        });
    });
}

updateRemoveButtons();
JS;

$this->registerJs($addItemJs, \yii\web\View::POS_END);
?>
