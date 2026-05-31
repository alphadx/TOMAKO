<?php
/** @var yii\web\View $this */
/** @var app\models\Vehiculo $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->patente;
$this->params['breadcrumbs'][] = ['label' => 'Vehículos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="vehiculo-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-car-front me-2"></i>
            <span class="badge bg-dark font-monospace fs-5"><?= Html::encode($model->patente) ?></span>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?php if ($model->status): ?>
                <?= Html::a('<i class="bi bi-x-circle me-1"></i>Desactivar', ['deactivate', 'id' => $model->id], [
                    'class'        => 'btn btn-outline-danger btn-sm',
                    'data-method'  => 'post',
                    'data-confirm' => '¿Desactivar este vehículo?',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-<?= $model->foto_path ? '8' : '12' ?>">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Información del Vehículo</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'options'    => ['class' => 'table table-bordered'],
                        'attributes' => [
                            [
                                'label'   => 'Patente',
                                'value'   => '<span class="badge bg-dark font-monospace fs-6">' . Html::encode($model->patente) . '</span>',
                                'format'  => 'raw',
                            ],
                            'marca',
                            'modelo',
                            'anio',
                            [
                                'label' => 'VIN',
                                'value' => $model->vin ?: '—',
                            ],
                            [
                                'label'  => 'Propietario',
                                'format' => 'raw',
                                'value'  => $model->cliente
                                    ? Html::a(Html::encode($model->cliente->nombre), ['/cliente/view', 'id' => $model->cliente_id])
                                    : '—',
                            ],
                            [
                                'label' => 'Último KM',
                                'value' => $model->ultimo_km ? number_format($model->ultimo_km) . ' km' : '—',
                            ],
                            [
                                'label'  => 'Estado',
                                'format' => 'raw',
                                'value'  => $model->status
                                    ? '<span class="badge bg-success">Activo</span>'
                                    : '<span class="badge bg-secondary">Inactivo</span>',
                            ],
                            [
                                'label' => 'Registrado',
                                'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—',
                            ],
                            [
                                'label' => 'Actualizado',
                                'value' => $model->updated_at ? date('d/m/Y H:i', $model->updated_at) : '—',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <?php if ($model->foto_path): ?>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Foto</strong></div>
                <div class="card-body text-center">
                    <img src="<?= Yii::$app->request->baseUrl . '/' . $model->foto_path ?>"
                         alt="Foto del vehículo" class="img-fluid rounded">
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

        <div class="row g-3 mt-1">
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><strong>Última Cita</strong></div>
                    <div class="card-body">
                        <?php $ultimaCita = $model->getUltimaCita(); ?>
                        <?php if ($ultimaCita !== null): ?>
                            <div><strong>Fecha:</strong> <?= Html::encode($ultimaCita->fecha) ?></div>
                            <div><strong>Horario:</strong> <?= Html::encode($ultimaCita->hora_inicio) ?> - <?= Html::encode($ultimaCita->hora_fin) ?></div>
                            <div><strong>Estado:</strong> <?= Html::encode($ultimaCita->estado) ?></div>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><strong>Próxima Cita</strong></div>
                    <div class="card-body">
                        <?php $proximaCita = $model->getProximaCita(); ?>
                        <?php if ($proximaCita !== null): ?>
                            <div><strong>Fecha:</strong> <?= Html::encode($proximaCita->fecha) ?></div>
                            <div><strong>Horario:</strong> <?= Html::encode($proximaCita->hora_inicio) ?> - <?= Html::encode($proximaCita->hora_fin) ?></div>
                            <div><strong>Estado:</strong> <?= Html::encode($proximaCita->estado) ?></div>
                        <?php else: ?>
                            <span class="text-muted">Sin cita programada.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de servicios (HU-003) -->
    <div class="card shadow-sm mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-clock-history me-2"></i>Historial Completo de Servicios</strong>
            <span class="badge bg-primary"><?= count($ordenes ?? []) ?> registros</span>
        </div>
            <div class="card-body p-0">
                <?php $ordenes = $model->getOrdenes()->orderBy(['created_at' => SORT_DESC])->all(); ?>
                <?php if (empty($ordenes)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-tools fs-1 d-block mb-2"></i>
                        Sin historial de órdenes de servicio.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Fecha</th>
                                    <th>Servicios Realizados</th>
                                    <th>Técnico</th>
                                    <th>KM Vehículo</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordenes as $orden): ?>
                                    <?php 
                                    // Obtener servicios de la orden
                                    $servicios = $orden->getServicios()->all();
                                    $tecnico = $orden->tecnico;
                                    ?>
                                    <tr>
                                        <td><strong><?= Html::encode($orden->codigo) ?></strong></td>
                                        <td><?= $orden->created_at ? date('d/m/Y', (int) $orden->created_at) : '—' ?></td>
                                        <td>
                                            <?php if (count($servicios) > 0): ?>
                                                <ul class="list-unstyled mb-0 small">
                                                    <?php foreach ($servicios as $servicio): ?>
                                                        <li><span class="badge bg-info"><?= Html::encode($servicio->nombre) ?></span></li>
                                                    <?php endforeach ?>
                                                </ul>
                                            <?php else: ?>
                                                <span class="text-muted">Sin servicios</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <?php if ($tecnico): ?>
                                                <span class="badge bg-secondary">
                                                    <?= Html::encode($tecnico->nombre) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td>
                                            <?php if ($orden->km_vehiculo): ?>
                                                <?= number_format($orden->km_vehiculo, 0, ',', '.') ?> km
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif ?>
                                        </td>
                                        <td><strong><?= Yii::$app->formatter->asCurrency((float) $orden->total, 'CLP') ?></strong></td>
                                        <td><span class="badge <?= Html::encode($orden->getEstadoBadgeClass()) ?>"><?= Html::encode(\app\models\OrdenServicio::getEstadosList()[$orden->estado] ?? $orden->estado) ?></span></td>
                                        <td><?= Html::a('<i class="bi bi-eye"></i>', ['/orden/view', 'id' => $orden->id], ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Ver detalle']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="5" class="text-end">Total Histórico:</th>
                                    <th colspan="3">
                                        <?php
                                        $totalHistorico = array_sum(array_column($ordenes, 'total'));
                                        echo Yii::$app->formatter->asCurrency($totalHistorico, 'CLP');
                                        ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
        </div>
    </div>
</div>
