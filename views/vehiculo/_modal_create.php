<?php
/** @var yii\web\View $this */
/** @var string $modalId */
/** @var string $formId */
/** @var string $vehiculoSelectId */
/** @var string $clienteSelectId */
/** @var int|null $clienteIdFijo */
/** @var string $clienteNombre */

use yii\helpers\Html;
use yii\helpers\Url;

$modalId = $modalId ?? 'vehiculo-quick-modal';
$formId = $formId ?? 'vehiculo-quick-form';
$vehiculoSelectId = $vehiculoSelectId ?? 'vehiculo_id';
$clienteSelectId = $clienteSelectId ?? 'cliente_id';
$clienteIdFijo = $clienteIdFijo ?? null;
$clienteNombre = $clienteNombre ?? '';
$createAjaxUrl = Url::to(['/vehiculo/create-ajax']);
?>

<div class="modal fade" id="<?= Html::encode($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alta rápida de vehículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="<?= Html::encode($formId) ?>">
                <div class="modal-body">
                    <div id="<?= Html::encode($formId) ?>-error" class="alert alert-danger d-none" role="alert"></div>

                    <?php if ($clienteIdFijo !== null): ?>
                    <!-- Cliente preseleccionado (solo lectura) -->
                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-cliente-display">Cliente</label>
                        <input
                            type="text"
                            class="form-control form-control-plaintext"
                            id="<?= Html::encode($formId) ?>-cliente-display"
                            value="<?= Html::encode($clienteNombre) ?>"
                            readonly
                            style="background-color: #e9ecef; cursor: not-allowed;"
                        >
                        <input type="hidden" name="Vehiculo[cliente_id]" value="<?= (int)$clienteIdFijo ?>">
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-patente">Patente</label>
                        <input
                            type="text"
                            class="form-control"
                            id="<?= Html::encode($formId) ?>-patente"
                            name="Vehiculo[patente]"
                            maxlength="20"
                            placeholder="Ej: ABCD-12"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-marca">Marca</label>
                        <select
                            id="<?= Html::encode($formId) ?>-marca"
                            name="Vehiculo[marca_id]"
                            class="form-control select2-marca"
                            required
                        ></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-modelo">Modelo</label>
                        <select
                            id="<?= Html::encode($formId) ?>-modelo"
                            name="Vehiculo[modelo_id]"
                            class="form-control select2-modelo"
                            required
                        ></select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-anio">Año</label>
                        <input
                            type="number"
                            class="form-control"
                            id="<?= Html::encode($formId) ?>-anio"
                            name="Vehiculo[anio]"
                            min="1900"
                            max="<?= date('Y') + 1 ?>"
                            placeholder="Ej: 2020"
                            required
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar vehículo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$escapedModalId = json_encode($modalId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedFormId = json_encode($formId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedVehiculoSelectId = json_encode($vehiculoSelectId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedCreateAjaxUrl = json_encode($createAjaxUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// Registrar CSS de Select2 para el modal (jQuery ya está cargado por AppAsset)
$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');

$js = <<<JS
(function() {
    const modalId = {$escapedModalId};
    const formId = {$escapedFormId};
    const vehiculoSelectId = {$escapedVehiculoSelectId};
    const createUrl = {$escapedCreateAjaxUrl};

    const form = document.getElementById(formId);
    const modalElement = document.getElementById(modalId);
    const errorBox = document.getElementById(formId + '-error');

    if (!form || !modalElement || !errorBox) {
        return;
    }

    // Inicializar Select2 para marca y modelo en este modal
    const marcaSelectId = formId + '-marca';
    const modeloSelectId = formId + '-modelo';
    
    // Usar la API global si está disponible
    if (window.VehiculoMarcaModeloSelect2) {
        window.VehiculoMarcaModeloSelect2.initForm(formId, marcaSelectId, modeloSelectId);
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();
        errorBox.classList.add('d-none');
        errorBox.textContent = '';

        const formData = new FormData(form);
        const csrfParamElement = document.querySelector('meta[name="csrf-param"]');
        const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');

        if (csrfParamElement && csrfTokenElement) {
            formData.append(csrfParamElement.content, csrfTokenElement.content);
        }

        // Obtener cliente_id dinámicamente desde el combo de cliente si no está en el formulario
        const clienteSelect = document.getElementById('<?= Html::encode($clienteSelectId) ?>');
        if (clienteSelect && clienteSelect.value && !formData.has('Vehiculo[cliente_id]')) {
            formData.append('Vehiculo[cliente_id]', clienteSelect.value);
        }

        fetch(createUrl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || !data.success) {
                    errorBox.textContent = (data && data.message) ? data.message : 'No fue posible crear el vehículo.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                const select = document.getElementById(vehiculoSelectId);
                if (select) {
                    const existing = select.querySelector('option[value="' + data.id + '"]');
                   if (!existing) {
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.textContent = data.text;
                        select.appendChild(option);
                    }
                    select.value = String(data.id);
                }

                const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modal.hide();
                form.reset();
                
                // Resetear selectores Select2
                if (window.VehiculoMarcaModeloSelect2) {
                    $('#' + marcaSelectId).val(null).trigger('change');
                    $('#' + modeloSelectId).val(null).trigger('change');
                }
            })
            .catch(function() {
                errorBox.textContent = 'Error de red al crear el vehículo.';
                errorBox.classList.remove('d-none');
            });
    });
})();
JS;
$this->registerJs($js);
