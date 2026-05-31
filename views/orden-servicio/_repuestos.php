<?php
declare(strict_types=1);

use yii\helpers\Html;
use app\models\OrdenServicio;
use app\models\InventoryItem;

/** @var yii\web\View $this */
/** @var OrdenServicio $model */

$repuestosDisponibles = InventoryItem::find()
    ->where(['status' => 1])
    ->andWhere(['>', 'cantidad', 0])
    ->orderBy(['nombre' => SORT_ASC])
    ->all();
?>

<div class="orden-repuestos-tab">
    <!-- Formulario para agregar repuesto -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Agregar Repuesto a Orden</h5>
        </div>
        <div class="card-body">
            <?= Html::beginForm(['agregar-repuesto', 'id' => $model->id], 'post', ['class' => 'row g-3']) ?>
            
            <div class="col-md-6">
                <label class="form-label">Repuesto/Insumo</label>
                <select name="repuesto_id" class="form-control" required>
                    <option value="">Seleccione un repuesto...</option>
                    <?php foreach ($repuestosDisponibles as $repuesto): ?>
                        <option value="<?= $repuesto->id ?>">
                            <?= Html::encode($repuesto->nombre) ?> 
                            (Stock: <?= $repuesto->cantidad ?>, 
                             Precio: $<?= number_format($repuesto->precio_unitario, 0, ',', '.') ?>)
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Cantidad</label>
                <input type="number" name="cantidad" class="form-control" value="1" min="1" required>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Agregar Repuesto</button>
            </div>
            
            <?= Html::endForm() ?>
        </div>
    </div>

    <!-- Lista de repuestos utilizados -->
    <div class="card">
        <div class="card-header">
            <h5>Repuestos Utilizados</h5>
        </div>
        <div class="card-body">
            <?php if (empty($model->repuestos)): ?>
                <p class="text-muted">No se han agregado repuestos a esta orden.</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Repuesto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                            <th>Notas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalRepuestos = 0;
                        foreach ($model->repuestos as $ordenRepuesto): 
                            $totalRepuestos += $ordenRepuesto->subtotal;
                        ?>
                            <tr>
                                <td>
                                    <strong><?= Html::encode($ordenRepuesto->repuesto->nombre) ?></strong>
                                    <br>
                                    <small class="text-muted">SKU: <?= Html::encode($ordenRepuesto->repuesto->sku) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <input type="number" 
                                               class="form-control form-control-sm me-2" 
                                               style="width: 80px;"
                                               value="<?= $ordenRepuesto->cantidad ?>" 
                                               min="1"
                                               data-id="<?= $ordenRepuesto->id ?>"
                                               data-action="actualizar-cantidad">
                                        <span class="badge bg-secondary"><?= $ordenRepuesto->repuesto->unidad ?></span>
                                    </div>
                                </td>
                                <td>$<?= number_format($ordenRepuesto->precio_unitario_aplicado, 0, ',', '.') ?></td>
                                <td><strong>$<?= number_format($ordenRepuesto->subtotal, 0, ',', '.') ?></strong></td>
                                <td><?= Html::encode($ordenRepuesto->nota ?? '—') ?></td>
                                <td>
                                    <?= Html::beginForm(['eliminar-repuesto', 'ordenId' => $model->id, 'repuestoId' => $ordenRepuesto->repuesto_id], 'post', ['style' => 'display:inline']) ?>
                                        <?= Html::submitButton('Eliminar', [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'data-confirm' => '¿Está seguro de eliminar este repuesto? El stock será reintegrado.',
                                        ]) ?>
                                    <?= Html::endForm() ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <th colspan="3">Total Repuestos:</th>
                            <th colspan="4">$<?= number_format($totalRepuestos, 0, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            <?php endif ?>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
// Actualizar cantidad de repuesto vía AJAX
document.querySelectorAll('[data-action="actualizar-cantidad"]').forEach(input => {
    input.addEventListener('change', function() {
        const id = this.dataset.id;
        const nuevaCantidad = parseInt(this.value);
        
        if (nuevaCantidad < 1) {
            alert('La cantidad mínima es 1');
            return;
        }
        
        fetch('/orden-servicio/actualizar-cantidad-repuesto/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'cantidad=' + nuevaCantidad
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.error);
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la cantidad');
        });
    });
});
JS
) ?>
