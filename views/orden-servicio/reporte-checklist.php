<?php
/** @var yii\web\View $this */
/** @var app\models\ChecklistReportForm $formModel */
/** @var array $estadisticas */
/** @var array $datosPorServicio */
/** @var string $fechaDesde */
/** @var string $fechaHasta */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Reporte de Cumplimiento de Checklists';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="reporte-checklist">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'row g-3 align-items-end mb-4'],
    ]); ?>

    <div class="col-md-3">
        <?= $form->field($formModel, 'fechaDesde')->textInput(['type' => 'date']) ?>
    </div>

    <div class="col-md-3">
        <?= $form->field($formModel, 'fechaHasta')->textInput(['type' => 'date']) ?>
    </div>

    <div class="col-md-2">
        <?= Html::submitButton('Filtrar', ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Exportar CSV', ['reporte-checklist', 'fechaDesde' => $fechaDesde, 'fechaHasta' => $fechaHasta, 'export' => 1], [
            'class' => 'btn btn-success',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Órdenes</h5>
                    <h2><?= $estadisticas['total_ordenes'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Con Checklist</h5>
                    <h2><?= $estadisticas['con_checklist'] ?></h2>
                    <small><?= $estadisticas['total_ordenes'] > 0 ? round(($estadisticas['con_checklist'] / $estadisticas['total_ordenes']) * 100, 1) : 0 ?>% del total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Checklists Completos</h5>
                    <h2><?= $estadisticas['checklist_completo'] ?></h2>
                    <small><?= $estadisticas['con_checklist'] > 0 ? round(($estadisticas['checklist_completo'] / $estadisticas['con_checklist']) * 100, 1) : 0 ?>% de los que tienen</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">% Cumplimiento Global</h5>
                    <h2><?= $estadisticas['porcentaje_cumplimiento'] ?>%</h2>
                    <small><?= $estadisticas['items_completados'] ?>/<?= $estadisticas['items_totales'] ?> items</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla por Servicio -->
    <div class="card">
        <div class="card-header">
            <h5>Cumplimiento por Servicio</h5>
        </div>
        <div class="card-body">
            <?php if (empty($datosPorServicio)): ?>
                <p class="text-muted">No hay datos disponibles para el período seleccionado.</p>
            <?php else: ?>
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th class="text-center">Total Órdenes</th>
                            <th class="text-center">Con Checklist</th>
                            <th class="text-center">Completados</th>
                            <th class="text-center">% Cumplimiento</th>
                            <th class="text-center">Items Totales</th>
                            <th class="text-center">Items Completados</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($datosPorServicio as $servicio => $datos): ?>
                            <tr>
                                <td><strong><?= Html::encode($servicio) ?></strong></td>
                                <td class="text-center"><?= $datos['total_ordenes'] ?></td>
                                <td class="text-center"><?= $datos['con_checklist'] ?></td>
                                <td class="text-center"><?= $datos['completados'] ?></td>
                                <td class="text-center">
                                    <?php 
                                    $porcentaje = $datos['con_checklist'] > 0 
                                        ? round(($datos['completados'] / $datos['con_checklist']) * 100, 1) 
                                        : 0;
                                    $clase = $porcentaje >= 80 ? 'text-success' : ($porcentaje >= 50 ? 'text-warning' : 'text-danger');
                                    ?>
                                    <span class="<?= $clase ?>"><strong><?= $porcentaje ?>%</strong></span>
                                </td>
                                <td class="text-center"><?= $datos['items_totales'] ?></td>
                                <td class="text-center"><?= $datos['items_completados'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen -->
    <div class="card mt-4">
        <div class="card-header">
            <h5>Resumen del Período</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Órdenes sin checklist
                            <span class="badge bg-danger rounded-pill"><?= $estadisticas['sin_checklist'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Checklists parciales
                            <span class="badge bg-warning rounded-pill"><?= $estadisticas['checklist_parcial'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Items totales verificados
                            <span class="badge bg-info rounded-pill"><?= $estadisticas['items_totales'] ?></span>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-info">
                        <strong>Recomendaciones:</strong>
                        <ul class="mb-0 mt-2">
                            <?php if ($estadisticas['sin_checklist'] > 0): ?>
                                <li>Hay <?= $estadisticas['sin_checklist'] ?> órdenes sin checklist. Revise el proceso de creación.</li>
                            <?php endif; ?>
                            <?php if ($estadisticas['porcentaje_cumplimiento'] < 80): ?>
                                <li>El cumplimiento global es bajo (<?= $estadisticas['porcentaje_cumplimiento'] ?>%). Considere capacitación.</li>
                            <?php endif; ?>
                            <?php if ($estadisticas['checklist_parcial'] > $estadisticas['checklist_completo']): ?>
                                <li>Más checklists parciales que completos. Establezca recordatorios de completion.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
