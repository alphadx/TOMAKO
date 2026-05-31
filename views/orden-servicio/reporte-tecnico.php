<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var yii\web\View $this */

$this->title = 'Reporte de Servicios por Técnico';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-servicio-reporte-tecnico">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Filtros -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label>Desde:</label>
                    <input type="date" class="form-control" id="desde">
                </div>
                <div class="col-md-3">
                    <label>Hasta:</label>
                    <input type="date" class="form-control" id="hasta">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="generarReporte()">Generar</button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-secondary w-100" onclick="exportarCSV()">Exportar CSV</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Reporte -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped" id="tabla-reporte">
                <thead>
                    <tr>
                        <th>Técnico</th>
                        <th class="text-end">Órdenes Asignadas</th>
                        <th class="text-end">Órdenes Completadas</th>
                        <th class="text-end">Horas Estimadas</th>
                        <th class="text-end">Ingresos Generados</th>
                    </tr>
                </thead>
                <tbody id="reporte-body">
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            Seleccione fechas y haga clic en "Generar"
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th>TOTAL</th>
                        <th class="text-end" id="total-ordenes">0</th>
                        <th class="text-end" id="total-completadas">0</th>
                        <th class="text-end" id="total-horas">0</th>
                        <th class="text-end" id="total-ingresos">$0</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
async function generarReporte() {
    const desde = document.getElementById('desde').value;
    const hasta = document.getElementById('hasta').value;
    
    if (!desde || !hasta) {
        alert('Seleccione ambas fechas');
        return;
    }
    
    try {
        const response = await fetch('/api/orden-servicio/reporte-tecnico?desde=' + desde + '&hasta=' + hasta);
        const data = await response.json();
        
        const tbody = document.getElementById('reporte-body');
        tbody.innerHTML = '';
        
        let totalOrdenes = 0;
        let totalCompletadas = 0;
        let totalHoras = 0;
        let totalIngresos = 0;
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.tecnico_nombre}</td>
                <td class="text-end">${row.ordenes_asignadas}</td>
                <td class="text-end">${row.ordenes_completadas}</td>
                <td class="text-end">${row.horas_estimadas}</td>
                <td class="text-end">$${row.ingresos.toLocaleString()}</td>
            `;
            tbody.appendChild(tr);
            
            totalOrdenes += row.ordenes_asignadas;
            totalCompletadas += row.ordenes_completadas;
            totalHoras += row.horas_estimadas;
            totalIngresos += row.ingresos;
        });
        
        document.getElementById('total-ordenes').textContent = totalOrdenes;
        document.getElementById('total-completadas').textContent = totalCompletadas;
        document.getElementById('total-horas').textContent = totalHoras;
        document.getElementById('total-ingresos').textContent = '$' + totalIngresos.toLocaleString();
        
    } catch (error) {
        console.error('Error:', error);
        alert('Error al generar reporte');
    }
}

function exportarCSV() {
    const table = document.getElementById('tabla-reporte');
    let csv = [];
    table.querySelectorAll('tr').forEach(row => {
        let rowData = [];
        row.querySelectorAll('td, th').forEach(cell => {
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'reporte-tecnicos-' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
}
JS
) ?>
