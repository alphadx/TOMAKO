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

$clientes = Cliente::find()->select(['id', 'nombre'])->asArray()->all();
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

                    <!-- Cliente Selection with Search -->
                    <div class="mb-3">
                        <label class="form-label">Cliente *</label>
                        <input
                            type="text"
                            id="cliente-search"
                            class="form-control"
                            placeholder="Buscar cliente..."
                            autocomplete="off"
                        >
                        <input
                            type="hidden"
                            name="cliente_id"
                            id="cliente_id"
                            required
                        >
                        <div id="cliente-results" class="list-group mt-2"></div>
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
                                    class="btn btn-sm btn-outline-primary"
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
// Dynamic client search
document.getElementById('cliente-search').addEventListener('input', function(e) {
    const query = e.target.value;
    if (query.length < 2) return;
    
    fetch(`/api/clientes/search?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            const resultsDiv = document.getElementById('cliente-results');
            resultsDiv.innerHTML = '';
            data.forEach(cliente => {
                const li = document.createElement('a');
                li.href = '#';
                li.className = 'list-group-item list-group-item-action';
                li.textContent = cliente.nombre;
                li.onclick = (e) => {
                    e.preventDefault();
                    document.getElementById('cliente-search').value = cliente.nombre;
                    document.getElementById('cliente_id').value = cliente.id;
                    resultsDiv.innerHTML = '';
                    // Load vehicles for this client
                    loadVehicles(cliente.id);
                };
                resultsDiv.appendChild(li);
            });
        });
});

function loadVehicles(clienteId) {
    const vehiculoSelect = document.getElementById('vehiculo_id');
    vehiculoSelect.innerHTML = '<option value="">Cargando...</option>';
    
    fetch(`/api/vehiculos/by-cliente?cliente_id=${clienteId}`)
        .then(r => r.json())
        .then(data => {
            vehiculoSelect.innerHTML = '<option value="">Seleccione vehículo...</option>';
            data.forEach(veh => {
                const opt = document.createElement('option');
                opt.value = veh.id;
                opt.textContent = `${veh.patente} - ${veh.marca} ${veh.modelo}`;
                vehiculoSelect.appendChild(opt);
            });
        });
}
JS
) ?>
