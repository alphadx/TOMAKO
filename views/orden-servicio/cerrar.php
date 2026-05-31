<?php
declare(strict_types=1);

use yii\helpers\Html;

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
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Cliente:</strong></td>
                            <td><?= htmlspecialchars($model->cliente->nombre ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>Vehículo:</strong></td>
                            <td><?= htmlspecialchars($model->vehiculo->patente ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <td><strong>estado:</strong></td>
                            <td><?= htmlspecialchars($model->estado) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Servicios:</strong></td>
                            <td><?= count($model->detalles) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Técnicos:</strong></td>
                            <td><?= count($model->asignaciones) ?></td>
                        </tr>
                    </table>

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
