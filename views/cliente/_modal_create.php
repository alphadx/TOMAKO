<?php
/** @var yii\web\View $this */
/** @var string $modalId */
/** @var string $formId */
/** @var string $clienteSelectId */

use yii\helpers\Html;
use yii\helpers\Url;

$modalId = $modalId ?? 'cliente-quick-modal';
$formId = $formId ?? 'cliente-quick-form';
$clienteSelectId = $clienteSelectId ?? 'cliente_id';
$createAjaxUrl = Url::to(['/cliente/create-ajax']);
?>

<div class="modal fade" id="<?= Html::encode($modalId) ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Alta rápida de cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="<?= Html::encode($formId) ?>">
                <div class="modal-body">
                    <div id="<?= Html::encode($formId) ?>-error" class="alert alert-danger d-none" role="alert"></div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-nombre">Nombre</label>
                        <input
                            type="text"
                            class="form-control"
                            id="<?= Html::encode($formId) ?>-nombre"
                            name="Cliente[nombre]"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-email">Correo</label>
                        <input
                            type="email"
                            class="form-control"
                            id="<?= Html::encode($formId) ?>-email"
                            name="Cliente[email]"
                            maxlength="150"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-telefono">Teléfono</label>
                        <input
                            type="text"
                            class="form-control"
                            id="<?= Html::encode($formId) ?>-telefono"
                            name="Cliente[telefono]"
                            maxlength="25"
                            placeholder="+56 9 1234 5678"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-tipo_identificacion">Tipo de Identificación</label>
                        <select
                            class="form-select"
                            id="<?= Html::encode($formId) ?>-tipo_identificacion"
                            name="Cliente[tipo_identificacion]"
                        >
                            <option value="RUN">RUN</option>
                            <option value="PASAPORTE">PASAPORTE</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="<?= Html::encode($formId) ?>-identificacion_alternativa">Número de Identificación</label>
                        <input
                            type="text"
                            class="form-control rut-input"
                            id="<?= Html::encode($formId) ?>-identificacion_alternativa"
                            name="Cliente[identificacion_alternativa]"
                            maxlength="50"
                            placeholder="12.345.678-9 o número de pasaporte"
                            required
                        >
                        <small class="text-muted">RUT chileno con dígito verificador o número de pasaporte</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$escapedModalId = json_encode($modalId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedFormId = json_encode($formId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedClienteSelectId = json_encode($clienteSelectId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$escapedCreateAjaxUrl = json_encode($createAjaxUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$js = <<<JS
(function() {
    const modalId = {$escapedModalId};
    const formId = {$escapedFormId};
    const clienteSelectId = {$escapedClienteSelectId};
    const createUrl = {$escapedCreateAjaxUrl};

    const form = document.getElementById(formId);
    const modalElement = document.getElementById(modalId);
    const errorBox = document.getElementById(formId + '-error');

    if (!form || !modalElement || !errorBox) {
        return;
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

        fetch(createUrl, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                if (!data || !data.success) {
                    errorBox.textContent = (data && data.message) ? data.message : 'No fue posible crear el cliente.';
                    errorBox.classList.remove('d-none');
                    return;
                }

                const select = document.getElementById(clienteSelectId);
                if (select) {
                    const existing = select.querySelector('option[value="' + data.id + '"]');
                    if (!existing) {
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.textContent = data.nombre;
                        select.appendChild(option);
                    }
                    select.value = String(data.id);
                    select.dispatchEvent(new Event('change'));
                }

                const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
                modal.hide();
                form.reset();
            })
            .catch(function() {
                errorBox.textContent = 'Error de red al crear el cliente.';
                errorBox.classList.remove('d-none');
            });
    });
})();
JS;
$this->registerJs($js);
