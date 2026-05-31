<?php
/**
 * Vista de Análisis de Rentabilidad por Servicio (HU-023)
 * 
 * @var yii\web\View $this
 * @var string $periodo
 * @var array $tablaMargenes
 * @var array $top10
 * @var array $bottom5
 * @var array $datosGraficos
 * @var array $comparativa
 * @var array $periodosDisponibles
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Análisis de Rentabilidad por Servicio';
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="servicio-rentabilidad">
    <div class="page-header mb-4">
        <h1 class="mb-3">
            <i class="bi bi-graph-up-arrow"></i> <?= Html::encode($this->title) ?>
        </h1>
    </div>

    <!-- Filtros y Acciones -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label for="periodo-selector" class="form-label">Período:</label>
                    <select id="periodo-selector" class="form-select" onchange="cambiarPeriodo(this.value)">
                        <?php foreach ($periodosDisponibles as $p): ?>
                            <option value="<?= Html::encode($p) ?>" <?= $p === $periodo ? 'selected' : '' ?>>
                                <?= date('F Y', strtotime($p . '-01')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 text-end">
                    <?= Html::a('<i class="bi bi-file-earmark-spreadsheet"></i> Exportar Excel', 
                        ['exportar-excel', 'periodo' => $periodo], 
                        ['class' => 'btn btn-success me-2']) ?>
                    <?= Html::a('<i class="bi bi-file-earmark-pdf"></i> Exportar PDF', 
                        ['exportar-pdf', 'periodo' => $periodo], 
                        ['class' => 'btn btn-danger me-2']) ?>
                    <button class="btn btn-primary" onclick="recalcularRentabilidad()">
                        <i class="bi bi-arrow-clockwise"></i> Recalcular
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards - Comparativa -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6>Ingreso Total</h6>
                    <h3>$<?= number_format($comparativa['datos']['ingreso_total']['actual'] ?? 0, 0) ?></h3>
                    <small class="<?= ($comparativa['datos']['ingreso_total']['variacion_porcentaje'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="bi bi-arrow-<?= ($comparativa['datos']['ingreso_total']['variacion_porcentaje'] ?? 0) >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($comparativa['datos']['ingreso_total']['variacion_porcentaje'] ?? 0) ?>% vs mes anterior
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6>Costo Total</h6>
                    <h3>$<?= number_format($comparativa['datos']['costo_total']['actual'] ?? 0, 0) ?></h3>
                    <small class="<?= ($comparativa['datos']['costo_total']['variacion_porcentaje'] ?? 0) <= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="bi bi-arrow-<?= ($comparativa['datos']['costo_total']['variacion_porcentaje'] ?? 0) <= 0 ? 'down' : 'up' ?>"></i>
                        <?= abs($comparativa['datos']['costo_total']['variacion_porcentaje'] ?? 0) ?>% vs mes anterior
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Utilidad Bruta</h6>
                    <h3>$<?= number_format($comparativa['datos']['utilidad_bruta']['actual'] ?? 0, 0) ?></h3>
                    <small class="<?= ($comparativa['datos']['utilidad_bruta']['variacion_porcentaje'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="bi bi-arrow-<?= ($comparativa['datos']['utilidad_bruta']['variacion_porcentaje'] ?? 0) >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($comparativa['datos']['utilidad_bruta']['variacion_porcentaje'] ?? 0) ?>% vs mes anterior
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Margen Promedio</h6>
                    <h3><?= number_format($comparativa['datos']['margen_promedio']['actual'] ?? 0, 1) ?>%</h3>
                    <small class="<?= ($comparativa['datos']['margen_promedio']['variacion_porcentaje'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                        <i class="bi bi-arrow-<?= ($comparativa['datos']['margen_promedio']['variacion_porcentaje'] ?? 0) >= 0 ? 'up' : 'down' ?>"></i>
                        <?= abs($comparativa['datos']['margen_promedio']['variacion_porcentaje'] ?? 0) ?>% vs mes anterior
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-trophy"></i> Top 10 Servicios Más Rentables</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoTop10" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Bottom 5 Servicios Menos Rentables</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoBottom5" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Márgenes -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-table"></i> Márgenes por Servicio</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Servicio</th>
                            <th class="text-center">Órdenes</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-end">Costo Servicio</th>
                            <th class="text-end">Costo Repuestos</th>
                            <th class="text-end">Mano de Obra</th>
                            <th class="text-end">Overhead</th>
                            <th class="text-end">Costo Total</th>
                            <th class="text-end">Utilidad</th>
                            <th class="text-center">Margen</th>
                            <th class="text-center">Clasif.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tablaMargenes as $registro): ?>
                            <tr class="<?= $registro->margen_porcentaje < 15 ? 'table-danger' : ($registro->margen_porcentaje >= 30 ? 'table-success' : 'table-warning') ?>">
                                <td><strong><?= Html::encode($registro->servicio->nombre ?? "Servicio #{$registro->servicio_id}") ?></strong></td>
                                <td class="text-center"><?= $registro->total_ordenes ?></td>
                                <td class="text-end">$<?= number_format($registro->ingreso_total, 2) ?></td>
                                <td class="text-end">$<?= number_format($registro->costo_servicio, 2) ?></td>
                                <td class="text-end">$<?= number_format($registro->costo_repuestos, 2) ?></td>
                                <td class="text-end">$<?= number_format($registro->costo_mano_obra, 2) ?></td>
                                <td class="text-end">$<?= number_format($registro->overhead, 2) ?></td>
                                <td class="text-end"><strong>$<?= number_format($registro->costo_total, 2) ?></strong></td>
                                <td class="text-end <?= $registro->utilidad_bruta >= 0 ? 'text-success' : 'text-danger' ?>">
                                    $<?= number_format($registro->utilidad_bruta, 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $registro->getClasificacionClass() ?>">
                                        <?= number_format($registro->margen_porcentaje, 1) ?>%
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?= Html::encode($registro->getClasificacionMargen()) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tablaMargenes)): ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <i class="bi bi-info-circle"></i> No hay datos de rentabilidad para este período.
                                    Haga clic en "Recalcular" para generar los datos.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const periodoActual = '<?= Html::encode($periodo) ?>';

// Datos para gráficos desde PHP
const datosTop10 = <?= json_encode($datosGraficos['top10']) ?>;
const datosBottom5 = <?= json_encode($datosGraficos['bottom5']) ?>;

// Gráfico Top 10
new Chart(document.getElementById('graficoTop10'), {
    type: 'bar',
    data: {
        labels: datosTop10.labels,
        datasets: [{
            label: 'Margen (%)',
            data: datosTop10.margenes,
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgba(40, 167, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Margen (%)' }
            }
        }
    }
});

// Gráfico Bottom 5
new Chart(document.getElementById('graficoBottom5'), {
    type: 'bar',
    data: {
        labels: datosBottom5.labels,
        datasets: [{
            label: 'Margen (%)',
            data: datosBottom5.margenes,
            backgroundColor: 'rgba(220, 53, 69, 0.7)',
            borderColor: 'rgba(220, 53, 69, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: false,
                title: { display: true, text: 'Margen (%)' }
            }
        }
    }
});

// Función para cambiar período
function cambiarPeriodo(periodo) {
    window.location.href = '<?= Url::to(['rentabilidad']) ?>?periodo=' + periodo;
}

// Función para recalcular rentabilidad
function recalcularRentabilidad() {
    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Calculando...';
    
    fetch('<?= Url::to(['recalcular-rentabilidad']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ periodo: periodoActual })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Rentabilidad recalculada exitosamente. Se recargará la página.');
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    })
    .catch(error => {
        alert('Error en la comunicación: ' + error);
        btn.disabled = false;
        btn.innerHTML = originalContent;
    });
}
</script>

<style>
.servicio-rentabilidad .card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
.servicio-rentabilidad .card-header {
    font-weight: 600;
}
.table td {
    vertical-align: middle;
}
.badge {
    min-width: 60px;
}
</style>
