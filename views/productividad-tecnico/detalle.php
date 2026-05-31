<?php
/**
 * Detalle de Productividad por Técnico - HU-022
 */

use yii\helpers\Html;

$this->title = 'Detalle: ' . $tecnico->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Productividad', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="productividad-tecnico-detalle">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Información del Técnico</h5>
                </div>
                <div class="card-body">
                    <p><strong>Nombre:</strong> <?= Html::encode($tecnico->getFullName()) ?></p>
                    <p><strong>RUT:</strong> <?= Html::encode($tecnico->rut ?? 'N/A') ?></p>
                    <p><strong>Especialidad:</strong> 
                        <?= $tecnico->especialidad ? Html::encode($tecnico->especialidad->nombre) : 'General' ?>
                    </p>
                    <p><strong>Email:</strong> <?= Html::encode($tecnico->email ?? 'N/A') ?></p>
                    <p><strong>Teléfono:</strong> <?= Html::encode($tecnico->telefono ?? 'N/A') ?></p>
                    <p><strong>Costo Hora:</strong> $<?= number_format($tecnico->costo_hora, 0, ',', '.') ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Estadísticas del Período</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Período: <?= date('d/m/Y', strtotime($fechaInicio)) ?> al <?= date('d/m/Y', strtotime($fechaFin)) ?>
                    </p>
                    
                    <?php if (empty($ordenes)): ?>
                        <p class="text-center text-muted py-4">
                            No hay órdenes registradas para este técnico en el período seleccionado.
                        </p>
                    <?php else: ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Fecha Término</th>
                                    <th>Cliente</th>
                                    <th>Horas Trabajadas</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalHoras = 0;
                                $totalIngresos = 0;
                                foreach ($ordenes as $orden): 
                                    // Calcular horas trabajadas
                                    $horasTrabajadas = 0;
                                    if ($orden->closed_at !== null && $orden->created_at !== null) {
                                        $horasTrabajadas = round(($orden->closed_at - $orden->created_at) / 3600, 2);
                                    }
                                    $totalHoras += $horasTrabajadas;
                                    
                                    // Calcular total de la orden
                                    $totalOrden = 0;
                                    if ($orden->detalles) {
                                        foreach ($orden->detalles as $detalle) {
                                            $totalOrden += $detalle->precio_total ?? 0;
                                        }
                                    }
                                    $totalIngresos += $totalOrden;
                                ?>
                                    <tr>
                                        <td><a href="<?= \yii\helpers\Url::to(['/orden-servicio/view', 'id' => $orden->id]) ?>">#<?= $orden->id ?></a></td>
                                        <td><?= date('d/m/Y H:i', $orden->created_at) ?></td>
                                        <td><?= $orden->closed_at ? date('d/m/Y H:i', $orden->closed_at) : '-' ?></td>
                                        <td><?= $orden->cliente ? Html::encode($orden->cliente->getFullName()) : 'N/A' ?></td>
                                        <td class="text-center"><?= $horasTrabajadas ?>h</td>
                                        <td class="text-right">$<?= number_format($totalOrden, 0, ',', '.') ?></td>
                                        <td><span class="badge badge-info"><?= str_replace('_', ' ', ucfirst($orden->estado)) ?></span></td>
                                        <td>
                                            <?= Html::a('<i class="fas fa-eye"></i>', ['/orden-servicio/view', 'id' => $orden->id], [
                                                'class' => 'btn btn-sm btn-outline-primary',
                                                'title' => 'Ver orden',
                                            ]) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="4" class="text-right">Totales del Período:</th>
                                    <th class="text-center"><?= round($totalHoras, 2) ?>h</th>
                                    <th class="text-right">$<?= number_format($totalIngresos, 0, ',', '.') ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
