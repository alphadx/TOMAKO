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
JS
) ?>
