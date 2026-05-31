<?php
/** @var yii\web\View $this */
/** @var app\models\Cita $model */
/** @var app\models\Cliente[] $clientes */
/** @var app\models\Servicio[] $servicios */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

// Registrar CSS de Select2 para los modales de alta rápida
$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
$this->registerCssFile('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');

$selectedServicios = array_map(fn($s) => $s->id, $model->isNewRecord ? [] : $model->servicios);

// Pre-cargar vehículos si hay cliente seleccionado
$vehiculosOptions = [];
if ($model->cliente_id) {
    foreach (\app\models\Vehiculo::find()->where(['cliente_id' => $model->cliente_id, 'status' => 1])->all() as $v) {
        $vehiculosOptions[$v->id] = "{$v->patente} – {$v->marca} {$v->modelo}";
    }
}
?>

<?php $form = ActiveForm::begin(['id' => 'cita-form']); ?>

<div class="row g-3">
    <!-- Cliente -->
    <div class="col-md-6">
        <?= $form->field($model, 'cliente_id')->dropDownList(
            \yii\helpers\ArrayHelper::merge(['' => '— Seleccione cliente —'], \yii\helpers\ArrayHelper::map($clientes, 'id', 'nombre')),
            ['id' => 'cita-cliente_id']
        ) ?>
        <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#cita-cliente-quick-modal"
        >
            <i class="bi bi-person-plus me-1"></i>Alta rápida
        </button>
    </div>
    
    <!-- Vehículo (poblado por AJAX) -->
    <div class="col-md-6" id="cita-vehiculo-container" style="<?= !$model->cliente_id ? 'display:none;' : '' ?>">
        <?= $form->field($model, 'vehiculo_id')->dropDownList(
            array_merge(['' => '— Seleccione vehículo —'], $vehiculosOptions),
            ['id' => 'cita-vehiculo_id']
        ) ?>
        <button
            type="button"
            class="btn btn-outline-primary btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#cita-vehiculo-quick-modal"
            <?= !$model->cliente_id ? 'disabled' : '' ?>
        >
            <i class="bi bi-car-front-plus me-1"></i>Alta rápida
        </button>
    </div>
    
    <!-- Patente temporal (para vehículos sin registro) -->
    <div class="col-md-6" id="cita-patente-temporal-container" style="display: none;">
        <?= $form->field($model, 'patente_temporal')->textInput([
            'id' => 'cita-patente-temporal',
            'placeholder' => 'Ej: AB-1234 o ABCD-12'
        ]) ?>
        <small class="text-muted">Use esta sección si el vehículo no está registrado</small>
    </div>

    <!-- Fecha -->
    <div class="col-md-4">
        <?= $form->field($model, 'fecha')->input('date') ?>
    </div>
    <!-- Hora inicio -->
    <div class="col-md-4">
        <?= $form->field($model, 'hora_inicio')->input('time', ['id' => 'cita-hora_inicio']) ?>
    </div>
    <!-- Hora fin (definida por usuario o ajustable) -->
    <div class="col-md-4">
        <?= $form->field($model, 'hora_fin')->input('time', ['id' => 'cita-hora_fin']) ?>
        <small class="text-muted d-block mt-1">
            <i class="bi bi-clock"></i> Defina la hora de fin o use el botón de ajuste automático
        </small>
    </div>

    <!-- Notas -->
    <div class="col-12">
        <?= $form->field($model, 'notas')->textarea(['rows' => 3, 'placeholder' => 'Observaciones adicionales...']) ?>
    </div>

    <!-- Servicios -->
    <div class="col-12">
        <label class="form-label fw-bold d-flex justify-content-between align-items-center">
            <span>Servicios</span>
            <span id="tiempo-aproximado" class="badge bg-info text-dark d-none">
                <i class="bi bi-clock me-1"></i><span id="tiempo-total">0m</span>
            </span>
        </label>
        <div class="row g-2">
            <?php foreach ($servicios as $s): ?>
            <div class="col-md-4">
                <div class="form-check">
                    <input class="form-check-input servicio-checkbox" type="checkbox"
                           name="servicio_ids[]" value="<?= $s->id ?>"
                           data-duracion="<?= (int) ($s->duracion_estimada ?? 0) ?>"
                           id="svc_<?= $s->id ?>"
                           <?= in_array($s->id, $selectedServicios, true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="svc_<?= $s->id ?>">
                        <?= Html::encode($s->nombre) ?>
                        <span class="text-muted small">($ <?= number_format((float) $s->precio_base, 0, ',', '.') ?>)</span>
                        <?php if ($s->duracion_estimada > 0): ?>
                            <span class="text-muted small ms-1">(<?= $s->duracion_estimada ?>m)</span>
                        <?php endif; ?>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="alert alert-warning mt-3 d-none" id="alerta-bloqueo-horario">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Atención:</strong> El horario seleccionado no es suficiente para los servicios elegidos.
            La hora de fin sugerida es <strong id="hora-fin-sugerida"></strong>.
        </div>
        <div class="mt-3">
            <button type="button" class="btn btn-outline-info btn-sm" id="btn-ajustar-hora-fin">
                <i class="bi bi-clock-history me-1"></i>Ajustar hora fin automáticamente a los servicios
            </button>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Registrar Cita' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

<?= $this->render('@app/views/cliente/_modal_create', [
    'modalId' => 'cita-cliente-quick-modal',
    'formId' => 'cita-cliente-quick-form',
    'clienteSelectId' => 'cita-cliente_id',
]) ?>

<?= $this->render('@app/views/vehiculo/_modal_create', [
    'modalId' => 'cita-vehiculo-quick-modal',
    'formId' => 'cita-vehiculo-quick-form',
    'vehiculoSelectId' => 'cita-vehiculo_id',
    'clienteSelectId' => 'cita-cliente_id',
]) ?>

<?php
$ajaxUrl = Url::to(['vehiculos-por-cliente']);
$js = <<<JS
// Mostrar/ocultar contenedor de vehículo según si hay cliente seleccionado
document.getElementById('cita-cliente_id').addEventListener('change', function() {
    const clienteId = this.value;
    const select = document.getElementById('cita-vehiculo_id');
    const vehiculoBtn = document.querySelector('[data-bs-target="#cita-vehiculo-quick-modal"]');
    const vehiculoContainer = document.getElementById('cita-vehiculo-container');
    const patenteContainer = document.getElementById('cita-patente-temporal-container');
    
    // Mostrar/ocultar contenedores según selección
    if (vehiculoContainer) {
        vehiculoContainer.style.display = clienteId ? 'block' : 'none';
    }
    if (patenteContainer) {
        patenteContainer.style.display = 'none';
    }
    
    // Limpiar vehículo y patente temporal cuando se cambia cliente
    if (clienteId) {
        if (select) select.value = '';
        const patenteInput = document.getElementById('cita-patente-temporal');
        if (patenteInput) patenteInput.value = '';
    } else {
        if (select) select.innerHTML = '<option value="">— Seleccione vehículo —</option>';
        if (vehiculoBtn) vehiculoBtn.disabled = true;
        return;
    }
    if (vehiculoBtn) vehiculoBtn.disabled = false;
    fetch('{$ajaxUrl}?clienteId=' + clienteId)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">— Seleccione vehículo —</option>';
            data.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.text;
                select.appendChild(opt);
            });
        });
});

// Mostrar/ocultar patente temporal según si hay vehículo seleccionado
document.getElementById('cita-vehiculo_id')?.addEventListener('change', function() {
    const vehiculoId = this.value;
    const patenteContainer = document.getElementById('cita-patente-temporal-container');
    
    if (patenteContainer) {
        patenteContainer.style.display = vehiculoId ? 'none' : 'block';
    }
    
    // Limpiar patente temporal cuando se selecciona vehículo
    if (vehiculoId) {
        const patenteInput = document.getElementById('cita-patente-temporal');
        if (patenteInput) patenteInput.value = '';
    }
});

// Sincronizar cliente_id al abrir el modal de vehículo
document.querySelector('[data-bs-target="#cita-vehiculo-quick-modal"]').addEventListener('click', function() {
    const clienteSelect = document.getElementById('cita-cliente_id');
    const form = document.getElementById('cita-vehiculo-quick-form');
    if (clienteSelect && clienteSelect.value && form) {
        let hiddenInput = form.querySelector('input[name="Vehiculo[cliente_id]"]');
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'Vehiculo[cliente_id]';
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = clienteSelect.value;
    }
});

// Cálculo del tiempo aproximado en el formulario de cita
function formatearTiempo(minutos) {
    if (minutos < 60) {
        return minutos + 'm';
    }
    const horas = Math.floor(minutos / 60);
    const resto = minutos % 60;
    return resto > 0 ? horas + 'h ' + resto + 'm' : horas + 'h';
}

function minutosAHora(minutos) {
    const horas = Math.floor(minutos / 60);
    const mins = minutos % 60;
    return String(horas).padStart(2, '0') + ':' + String(mins).padStart(2, '0');
}

function horaAMinutos(hora) {
    const [horas, minutos] = hora.split(':').map(Number);
    return horas * 60 + minutos;
}

function actualizarTiempoAproximado() {
    let total = 0;
    document.querySelectorAll('.servicio-checkbox:checked').forEach(cb => {
        total += parseInt(cb.dataset.duracion || 0);
    });
    const badge = document.getElementById('tiempo-aproximado');
    const span = document.getElementById('tiempo-total');
    if (total > 0) {
        span.textContent = formatearTiempo(total);
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
    
    // HU-018: Bloqueo automático de horas según servicios seleccionados
    validarYBloquearHorario(total);
}

/**
 * HU-018: Valida que el horario sea suficiente para los servicios seleccionados
 * y muestra una alerta si es necesario, pero NO ajusta automáticamente.
 */
function validarYBloquearHorario(duracionTotalMinutos) {
    const horaInicioInput = document.getElementById('cita-hora_inicio');
    const horaFinInput = document.getElementById('cita-hora_fin');
    const alerta = document.getElementById('alerta-bloqueo-horario');
    const horaSugeridaSpan = document.getElementById('hora-fin-sugerida');
    
    if (!horaInicioInput || !horaFinInput || !horaInicioInput.value || duracionTotalMinutos === 0) {
        if (alerta) alerta.classList.add('d-none');
        return;
    }
    
    const inicioMinutos = horaAMinutos(horaInicioInput.value);
    const finMinutosActual = horaFinInput.value ? horaAMinutos(horaFinInput.value) : inicioMinutos + 60;
    const finMinutosRequerido = inicioMinutos + duracionTotalMinutos;
    
    // Si el horario actual no es suficiente, mostrar alerta pero NO ajustar automáticamente
    if (finMinutosActual < finMinutosRequerido) {
        const nuevaHoraFin = minutosAHora(finMinutosRequerido);
        
        if (alerta && horaSugeridaSpan) {
            horaSugeridaSpan.textContent = nuevaHoraFin;
            alerta.classList.remove('d-none');
        }
    } else {
        if (alerta) alerta.classList.add('d-none');
    }
}

// Botón para ajustar hora fin automáticamente
document.getElementById('btn-ajustar-hora-fin')?.addEventListener('click', function() {
    let total = 0;
    document.querySelectorAll('.servicio-checkbox:checked').forEach(cb => {
        total += parseInt(cb.dataset.duracion || 0);
    });
    
    if (total === 0) {
        alert('Seleccione al menos un servicio para ajustar la hora de fin.');
        return;
    }
    
    const horaInicioInput = document.getElementById('cita-hora_inicio');
    const horaFinInput = document.getElementById('cita-hora_fin');
    
    if (!horaInicioInput || !horaInicioInput.value) {
        alert('Debe definir una hora de inicio primero.');
        return;
    }
    
    const inicioMinutos = horaAMinutos(horaInicioInput.value);
    const finMinutosRequerido = inicioMinutos + total;
    horaFinInput.value = minutosAHora(finMinutosRequerido);
    
    // Ocultar alerta si estaba visible
    const alerta = document.getElementById('alerta-bloqueo-horario');
    if (alerta) alerta.classList.add('d-none');
});

// Escuchar cambios en la hora de inicio para recalcular
document.getElementById('cita-hora_inicio')?.addEventListener('change', function() {
    let total = 0;
    document.querySelectorAll('.servicio-checkbox:checked').forEach(cb => {
        total += parseInt(cb.dataset.duracion || 0);
    });
    if (total > 0) {
        validarYBloquearHorario(total);
    }
});

document.querySelectorAll('.servicio-checkbox').forEach(cb => {
    cb.addEventListener('change', actualizarTiempoAproximado);
});

// Calcular al cargar por si hay servicios preseleccionados
document.addEventListener('DOMContentLoaded', actualizarTiempoAproximado);

// Inicializar Select2 para marca y modelo en los modales de alta rápida
// Se inicializa cuando se abre el modal
document.getElementById('cita-vehiculo-quick-modal')?.addEventListener('shown.bs.modal', function() {
    if (window.VehiculoMarcaModeloSelect2) {
        window.VehiculoMarcaModeloSelect2.initForm(
            'cita-vehiculo-quick-form',
            'cita-vehiculo-quick-form-marca',
            'cita-vehiculo-quick-form-modelo'
        );
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
