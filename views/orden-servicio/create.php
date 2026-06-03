<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Cliente;
use app\models\Vehiculo;
use app\models\Servicio;

/** @var yii\web\View $this */

$this->title = 'Nueva Orden de Servicio';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$clientesList = Cliente::find()
    ->select(['nombre', 'id'])
    ->orderBy(['nombre' => SORT_ASC])
    ->indexBy('id')
    ->column();
$servicios = Servicio::find()->select(['id', 'nombre', 'precio_base'])->asArray()->all();
?>

<div class="orden-servicio-create">
    <div class="row">
        <!-- Form Column (2/3) -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><?= Html::encode($this->title) ?></h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin() ?>

                    <!-- Cliente Selection -->
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <?= Html::dropDownList('cliente_id', null, $clientesList, [
                            'id' => 'cliente_id',
                            'class' => 'form-control',
                            'prompt' => 'Seleccione cliente...',
                            'required' => true,
                        ]) ?>
                    </div>

                    <!-- Vehicle Selection -->
                    <div class="mb-3">
                        <label class="form-label">Vehículo *</label>
                        <select name="vehiculo_id" id="vehiculo_id" class="form-control" required>
                            <option value="">Seleccione vehículo...</option>
                        </select>
                    </div>

                    <!-- Priority -->
                    <div class="mb-3">
                        <label class="form-label">Prioridad</label>
                        <?= Html::dropDownList('prioridad', 'normal', [
                            'baja' => 'Baja',
                            'normal' => 'Normal',
                            'alta' => 'Alta',
                            'urgente' => 'Urgente',
                        ], ['class' => 'form-control']) ?>
                    </div>

                    <!-- Selected Services -->
                    <div class="mb-3">
                        <label class="form-label">Servicios Seleccionados</label>
                        <div class="border rounded p-2 bg-light">
                            <div id="servicios-seleccionados-empty" class="text-muted small">Aún no hay servicios seleccionados.</div>
                            <ul id="servicios-seleccionados-list" class="list-group list-group-flush"></ul>
                            <div id="servicios-seleccionados-total" class="fw-bold text-end mt-2 d-none"></div>
                        </div>
                        <div id="servicios-seleccionados-inputs"></div>
                    </div>

                    <div class="text-end">
                        <?= Html::a('Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
                        <?= Html::submitButton('Crear Orden', ['class' => 'btn btn-primary']) ?>
                    </div>

                    <?php ActiveForm::end() ?>
                </div>
            </div>
        </div>

        <!-- Services Column (1/3) -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6>Servicios Disponibles</h6>
                </div>
                <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    <div id="servicios-list">
                        <?php foreach ($servicios as $servicio): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border-bottom">
                                <div>
                                    <strong><?= Html::encode($servicio['nombre']) ?></strong>
                                    <br>
                                    <small class="text-muted">$<?= number_format((float)$servicio['precio_base'], 0, ',', '.') ?></small>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary js-add-servicio"
                                    data-servicio-id="<?= $servicio['id'] ?>"
                                    data-servicio-nombre="<?= $servicio['nombre'] ?>"
                                    data-servicio-precio="<?= $servicio['precio_base'] ?>"
                                >
                                    +
                                </button>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
const serviciosSeleccionados = new Map();

function formatearMoneda(valor) {
    const numero = Number.isFinite(valor) ? valor : 0;
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        maximumFractionDigits: 0,
    }).format(numero);
}

function renderServiciosSeleccionados() {
    const lista = document.getElementById('servicios-seleccionados-list');
    const empty = document.getElementById('servicios-seleccionados-empty');
    const totalEl = document.getElementById('servicios-seleccionados-total');
    const inputs = document.getElementById('servicios-seleccionados-inputs');

    lista.innerHTML = '';
    inputs.innerHTML = '';

    let total = 0;

    serviciosSeleccionados.forEach((servicio) => {
        total += servicio.precio * servicio.cantidad;

        const item = document.createElement('li');
        item.className = 'list-group-item px-0 d-flex justify-content-between align-items-center';
        item.innerHTML = `
            <div>
                <div>${servicio.nombre}</div>
                <small class="text-muted">${formatearMoneda(servicio.precio)} c/u</small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary js-decrease-servicio" data-servicio-id="${servicio.id}">-</button>
                <span class="badge bg-primary">${servicio.cantidad}</span>
                <button type="button" class="btn btn-sm btn-outline-secondary js-increase-servicio" data-servicio-id="${servicio.id}">+</button>
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-servicio" data-servicio-id="${servicio.id}">x</button>
            </div>
        `;
        lista.appendChild(item);

        for (let i = 0; i < servicio.cantidad; i += 1) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'servicio_ids[]';
            input.value = String(servicio.id);
            inputs.appendChild(input);
        }
    });

    const tieneServicios = serviciosSeleccionados.size > 0;
    empty.classList.toggle('d-none', tieneServicios);
    totalEl.classList.toggle('d-none', !tieneServicios);
    totalEl.textContent = `Total estimado: ${formatearMoneda(total)}`;
}

document.getElementById('servicios-list').addEventListener('click', function(event) {
    const button = event.target.closest('button.js-add-servicio');
    if (!button) {
        return;
    }

    const id = Number.parseInt(button.dataset.servicioId, 10);
    const nombre = button.dataset.servicioNombre || 'Servicio';
    const precio = Number.parseFloat(button.dataset.servicioPrecio || '0');

    if (!Number.isInteger(id) || id <= 0) {
        return;
    }

    const actual = serviciosSeleccionados.get(id);
    if (actual) {
        actual.cantidad += 1;
    } else {
        serviciosSeleccionados.set(id, { id, nombre, precio, cantidad: 1 });
    }

    renderServiciosSeleccionados();
});

document.getElementById('servicios-seleccionados-list').addEventListener('click', function(event) {
    const button = event.target.closest('button[data-servicio-id]');
    if (!button) {
        return;
    }

    const id = Number.parseInt(button.dataset.servicioId, 10);
    if (!Number.isInteger(id) || !serviciosSeleccionados.has(id)) {
        return;
    }

    const servicio = serviciosSeleccionados.get(id);

    if (button.classList.contains('js-remove-servicio')) {
        serviciosSeleccionados.delete(id);
    } else if (button.classList.contains('js-increase-servicio')) {
        servicio.cantidad += 1;
    } else if (button.classList.contains('js-decrease-servicio')) {
        servicio.cantidad -= 1;
        if (servicio.cantidad <= 0) {
            serviciosSeleccionados.delete(id);
        }
    }

    renderServiciosSeleccionados();
});

// Load vehicles when client is selected
document.getElementById('cliente_id').addEventListener('change', function() {
    const clienteId = this.value;
    const vehiculoSelect = document.getElementById('vehiculo_id');

    vehiculoSelect.innerHTML = '<option value="">Seleccione vehículo...</option>';

    if (!clienteId) return;

    vehiculoSelect.innerHTML = '<option value="">Cargando...</option>';

    fetch(`/orden-servicio/vehiculos-by-cliente?cliente_id=${clienteId}`)
        .then(r => r.json())
        .then(data => {
            vehiculoSelect.innerHTML = '<option value="">Seleccione vehículo...</option>';
            data.forEach(veh => {
                const opt = document.createElement('option');
                opt.value = veh.id;
                opt.textContent = `${veh.patente} - ${veh.marca} ${veh.modelo}`;
                vehiculoSelect.appendChild(opt);
            });
        })
        .catch(() => {
            vehiculoSelect.innerHTML = '<option value="">Error al cargar vehículos</option>';
        });
});

renderServiciosSeleccionados();
JS
) ?>
