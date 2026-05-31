<?php
/** @var yii\web\View $this */
/** @var int $mes */
/** @var int $anio */
/** @var int $diasEnMes */
/** @var array $citasPorDia */
/** @var array $citasConEstadoPorDia */
/** @var array $citasOrdenadasPorDia */
/** @var int $primerDiaSemana */
/** @var string $fechaSeleccionada */
/** @var app\models\Cita[] $citasDia */
/** @var int $citasActivasDia */
/** @var string $mesIso */
/** @var app\components\services\CitaService $service */

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Cita;

$this->title = 'Calendario de Citas';
$this->params['breadcrumbs'][] = ['label' => 'Citas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$nombresMes = ['', 'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$mesPrev = $mes - 1; $anioPrev = $anio;
if ($mesPrev < 1)  { $mesPrev = 12; $anioPrev--; }
$mesSig  = $mes + 1; $anioSig  = $anio;
if ($mesSig > 12) { $mesSig = 1;  $anioSig++;  }
$hoy = date('Y-m-d');

// Colores por estado (coherentes con histograma y leyenda)
$coloresEstadoCalendario = [
    'pendiente' => '#ffc107',      // amarillo
    'confirmada' => '#0d6efd',     // azul
    'en_progreso' => '#0dcaf0',    // celeste
    'completada' => '#198754',     // verde
    'cancelada' => '#6c757d',      // gris
    'no_show' => '#dc3545',        // rojo
];
?>

<div class="cita-calendario">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-calendar3 me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-list me-1"></i>Ver Listado', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <?= Html::a('<i class="bi bi-chevron-left"></i>', ['calendario', 'mes' => $mesPrev, 'anio' => $anioPrev], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                    <h5 class="mb-0"><?= $nombresMes[$mes] . ' ' . $anio ?></h5>
                    <?= Html::a('<i class="bi bi-chevron-right"></i>', ['calendario', 'mes' => $mesSig, 'anio' => $anioSig], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                </div>
                <div class="card-body p-2">
                    <table class="table table-bordered text-center mb-0" style="table-layout:fixed">
                        <thead class="table-dark">
                            <tr>
                                <th>Lun</th><th>Mar</th><th>Mié</th><th>Jue</th>
                                <th>Vie</th><th>Sáb</th><th>Dom</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $dia = 1;
                            // primerDiaSemana: 1=Lun ... 7=Dom
                            $celdasVacias = $primerDiaSemana - 1;
                            $totalCeldas  = $celdasVacias + $diasEnMes;
                            $filas = (int) ceil($totalCeldas / 7);
                        ?>
                        <?php for ($f = 0; $f < $filas; $f++): ?>
                        <tr style="height:88px">
                            <?php for ($c = 0; $c < 7; $c++):
                                $celda = $f * 7 + $c;
                                $esDia = $celda >= $celdasVacias && $dia <= $diasEnMes;
                            ?>
                            <td class="p-1 align-top <?= $esDia && sprintf('%04d-%02d-%02d', $anio, $mes, $dia) === $hoy ? 'table-warning' : '' ?>">
                                <?php if ($esDia):
                                    $fechaDia = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
                                    $count    = $citasPorDia[$fechaDia] ?? 0;
                                    $esSeleccionado = $fechaDia === $fechaSeleccionada;
                                ?>
                                <div class="d-flex flex-column align-items-center" style="width:100%;">
                                    <!-- Número del día -->
                                    <div class="fw-bold small">
                                        <?= Html::a((string) $dia, ['calendario', 'mes' => $mes, 'anio' => $anio, 'fecha' => $fechaDia], [
                                            'class' => $esSeleccionado ? 'text-decoration-underline' : 'text-decoration-none',
                                        ]) ?>
                                    </div>

                                    <?php if ($count > 0): ?>
                                        <div class="small text-secondary mt-1"><?= Html::encode("{$count} cita" . ($count !== 1 ? 's' : '')) ?></div>
                                    <?php endif; ?>
                                </div>
                                <?php $dia++; endif; ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        Total de citas por día mostrado en el calendario.
                    </div>
                    <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Cita', ['create'], ['class' => 'btn btn-success btn-sm']) ?>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-bar-chart me-1"></i>Estadísticas del mes</strong>
                    <small class="text-muted" id="stats-resumen"></small>
                </div>
                <div class="card-body">
                    <div id="stats-bars" class="d-flex align-items-end gap-1" style="height:180px;"></div>
                    <div class="small text-muted mt-2" id="stats-labels"></div>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header"><strong><i class="bi bi-palette me-1"></i>Leyenda de estados</strong></div>
                <div class="card-body d-flex flex-wrap gap-2">
                    <span class="badge bg-warning text-dark">Pendiente</span>
                    <span class="badge bg-primary">Confirmada</span>
                    <span class="badge bg-info text-dark">En Progreso</span>
                    <span class="badge bg-success">Completada</span>
                    <span class="badge bg-secondary">Cancelada</span>
                    <span class="badge bg-danger">No Show</span>
                    <span class="badge bg-dark">Quick Service (<= 60 min)</span>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?= Html::encode($fechaSeleccionada) ?></strong>
                    <span class="badge bg-primary"><?= $citasActivasDia ?> activas</span>
                </div>
                <?php
                $citasDiaCompletas = $service->getCitasDelDiaConEstados($fechaSeleccionada);
                ?>
                <?php if (empty($citasDiaCompletas)): ?>
                    <div class="card-body text-muted text-center">Sin citas para este día.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($citasDiaCompletas as $cita): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold"><?= Html::encode(substr((string) $cita->hora_inicio, 0, 5)) ?> - <?= Html::encode(substr((string) $cita->hora_fin, 0, 5)) ?></div>
                                        <div class="small"><?= Html::encode($cita->cliente?->nombre ?? 'Cliente no disponible') ?></div>
                                        <div class="small text-muted"><?= Html::encode($cita->vehiculo?->patente ?? 'Sin patente') ?></div>
                                        <?php
                                            $duracion = max(0, strtotime($cita->fecha . ' ' . $cita->hora_fin) - strtotime($cita->fecha . ' ' . $cita->hora_inicio));
                                            $esRapido = $duracion <= 3600;
                                        ?>
                                        <div class="mt-1 d-flex gap-1 flex-wrap">
                                            <span class="badge <?= Html::encode($cita->getEstadoBadgeClass()) ?>"><?= Html::encode($cita->getEstadoLabel()) ?></span>
                                            <?php if ($esRapido): ?>
                                                <span class="badge bg-dark">Quick Service</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?= Html::a('Ver', ['view', 'id' => $cita->id], ['class' => 'btn btn-outline-primary btn-sm']) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$statsUrl = Url::to(['estadisticas', 'mes' => $mesIso]);
$js = <<<JS
(function() {
    const bars = document.getElementById('stats-bars');
    const labelsNode = document.getElementById('stats-labels');
    const resumenNode = document.getElementById('stats-resumen');
    if (!bars || !labelsNode || !resumenNode) {
        return;
    }

    // Colores por estado
    const colores = {
        'pendientes': '#ffc107',      // amarillo
        'confirmadas': '#0d6efd',     // azul
        'en_progreso': '#0dcaf0',     // celeste
        'completadas': '#198754',     // verde
        'canceladas': '#6c757d',      // gris
        'no_show': '#dc3545'          // rojo
    };

    fetch('{$statsUrl}')
        .then(r => r.json())
        .then(data => {
            const labels = data.labels || [];
            const series = data.series || {};
            const totalesActivos = data.totales_activos || [];
            const resumen = data.resumen || {};
            
            const max = Math.max(1, ...totalesActivos);
            const diaSeleccionado = new URLSearchParams(window.location.search).get('fecha');

            bars.innerHTML = '';
            labels.forEach((label, idx) => {
                const totalDia = Number(totalesActivos[idx] || 0);
                const h = Math.max(2, Math.round((totalDia / max) * 160));
                
                // Crear contenedor de barra + día
                const col = document.createElement('div');
                col.className = 'd-flex flex-column align-items-center';
                col.style.width = '14px';
                col.style.flexShrink = '0';
                col.title = 'Día ' + label + ': ' + totalDia + ' citas';
                
                // Contenedor interno para la barra (flex-column-reverse para apilar de abajo hacia arriba)
                const barContainer = document.createElement('div');
                barContainer.className = 'd-flex flex-column-reverse';
                barContainer.style.width = '100%';
                barContainer.style.height = h + 'px';
                
                // Verificar si es el día seleccionado (construir fecha YYYY-MM-DD)
                const mesStr = String({$mes}).padStart(2, '0');
                const fechaDia = '{$anio}-' + mesStr + '-' + label;
                const esSeleccionado = fechaDia === diaSeleccionado;
                
                // Apilar segmentos por estado
                ['pendientes', 'confirmadas', 'en_progreso', 'completadas'].forEach(estado => {
                    const valores = series[estado] || [];
                    const valor = Number(valores[idx] || 0);
                    if (valor > 0) {
                        const segmento = document.createElement('div');
                        segmento.style.width = '100%';
                        segmento.style.height = Math.max(2, Math.round((valor / max) * 160)) + 'px';
                        segmento.style.background = colores[estado];
                        if (esSeleccionado) {
                            segmento.style.filter = 'brightness(1.1)';
                        } else {
                            segmento.style.opacity = '0.85';
                        }
                        barContainer.appendChild(segmento);
                    }
                });
                
                // Si hay canceladas o no_show, mostrarlas también (más pequeñas)
                ['canceladas', 'no_show'].forEach(estado => {
                    const valores = series[estado] || [];
                    const valor = Number(valores[idx] || 0);
                    if (valor > 0) {
                        const segmento = document.createElement('div');
                        segmento.style.width = '100%';
                        segmento.style.height = Math.max(1, Math.round((valor / max) * 80)) + 'px';
                        segmento.style.background = colores[estado];
                        segmento.style.borderTop = '1px dashed #fff';
                        if (esSeleccionado) {
                            segmento.style.filter = 'brightness(1.1)';
                        } else {
                            segmento.style.opacity = '0.85';
                        }
                        barContainer.appendChild(segmento);
                    }
                });
                
                col.appendChild(barContainer);
                
                // Agregar el número del día debajo de la barra
                const dayLabel = document.createElement('div');
                dayLabel.textContent = label;
                dayLabel.className = 'small text-muted';
                dayLabel.style.fontSize = '10px';
                dayLabel.style.marginTop = '2px';
                dayLabel.style.textAlign = 'center';
                dayLabel.style.width = '100%';
                col.appendChild(dayLabel);
                
                bars.appendChild(col);
            });

            
            resumenNode.textContent = 'Total: ' + (resumen.totales || 0)
                + ' | Confirmadas: ' + (resumen.confirmadas || 0)
                + ' | Canceladas: ' + (resumen.canceladas || 0)
                + ' | No Show: ' + (resumen.no_show || 0);
        })
        .catch(() => {
            labelsNode.textContent = 'No fue posible cargar estadísticas del mes.';
        });
})();
JS;
$this->registerJs($js);
?>
