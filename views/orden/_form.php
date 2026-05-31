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
?>

<?php $form = ActiveForm::begin(['id' => 'orden-form']); ?>

<div class="row g-3">
    <div class="col-md-5">
        <?= $form->field($model, 'cliente_id')->dropDownList(
            array_merge(['' => '— Seleccione cliente —'],
                array_combine(
                    array_map(fn($c) => $c->id, $clientes),
                    array_map(fn($c) => $c->nombre, $clientes)
                )
            ),
            ['id' => 'orden-cliente_id']
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
    <div class="col-md-5">
        <?= $form->field($model, 'vehiculo_id')->dropDownList(
            array_merge(['' => '— Seleccione vehículo —'], $vehiculosOptions),
            ['id' => 'orden-vehiculo_id']
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
    <div class="col-md-2">
        <?= $form->field($model, 'prioridad')->dropDownList(OrdenServicio::getPrioridadesList()) ?>
    </div>
    <div class="col-12">
        <?= $form->field($model, 'notas_generales')->textarea(['rows' => 2, 'placeholder' => 'Observaciones generales...']) ?>
    </div>
</div>

<!-- Servicios dinámicos -->
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

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Orden' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

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

function buildSelectOptions(selected) {
    let opts = '<option value="">— Seleccione —</option>';
    serviciosData.forEach(s => {
        const sel = s.id == selected ? ' selected' : '';
        opts += `<option value="\${s.id}" data-precio="\${s.precio}"\${sel}>\${s.nombre}</option>`;
    });
    return opts;
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
JS;
$this->registerJs($js);
?>
