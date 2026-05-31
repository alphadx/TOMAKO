<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/** @var yii\web\View $this */
/** @var array $clientesFrecuentes */
/** @var string $fechaDesde */
/** @var string $fechaHasta */

$this->title = 'Reporte de Clientes Más Frecuentes';
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cliente-reporte-frecuentes">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Filtros de fecha -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>Filtrar por Período</h5>
        </div>
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'options' => ['class' => 'row g-3 align-items-end'],
            ]); ?>

            <div class="col-md-4">
                <label class="form-label">Fecha Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($fechaDesde) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">Fecha Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($fechaHasta) ?>">
            </div>

            <div class="col-md-4">
                <?= Html::submitButton('Filtrar', ['class' => 'btn btn-primary']) ?>
                <?= Html::a('Exportar CSV', ['reporte-frecuentes-csv', 'fecha_desde' => $fechaDesde, 'fecha_hasta' => $fechaHasta], ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Tabla de resultados -->
    <div class="card">
        <div class="card-header">
            <h5>Ranking de Clientes</h5>
        </div>
        <div class="card-body">
            <?php if (empty($clientesFrecuentes)): ?>
                <p class="text-muted text-center">No hay clientes en el período seleccionado.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Cliente</th>
                                <th>RUT</th>
                                <th>Teléfono</th>
                                <th>Email</th>
                                <th class="text-center">Órdenes</th>
                                <th class="text-end">Total Gastado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 0;
                            foreach ($clientesFrecuentes as $index => $cliente): 
                                $rank++;
                                $medalla = '';
                                if ($rank === 1) {
                                    $medalla = '🥇';
                                } elseif ($rank === 2) {
                                    $medalla = '🥈';
                                } elseif ($rank === 3) {
                                    $medalla = '🥉';
                                }
                            ?>
                                <tr class="<?= $rank <= 3 ? 'table-warning' : '' ?>">
                                    <td class="text-center"><?= $medalla ?> <strong>#<?= $rank ?></strong></td>
                                    <td>
                                        <strong><?= Html::encode($cliente['nombre']) ?></strong>
                                    </td>
                                    <td><?= Html::encode($cliente['rut'] ?? '—') ?></td>
                                    <td><?= Html::encode($cliente['telefono'] ?? '—') ?></td>
                                    <td><?= Html::encode($cliente['email'] ?? '—') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary fs-6"><?= (int)$cliente['total_ordenes'] ?></span>
                                    </td>
                                    <td class="text-end">
                                        <strong>$<?= number_format((float)$cliente['total_gastado'], 0, ',', '.') ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <?= Html::a('Ver', ['view', 'id' => $cliente['id']], ['class' => 'btn btn-sm btn-info']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Resumen -->
                <div class="alert alert-info mt-3">
                    <strong>Resumen del Período:</strong><br>
                    <ul class="mb-0">
                        <li>Total de clientes con órdenes: <strong><?= count($clientesFrecuentes) ?></strong></li>
                        <li>Cliente más frecuente: <strong><?= Html::encode($clientesFrecuentes[0]['nombre']) ?></strong> con <?= (int)$clientesFrecuentes[0]['total_ordenes'] ?> órdenes</li>
                        <li>Total gastado por todos los clientes: <strong>$<?= number_format(array_sum(array_column($clientesFrecuentes, 'total_gastado')), 0, ',', '.') ?></strong></li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table-warning {
    background-color: rgba(255, 193, 7, 0.15);
}
</style>
