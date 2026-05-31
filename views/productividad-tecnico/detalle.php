<?php
/**
 * Detalle de Productividad por Técnico - HU-022
 */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

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
                    <?= DetailView::widget([
                        'model' => $tecnico,
                        'options' => ['class' => 'table table-borderless mb-0'],
                        'attributes' => [
                            [
                                'label' => 'Nombre',
                                'value' => $tecnico->getFullName(),
                            ],
                            [
                                'label' => 'RUT',
                                'value' => $tecnico->rut ?? 'N/A',
                            ],
                            [
                                'label' => 'Especialidad',
                                'value' => $tecnico->especialidad ? $tecnico->especialidad->nombre : 'General',
                            ],
                            [
                                'label' => 'Email',
                                'value' => $tecnico->email ?? 'N/A',
                            ],
                            [
                                'label' => 'Teléfono',
                                'value' => $tecnico->telefono ?? 'N/A',
                            ],
                            [
                                'label' => 'Costo Hora',
                                'value' => '$' . number_format((float)$tecnico->costo_hora, 0, ',', '.'),
                            ],
                        ],
                    ]) ?>
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
                        <?php
                        $totalHoras = 0;
                        $totalIngresos = 0;
                        $ordenesData = [];
                        foreach ($ordenes as $orden) {
                            $horasTrabajadas = 0;
                            if ($orden->closed_at !== null && $orden->created_at !== null) {
                                $horasTrabajadas = round(($orden->closed_at - $orden->created_at) / 3600, 2);
                            }
                            $totalHoras += $horasTrabajadas;

                            $totalOrden = 0;
                            if ($orden->detalles) {
                                foreach ($orden->detalles as $detalle) {
                                    $totalOrden += $detalle->precio_total ?? 0;
                                }
                            }
                            $totalIngresos += $totalOrden;

                            $ordenesData[] = [
                                'id' => $orden->id,
                                'created_at' => $orden->created_at,
                                'closed_at' => $orden->closed_at,
                                'cliente' => $orden->cliente ? $orden->cliente->getFullName() : 'N/A',
                                'horasTrabajadas' => $horasTrabajadas,
                                'totalOrden' => $totalOrden,
                                'estado' => $orden->estado,
                            ];
                        }
                        $ordenesProvider = new ArrayDataProvider([
                            'allModels' => $ordenesData,
                            'pagination' => false,
                        ]);
                        ?>
                        <?= GridView::widget([
                            'dataProvider' => $ordenesProvider,
                            'tableOptions' => ['class' => 'table table-hover mb-0'],
                            'layout' => '{items}',
                            'columns' => [
                                [
                                    'label' => 'Orden',
                                    'format' => 'raw',
                                    'value' => fn($model) => Html::a('#' . $model['id'], ['/orden-servicio/view', 'id' => $model['id']]),
                                ],
                                [
                                    'label' => 'Fecha Ingreso',
                                    'value' => fn($model) => date('d/m/Y H:i', $model['created_at']),
                                ],
                                [
                                    'label' => 'Fecha Término',
                                    'value' => fn($model) => $model['closed_at'] ? date('d/m/Y H:i', $model['closed_at']) : '-',
                                ],
                                [
                                    'label' => 'Cliente',
                                    'value' => fn($model) => Html::encode($model['cliente']),
                                ],
                                [
                                    'label' => 'Horas Trabajadas',
                                    'value' => fn($model) => $model['horasTrabajadas'] . 'h',
                                    'contentOptions' => ['class' => 'text-center'],
                                    'headerOptions' => ['class' => 'text-center'],
                                ],
                                [
                                    'label' => 'Total',
                                    'value' => fn($model) => '$' . number_format((float)$model['totalOrden'], 0, ',', '.'),
                                    'contentOptions' => ['class' => 'text-end'],
                                    'headerOptions' => ['class' => 'text-end'],
                                ],
                                [
                                    'label' => 'Estado',
                                    'format' => 'raw',
                                    'value' => fn($model) => '<span class="badge bg-info">' . str_replace('_', ' ', ucfirst($model['estado'])) . '</span>',
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{view}',
                                    'buttons' => [
                                        'view' => fn($url, $model) => Html::a(
                                            '<i class="fas fa-eye"></i>',
                                            ['/orden-servicio/view', 'id' => $model['id']],
                                            ['class' => 'btn btn-sm btn-outline-primary', 'title' => 'Ver orden']
                                        ),
                                    ],
                                ],
                            ],
                        ]) ?>
                        <div class="d-flex justify-content-end gap-4 mt-2 p-2 bg-light rounded">
                            <strong>Totales del Período:</strong>
                            <span>Horas: <strong><?= round($totalHoras, 2) ?>h</strong></span>
                            <span>Ingresos: <strong>$<?= number_format((float)$totalIngresos, 0, ',', '.') ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
