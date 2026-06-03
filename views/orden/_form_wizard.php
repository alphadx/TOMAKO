<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */
/** @var app\models\Cliente[] $clientes */
/** @var app\models\Servicio[] $servicios */
/** @var app\models\Tecnico[] $tecnicos */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\OrdenServicio;

// Pre-cargar vehículos si hay cliente seleccionado
$vehiculosOptions = [];
if ($model->cliente_id) {
    foreach (\app\models\Vehiculo::find()->where(['cliente_id' => $model->cliente_id, 'status' => 1])->all() as $v) {
        $vehiculosOptions[$v->id] = "{$v->patente} – {$v->marca} {$v->modelo}";
    }
}

// Detalles existentes para edición
$detallesExistentes = $model->isNewRecord ? [] : $model->detalles;
$tecnicosAsignados  = $model->isNewRecord ? [] : array_map(fn($a) => $a->tecnico_id, $model->asignaciones);

$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
?>

<?php $form = ActiveForm::begin(['id' => 'orden-form']); ?>

<!-- Wizard Steps -->
<div class="wizard-steps mb-4">
    <div class="row text-center">
        <div class="<?= $model->isNewRecord ? 'col-md-3' : 'col-md-4' ?> step-item active" data-step="1">
            <div class="step-circle mx-auto mb-2">1</div>
            <div class="step-label fw-bold">Cliente y Vehículo</div>
        </div>
        <div class="<?= $model->isNewRecord ? 'col-md-3' : 'col-md-4' ?> step-item" data-step="2">
            <div class="step-circle mx-auto mb-2">2</div>
            <div class="step-label fw-bold">Servicios</div>
        </div>
        <div class="<?= $model->isNewRecord ? 'col-md-3' : 'col-md-4' ?> step-item" data-step="3">
            <div class="step-circle mx-auto mb-2">3</div>
            <div class="step-label fw-bold">Técnicos</div>
        </div>
        <?php if ($model->isNewRecord): ?>
            <div class="col-md-3 step-item" data-step="4">
                <div class="step-circle mx-auto mb-2">4</div>
                <div class="step-label fw-bold">Checklist</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Step 1: Cliente y Vehículo -->
<div class="wizard-step" id="step-1">
    <div class="row g-3">
        <div class="col-md-6">
            <?= $form->field($model, 'cliente_id')->dropDownList(
                array_merge(['' => '— Seleccione cliente —'],
                    array_combine(
                        array_map(fn($c) => $c->id, $clientes),
                        array_map(fn($c) => $c->nombre . ' (' . $c->rut . ')', $clientes)
                    )
                ),
                ['id' => 'orden-cliente_id', 'class' => 'form-control select2-cliente']
            ) ?>
            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#orden-cliente-quick-modal"
            >
                <i class="bi bi-person-plus me-1"></i>Alta rápida
            </button>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'vehiculo_id')->dropDownList(
                array_merge(['' => '— Seleccione vehículo —'], $vehiculosOptions),
                ['id' => 'orden-vehiculo_id', 'class' => 'form-control select2-vehiculo']
            ) ?>
            <button
                type="button"
                class="btn btn-outline-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#orden-vehiculo-quick-modal"
            >
                <i class="bi bi-car-front me-1"></i>Alta rápida
            </button>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'prioridad')->dropDownList(OrdenServicio::getPrioridadesList()) ?>
        </div>
        <div class="col-md-4">
            <?= $form->field($model, 'ultimo_km')->textInput(['type' => 'number', 'min' => '0']) ?>
        </div>
        <div class="col-md-4">
            <label class="form-label">Días desde última mantención</label>
            <div id="dias-mantencion-display" class="form-control-plaintext fw-bold">—</div>
        </div>
        <div class="col-12">
            <?= $form->field($model, 'notas_generales')->textarea(['rows' => 2, 'placeholder' => 'Observaciones generales...']) ?>
        </div>
    </div>
    
    <div class="mt-4 d-flex justify-content-end">
        <button type="button" class="btn btn-primary btn-next" data-next="2">
            Siguiente <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- Step 2: Servicios -->
<div class="wizard-step d-none" id="step-2">
    <div class="mt-3">
        <label class="form-label fw-bold">Servicios</label>
        <div class="table-responsive">
            <table class="table table-bordered table-sm" id="servicios-table">
                <thead class="table-dark">
                    <tr>
                        <th>Servicio</th>
                        <th style="width:100px">Cantidad</th>
                        <th style="width:140px">Precio Unit.</th>
                        <th>Nota del servicio</th>
                        <th style="width:50px"></th>
                    </tr>
                </thead>
                <tbody id="servicios-body">
                    <?php
                    $idx = 0;
                    foreach ($detallesExistentes as $d):
                        $precio = $d->precio_unitario;
                        $svcId  = $d->servicio_id;
                        $qty    = $d->cantidad;
                        $nota   = $d->nota;
                    ?>
                    <tr>
                        <td>
                            <select name="servicios[<?= $idx ?>][servicio_id]" class="form-select form-select-sm svc-select" required>
                                <option value="">— Seleccione —</option>
                                <?php foreach ($servicios as $s): ?>
                                <option value="<?= $s->id ?>" data-precio="<?= $s->precio_base ?>" <?= $s->id == $svcId ? 'selected' : '' ?>><?= Html::encode($s->nombre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="servicios[<?= $idx ?>][cantidad]" class="form-control form-control-sm" value="<?= $qty ?>" min="1" required></td>
                        <td><input type="number" name="servicios[<?= $idx ?>][precio_unitario]" class="form-control form-control-sm" value="<?= $precio ?>" min="0" step="0.01" required></td>
                        <td><input type="text" name="servicios[<?= $idx ?>][nota]" class="form-control form-control-sm" value="<?= Html::encode((string) $nota) ?>" maxlength="500" placeholder="Nota opcional del servicio"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
                    </tr>
                    <?php $idx++; endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="button" id="add-servicio" class="btn btn-outline-success btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Agregar Servicio
        </button>
    </div>
    
    <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-prev" data-prev="1">
            <i class="bi bi-arrow-left me-1"></i> Anterior
        </button>
        <button type="button" class="btn btn-primary btn-next" data-next="3">
            Siguiente <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

<!-- Step 3: Técnicos y Confirmación -->
<div class="wizard-step d-none" id="step-3">
    <!-- Técnicos -->
    <div class="mt-3">
        <label class="form-label fw-bold">Técnicos Asignados</label>
        <div class="row g-2">
            <?php foreach ($tecnicos as $t): ?>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tecnico_ids[]"
                           value="<?= $t->id ?>" id="tec_<?= $t->id ?>"
                           <?= in_array($t->id, $tecnicosAsignados, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="tec_<?= $t->id ?>"><?= Html::encode($t->getFullName()) ?></label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Resumen -->
    <div class="mt-4 card bg-light">
        <div class="card-body">
            <h5 class="card-title fw-bold">Resumen de la Orden</h5>
            <div id="resumen-orden">
                <p class="mb-1"><strong>Cliente:</strong> <span id="resumen-cliente">—</span></p>
                <p class="mb-1"><strong>Vehículo:</strong> <span id="resumen-vehiculo">—</span></p>
                <p class="mb-1"><strong>Prioridad:</strong> <span id="resumen-prioridad">—</span></p>
                <p class="mb-1"><strong>Servicios:</strong> <span id="resumen-servicios">0</span></p>
                <p class="mb-1"><strong>Total estimado:</strong> <span id="resumen-total">$0</span></p>
            </div>
        </div>
    </div>
    
    <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-prev" data-prev="2">
            <i class="bi bi-arrow-left me-1"></i> Anterior
        </button>
        <?php if ($model->isNewRecord): ?>
            <button type="button" class="btn btn-primary btn-next" data-next="4">
                Siguiente <i class="bi bi-arrow-right ms-1"></i>
            </button>
        <?php else: ?>
            <button type="submit" class="btn btn-success">
                <i class="bi bi-floppy me-1"></i>Actualizar
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($model->isNewRecord): ?>
<!-- Step 4: Checklist (solo al crear) -->
<div class="wizard-step d-none" id="step-4">
    <div class="mt-3">
        <label class="form-label fw-bold">Checklist (items de verificación)</label>

        <div id="checklist-items-list" class="mb-2">
            <?php if (!empty($model->checklistItems)): ?>
                <?php foreach ($model->checklistItems as $ci): ?>
                    <div class="d-flex gap-2 align-items-center mb-1">
                        <input type="hidden" name="checklist_items[]" value="<?= Html::encode($ci->item) ?>">
                        <div class="flex-grow-1"><?= Html::encode($ci->item) ?></div>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-checklist-item"><i class="bi bi-trash"></i></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="input-group mb-3">
            <input type="text" id="checklist-new-item" class="form-control" placeholder="Agregar item del checklist (ej. Revisar niveles)" />
            <button type="button" id="add-checklist-item" class="btn btn-outline-success">Agregar</button>
        </div>

        <div class="text-muted small">Puedes añadir varios items; se guardarán junto con la orden.</div>
    </div>

    <div class="mt-4 d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-prev" data-prev="3">
            <i class="bi bi-arrow-left me-1"></i> Anterior
        </button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Orden' : 'Actualizar' ?>
        </button>
    </div>
</div>
<?php endif; ?>

<?php ActiveForm::end(); ?>

<?= $this->render('@app/views/cliente/_modal_create', [
    'modalId' => 'orden-cliente-quick-modal',
    'formId' => 'orden-cliente-quick-form',
    'clienteSelectId' => 'orden-cliente_id',
]) ?>

<?= $this->render('@app/views/vehiculo/_modal_create', [
    'modalId' => 'orden-vehiculo-quick-modal',
    'formId' => 'orden-vehiculo-quick-form',
    'vehiculoSelectId' => 'orden-vehiculo_id',
    'clienteSelectId' => 'orden-cliente_id',
]) ?>

<?php
$serviciosJson = json_encode(array_map(fn($s) => ['id' => $s->id, 'nombre' => $s->nombre, 'precio' => $s->precio_base], $servicios));
$ajaxUrl       = Url::to(['/cita/vehiculos-por-cliente']);
$rowIdx        = $idx;
$js = <<<JS
let rowIdx = {$rowIdx};
const serviciosData = {$serviciosJson};
let currentStep = 1;

function buildSelectOptions(selected) {
    let opts = '<option value="">— Seleccione —</option>';
    serviciosData.forEach(s => {
        const sel = s.id == selected ? ' selected' : '';
        opts += `<option value="\${s.id}" data-precio="\${s.precio}"\${sel}>\${s.nombre}</option>`;
    });
    return opts;
}

// Navegación del wizard
document.querySelectorAll('.btn-next').forEach(btn => {
    btn.addEventListener('click', function() {
        const nextStep = parseInt(this.dataset.next);
        if (validateStep(currentStep)) {
            goToStep(nextStep);
        }
    });
});

document.querySelectorAll('.btn-prev').forEach(btn => {
    btn.addEventListener('click', function() {
        goToStep(parseInt(this.dataset.prev));
    });
});

function goToStep(step) {
    document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('d-none'));
    document.getElementById('step-' + step).classList.remove('d-none');
    
    document.querySelectorAll('.step-item').forEach(el => {
        el.classList.remove('active');
        if (parseInt(el.dataset.step) <= step) {
            el.classList.add('active');
        }
    });
    
    currentStep = step;
    
    if (step === 3) {
        updateResumen();
    }
}

function validateStep(step) {
    if (step === 1) {
        const clienteId = document.getElementById('orden-cliente_id').value;
        const vehiculoId = document.getElementById('orden-vehiculo_id').value;
        if (!clienteId) {
            alert('Debe seleccionar un cliente');
            return false;
        }
        if (!vehiculoId) {
            alert('Debe seleccionar un vehículo');
            return false;
        }
    }
    if (step === 2) {
        const serviciosRows = document.querySelectorAll('#servicios-body tr');
        if (serviciosRows.length === 0) {
            alert('Debe agregar al menos un servicio');
            return false;
        }
    }
    return true;
}

function updateResumen() {
    const clienteSelect = document.getElementById('orden-cliente_id');
    const vehiculoSelect = document.getElementById('orden-vehiculo_id');
    const prioridadSelect = document.getElementById('orden-prioridad');
    
    document.getElementById('resumen-cliente').textContent = clienteSelect.options[clienteSelect.selectedIndex]?.text || '—';
    document.getElementById('resumen-vehiculo').textContent = vehiculoSelect.options[vehiculoSelect.selectedIndex]?.text || '—';
    document.getElementById('resumen-prioridad').textContent = prioridadSelect.options[prioridadSelect.selectedIndex]?.text || '—';
    
    const serviciosCount = document.querySelectorAll('#servicios-body tr').length;
    document.getElementById('resumen-servicios').textContent = serviciosCount;
    
    let total = 0;
    document.querySelectorAll('#servicios-body tr').forEach(row => {
        const qty = parseFloat(row.querySelector('input[name*="cantidad"]')?.value || 0);
        const precio = parseFloat(row.querySelector('input[name*="precio_unitario"]')?.value || 0);
        total += qty * precio;
    });
    document.getElementById('resumen-total').textContent = '$' + total.toLocaleString('es-CL', {minimumFractionDigits: 0, maximumFractionDigits: 0});
}

document.getElementById('add-servicio').addEventListener('click', function() {
    const tbody = document.getElementById('servicios-body');
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><select name="servicios[\${rowIdx}][servicio_id]" class="form-select form-select-sm svc-select" required>\${buildSelectOptions(null)}</select></td>
        <td><input type="number" name="servicios[\${rowIdx}][cantidad]" class="form-control form-control-sm" value="1" min="1" required></td>
        <td><input type="number" name="servicios[\${rowIdx}][precio_unitario]" class="form-control form-control-sm" value="0" min="0" step="0.01" required></td>
        <td><input type="text" name="servicios[\${rowIdx}][nota]" class="form-control form-control-sm" maxlength="500" placeholder="Nota opcional del servicio"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-trash"></i></button></td>
    `;
    tbody.appendChild(tr);
    rowIdx++;
});

document.getElementById('servicios-body').addEventListener('click', function(e) {
    if (e.target.closest('.remove-row')) {
        e.target.closest('tr').remove();
    }
});

document.getElementById('servicios-body').addEventListener('change', function(e) {
    if (e.target.classList.contains('svc-select')) {
        const opt = e.target.selectedOptions[0];
        const precio = opt ? opt.dataset.precio : 0;
        const row = e.target.closest('tr');
        row.querySelector('input[name*="precio_unitario"]').value = precio || 0;
    }
});

document.getElementById('orden-cliente_id').addEventListener('change', function() {
    const clienteId = this.value;
    const select = document.getElementById('orden-vehiculo_id');
    select.innerHTML = '<option value="">— Cargando... —</option>';
    if (!clienteId) { select.innerHTML = '<option value="">— Seleccione vehículo —</option>'; return; }
    fetch('{$ajaxUrl}?clienteId=' + clienteId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">— Seleccione vehículo —</option>';
            data.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id; opt.textContent = v.text;
                select.appendChild(opt);
            });
        });
});

// Inicializar Select2
\$(document).ready(function() {
    $('.select2-cliente').select2({
        placeholder: '— Seleccione cliente —',
        allowClear: true,
        language: 'es'
    });
    
    $('.select2-vehiculo').select2({
        placeholder: '— Seleccione vehículo —',
        allowClear: true,
        language: 'es'
    });
});
JS;
$this->registerJs($js);
?>

<?php
$this->registerJs(<<<'JS'
(function(){
    const addBtn = document.getElementById('add-checklist-item');
    const input = document.getElementById('checklist-new-item');
    const list = document.getElementById('checklist-items-list');

    if (addBtn) {
        addBtn.addEventListener('click', function(){
            const val = (input.value || '').trim();
            if (!val) return;
            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex gap-2 align-items-center mb-1';
            wrapper.innerHTML = `<input type="hidden" name="checklist_items[]" value="${val}">` +
                `<div class="flex-grow-1">${val}</div>` +
                `<button type="button" class="btn btn-sm btn-outline-danger remove-checklist-item"><i class="bi bi-trash"></i></button>`;
            list.appendChild(wrapper);
            input.value = '';
        });
    }

    if (list) {
        // Delegated remove
        list.addEventListener('click', function(e){
            if (e.target.closest('.remove-checklist-item')) {
                const row = e.target.closest('.d-flex');
                if (row) row.remove();
            }
        });
    }
})();
JS
);
?>

<style>
.wizard-steps .step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    transition: all 0.3s ease;
}

.wizard-steps .step-item.active .step-circle {
    background-color: #0d6efd;
    color: white;
}

.wizard-step {
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
