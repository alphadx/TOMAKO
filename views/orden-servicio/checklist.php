<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\OrdenServicio;

/** @var yii\web\View $this */
/** @var OrdenServicio $model */

$this->title = 'Gestionar Checklist - ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->codigo, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Checklist';
?>

<div class="checklist-form">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <strong>Información:</strong> Complete el checklist de ingreso del vehículo. 
        Marque todos los items que deben verificarse al recibir el vehículo del cliente.
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'checklist-form',
        'action' => ['gestionar-checklist', 'id' => $model->id],
        'method' => 'post',
    ]); ?>

    <div class="card mb-3">
        <div class="card-header">
            <h5>Datos del Vehículo</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Cliente:</strong> <?= Html::encode($model->cliente->nombre ?? 'N/A') ?></p>
                    <p><strong>Vehículo:</strong> <?= Html::encode($model->vehiculo->marca_modelo ?? 'N/A') ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Patente:</strong> <?= Html::encode($model->vehiculo->patente ?? 'N/A') ?></p>
                    <p><strong>Kilometraje:</strong> <?= number_format((float)($model->vehiculo->kilometraje ?? 0), 0, ',', '.') ?> km</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5>Items del Checklist</h5>
            <button type="button" id="btn-agregar-item" class="btn btn-sm btn-outline-primary">
                + Agregar Item
            </button>
        </div>
        <div class="card-body">
            <div id="checklist-items-container">
                <?php if (!empty($model->checklistItems)): ?>
                    <?php foreach ($model->checklistItems as $index => $item): ?>
                        <div class="input-group mb-2 checklist-item-row">
                            <input type="text" 
                                   name="checklist_items[]" 
                                   class="form-control" 
                                   value="<?= Html::encode($item->item) ?>"
                                   placeholder="Descripción del item a verificar"
                                   required>
                            <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Items por defecto para checklist de ingreso -->
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Nivel de combustible" 
                               value="Nivel de combustible">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Kilometraje actual"
                               value="Kilometraje actual">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Estado de la carrocería (rayaduras/golpes)"
                               value="Estado de la carrocería (rayaduras/golpes)">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Funcionamiento de luces"
                               value="Funcionamiento de luces">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Estado de neumáticos"
                               value="Estado de neumáticos">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                    <div class="input-group mb-2 checklist-item-row">
                        <input type="text" name="checklist_items[]" class="form-control" 
                               placeholder="Ej: Objetos personales en el vehículo"
                               value="Objetos personales en el vehículo">
                        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="mt-3">
                <small class="text-muted">
                    <strong>Sugerencia:</strong> Agregue todos los items relevantes para el ingreso del vehículo. 
                    Estos items deberán ser verificados antes de la entrega.
                </small>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <?= Html::submitButton('Guardar Checklist', ['class' => 'btn btn-success']) ?>
        <?= Html::a('Cancelar', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>

<?php
$this->registerJs(<<<JS
// Agregar nuevo item
document.getElementById('btn-agregar-item').addEventListener('click', function() {
    const container = document.getElementById('checklist-items-container');
    const newItem = document.createElement('div');
    newItem.className = 'input-group mb-2 checklist-item-row';
    newItem.innerHTML = `
        <input type="text" name="checklist_items[]" class="form-control" 
               placeholder="Descripción del item a verificar" required>
        <button type="button" class="btn btn-outline-danger btn-eliminar-item">
            <i class="bi bi-trash"></i> Eliminar
        </button>
    `;
    container.appendChild(newItem);
});

// Eliminar item
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-eliminar-item')) {
        const row = e.target.closest('.checklist-item-row');
        const container = document.getElementById('checklist-items-container');
        
        // Prevenir eliminar el último item
        if (container.querySelectorAll('.checklist-item-row').length > 1) {
            row.remove();
        } else {
            alert('Debe haber al menos un item en el checklist');
        }
    }
});
JS, \yii\web\View::POS_END);
?>
