<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */

$this->title = 'Cerrar Orden ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->codigo, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Cerrar';
?>

<div class="orden-servicio-cerrar">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>Checklist de Entrega - <?= $model->codigo ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Completar todos los items antes de entregar</p>

                    <form method="post" id="form-cerrar">
                        <div class="list-group">
                            <?php foreach ($model->checklistItems as $item): ?>
                                <label class="list-group-item">
                                    <input 
                                        type="checkbox" 
                                        name="checklist_ids[]" 
                                        value="<?= $item->id ?>"
                                        class="form-check-input me-2"
                                        required
                                    >
                                    <?= htmlspecialchars($item->item) ?>
                                </label>
                            <?php endforeach ?>
                        </div>

                        <div class="mt-3 alert alert-warning" id="validation-msg" style="display: none;">
                            ⚠️ Todos los items deben estar marcados para entregar la orden
                        </div>

                        <div class="mt-4 text-end">
                            <?= Html::a('Cancelar', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
                            <?= Html::submitButton('Entregar Orden', ['class' => 'btn btn-success btn-lg']) ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Resumen lateral -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6>Resumen de Orden</h6>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-sm mb-0'],
                        'attributes' => [
                            [
                                'label' => 'Cliente',
                                'value' => $model->cliente?->nombre ?? 'N/A',
                            ],
                            [
                                'label' => 'Vehículo',
                                'value' => $model->vehiculo?->patente ?? 'N/A',
                            ],
                            'estado',
                            [
                                'label' => 'Servicios',
                                'value' => count($model->detalles),
                            ],
                            [
                                'label' => 'Técnicos',
                                'value' => count($model->asignaciones),
                            ],
                        ],
                    ]) ?>

                    <hr>

                    <div class="alert alert-info">
                        <strong>Total Orden:</strong><br>
                        $<?= number_format((float)$model->total, 0, ',', '.') ?>
                    </div>

                    <!-- Placeholder for Hito 10 Pagos -->
                    <div class="alert alert-warning">
                        <strong>Saldo Pendiente:</strong><br>
                        $0 (Integración Hito 10)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
// Validar que al menos un item esté marcado
document.getElementById('form-cerrar').addEventListener('submit', function(e) {
    const checkboxes = document.querySelectorAll('input[name="checklist_ids[]"]:checked');
    const total = document.querySelectorAll('input[name="checklist_ids[]"]').length;
    
    if (checkboxes.length !== total) {
        e.preventDefault();
        document.getElementById('validation-msg').style.display = 'block';
        return false;
    }
});

// Mostrar/ocultar mensaje de validación
document.querySelectorAll('input[name="checklist_ids[]"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('input[name="checklist_ids[]"]:checked');
        const total = document.querySelectorAll('input[name="checklist_ids[]"]').length;
        
        if (checkboxes.length === total) {
            document.getElementById('validation-msg').style.display = 'none';
        }
    });
});
JS
) ?>
