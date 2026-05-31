<?php
declare(strict_types=1);

/** @var app\models\OrdenServicio $model */
use yii\helpers\Html;
use yii\helpers\Url;
?>

<div class="checklist-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Checklist de Ingreso/Entrega</h5>
        <?php if ($model->estado === 'abierto' || $model->estado === 'en_progreso'): ?>
            <?= Html::a('Gestionar Checklist', ['gestionar-checklist', 'id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
        <?php endif; ?>
    </div>

    <?php if (empty($model->checklistItems)): ?>
        <p class="text-muted">
            <em>Sin items de verificación.</em>
            <?php if ($model->estado === 'abierto' || $model->estado === 'en_progreso'): ?>
                <?= Html::a('Agregar items del checklist', ['gestionar-checklist', 'id' => $model->id]) ?>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <!-- Progress bar -->
        <div class="progress mb-3" style="height: 25px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?= $model->checklistPorcentaje ?>%;" 
                 aria-valuenow="<?= $model->checklistPorcentaje ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <?= $model->checklistPorcentaje ?>% Completado
            </div>
        </div>

        <div class="list-group">
            <?php foreach ($model->checklistItems as $item): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input 
                            class="form-check-input checklist-item-checkbox" 
                            type="checkbox" 
                            data-id="<?= $item->id ?>"
                            id="check-<?= $item->id ?>" 
                            <?= $item->completado ? 'checked' : '' ?>
                            <?= ($model->estado !== 'abierto' && $model->estado !== 'en_progreso') ? 'disabled' : '' ?>
                        >
                        <label class="form-check-label <?= $item->completado ? 'text-decoration-line-through text-muted' : '' ?>" for="check-<?= $item->id ?>">
                            <?= Html::encode($item->item) ?>
                        </label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<?php
$this->registerJs(<<<JS
document.querySelectorAll('.checklist-item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const itemId = this.dataset.id;
        const completado = this.checked;
        const label = this.nextElementSibling;
        
        fetch('/orden-servicio/actualizar-checklist-item/' + itemId, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ completado: completado })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update label style
                if (completado) {
                    label.classList.add('text-decoration-line-through', 'text-muted');
                } else {
                    label.classList.remove('text-decoration-line-through', 'text-muted');
                }
                
                // Update progress bar if exists
                const progressBar = document.querySelector('.progress-bar.bg-success');
                if (progressBar) {
                    progressBar.style.width = data.porcentaje + '%';
                    progressBar.setAttribute('aria-valuenow', data.porcentaje);
                    progressBar.textContent = data.porcentaje + '% Completado';
                }
            } else {
                // Revert checkbox on error
                this.checked = !completado;
                alert('Error al actualizar: ' + data.error);
            }
        })
        .catch(error => {
            this.checked = !completado;
            console.error('Error:', error);
        });
    });
});
JS, \yii\web\View::POS_END);
?>
