<?php
declare(strict_types=1);

/** @var app\models\OrdenServicio $model */
/** @var array|string $gestionarRoute */
/** @var string $toggleRoute */

use yii\helpers\Html;
use yii\helpers\Url;

$gestionarRoute = $gestionarRoute ?? ['gestionar-checklist', 'id' => $model->id];
$toggleRoute = $toggleRoute ?? 'actualizar-checklist-item';
?>

<div class="checklist-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5>Checklist de Ingreso/Entrega</h5>
        <?php if ($model->estado === 'abierto' || $model->estado === 'en_progreso'): ?>
            <?= Html::a('Gestionar Checklist', $gestionarRoute, ['class' => 'btn btn-sm btn-primary']) ?>
        <?php endif; ?>
    </div>

    <?php if (empty($model->checklistItems)): ?>
        <p class="text-muted">
            <em>Sin items de verificación.</em>
            <?php if ($model->estado === 'abierto' || $model->estado === 'en_progreso'): ?>
                <?= Html::a('Agregar items del checklist', $gestionarRoute) ?>
            <?php endif; ?>
        </p>
    <?php else: ?>
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
                            data-url="<?= Url::to([$toggleRoute, 'id' => $item->id]) ?>"
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
$this->registerJs(<<<'JS'
document.querySelectorAll('.checklist-item-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', async function() {
        const completado = this.checked;
        const label = this.nextElementSibling;
        const endpoint = this.dataset.url;

        const csrfParamMeta = document.querySelector('meta[name="csrf-param"]');
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfParam = csrfParamMeta ? csrfParamMeta.content : '_csrf';
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.content : '';

        try {
            const payload = new URLSearchParams();
            payload.set('completado', completado ? '1' : '0');
            if (csrfToken) {
                payload.set(csrfParam, csrfToken);
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: payload.toString(),
            });

            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                throw new Error('Respuesta no JSON del servidor');
            }

            const data = await response.json();

            if (data.success) {
                if (completado) {
                    label.classList.add('text-decoration-line-through', 'text-muted');
                } else {
                    label.classList.remove('text-decoration-line-through', 'text-muted');
                }

                const progressBar = document.querySelector('.progress-bar.bg-success');
                if (progressBar) {
                    progressBar.style.width = data.porcentaje + '%';
                    progressBar.setAttribute('aria-valuenow', data.porcentaje);
                    progressBar.textContent = data.porcentaje + '% Completado';
                }
            } else {
                this.checked = !completado;
                alert('Error al actualizar: ' + (data.error || 'Error desconocido'));
            }
        } catch (error) {
            this.checked = !completado;
            console.error('Error actualizando checklist:', error);
            alert('No se pudo actualizar el checklist. Intenta nuevamente.');
        }
    });
});
JS, \yii\web\View::POS_END);
?>
