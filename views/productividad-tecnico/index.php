<?php
/**
 * Reporte de Productividad de Mecánicos - HU-022
 */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Reporte de Productividad de Mecánicos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="productividad-tecnico-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Filtros -->
    <?php $form = \yii\widgets\ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'form-inline mb-4'],
    ]); ?>
    
    <div class="form-group mr-3">
        <label for="fecha_inicio" class="control-label">Desde:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
    </div>

    <div class="form-group mr-3">
        <label for="fecha_fin" class="control-label">Hasta:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <?= Html::a('<i class="fas fa-file-csv"></i> Exportar CSV', ['export-csv'], [
            'class' => 'btn btn-success ml-2',
            'data' => ['method' => 'post'],
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Órdenes Completadas</h5>
                    <h3 class="mb-0"><?= $totalOrdenes ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Horas Trabajadas</h5>
                    <h3 class="mb-0"><?= $totalHoras ?>h</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Ingreso Total Generado</h5>
                    <h3 class="mb-0">$<?= number_format($totalIngreso, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Comparativo de Productividad</h5>
        </div>
        <div class="card-body">
            <canvas id="chartProductividad" height="80"></canvas>
        </div>
    </div>

    <!-- Tabla de Técnicos -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detalle por Técnico</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => array_values($estadisticas),
                    'pagination' => ['pageSize' => 20],
                    'sort' => [
                        'attributes' => ['nombre', 'ordenes_completadas', 'horas_trabajadas'],
                        'defaultOrder' => ['ordenes_completadas' => SORT_DESC],
                    ],
                ]),
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'label' => 'Técnico',
                        'value' => function($model) {
                            return $model['tecnico']->getFullName();
                        },
                    ],
                    [
                        'label' => 'Especialidad',
                        'value' => function($model) {
                            return $model['tecnico']->especialidad 
                                ? $model['tecnico']->especialidad->nombre 
                                : 'General';
                        },
                    ],
                    [
                        'attribute' => 'ordenes_completadas',
                        'label' => 'Órdenes Completadas',
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'attribute' => 'horas_trabajadas',
                        'label' => 'Horas Trabajadas',
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'attribute' => 'ingreso_generado',
                        'label' => 'Ingreso Generado',
                        'value' => function($model) {
                            return '$' . number_format($model['ingreso_generado'], 0, ',', '.');
                        },
                        'contentOptions' => ['class' => 'text-right'],
                    ],
                    [
                        'attribute' => 'eficiencia',
                        'label' => 'Eficiencia',
                        'value' => function($model) {
                            return $model['eficiencia'] . '%';
                        },
                        'contentOptions' => ['class' => 'text-center'],
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{ver}',
                        'buttons' => [
                            'ver' => function($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', ['detalle', 'id' => $model['tecnico']->id], [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'title' => 'Ver detalle',
                                ]);
                            },
                        ],
                    ],
                ],
                'tableOptions' => ['class' => 'table table-bordered table-hover'],
            ]) ?>
        </div>
    </div>
</div>

<?php
// Registrar Chart.js
$this->registerJsFile('https://cdn.jsdelivr.net/npm/chart.js', ['position' => \yii\web\View::POS_END]);

$js = <<<JS
$(document).ready(function() {
    // Cargar datos del gráfico
    $.get('chart-data', {
        fecha_inicio: $('#fecha_inicio').val(),
        fecha_fin: $('#fecha_fin').val()
    }, function(response) {
        var ctx = document.getElementById('chartProductividad').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: response.labels,
                datasets: response.datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }, 'json');
});
JS;

$this->registerJs($js);
?>

<style>
.text-right { text-align: right; }
.text-center { text-align: center; }
.card { box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
</style>
