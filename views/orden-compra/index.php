<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\OrdenCompra;

/** @var yii\web\View $this */
/** @var app\models\search\OrdenCompraSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $totalOrdenes */
/** @var int $ordenesPendientes */
/** @var int $ordenesRecibidas */
/** @var float $montoTotalMes */

$this->title = 'Órdenes de Compra';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-compra-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Crear Orden de Compra', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Órdenes</h5>
                    <h2 class="mb-0"><?= $totalOrdenes ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pendientes</h5>
                    <h2 class="mb-0"><?= $ordenesPendientes ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Recibidas</h5>
                    <h2 class="mb-0"><?= $ordenesRecibidas ?></h2>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Monto del Mes</h5>
<h2 class="mb-0">$<?= number_format((float)$montoTotalMes, 0, ',', '.') ?></h2>
                </div>
            </div>
        </div>
    </div>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'numero_orden',
            [
                'attribute' => 'proveedor_id',
                'value' => function($model) {
                    return $model->proveedor ? $model->proveedor->nombre : '-';
                },
                'filter' => \app\models\Proveedor::getListaParaDropdown(),
            ],
            'fecha_emision:date',
            'fecha_entrega_esperada:date',
            [
                'attribute' => 'estado',
                'value' => function($model) {
                    return OrdenCompra::getEstadosOpciones()[$model->estado] ?? $model->estado;
                },
                'filter' => OrdenCompra::getEstadosOpciones(),
                'format' => 'raw',
                'value' => function($model) {
                    $badgeClass = match($model->estado) {
                        OrdenCompra::ESTADO_BORRADOR => 'secondary',
                        OrdenCompra::ESTADO_ENVIADA => 'warning',
                        OrdenCompra::ESTADO_RECIBIDA_PARCIAL => 'info',
                        OrdenCompra::ESTADO_RECIBIDA_COMPLETO => 'success',
                        OrdenCompra::ESTADO_CANCELADA => 'danger',
                        default => 'secondary',
                    };
                    return Html::tag('span', 
                        OrdenCompra::getEstadosOpciones()[$model->estado] ?? $model->estado, 
                        ['class' => "badge bg-{$badgeClass}"]
                    );
                },
            ],
            [
                'attribute' => 'total_monto',
                'value' => function($model) {
return '$' . number_format((float)$model->total_monto, 0, ',', '.');
                },
            ],
            //'created_at:datetime',
            //'updated_at:datetime',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {delete}',
                'buttons' => [
                    'view' => function($url, $model) {
                        return Html::a('<i class="fas fa-eye"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Ver',
                        ]);
                    },
                    'update' => function($url, $model) {
                        if (in_array($model->estado, [OrdenCompra::ESTADO_RECIBIDA_COMPLETO, OrdenCompra::ESTADO_CANCELADA])) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-edit"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-warning',
                            'title' => 'Editar',
                        ]);
                    },
                    'delete' => function($url, $model) {
                        if ($model->estado !== OrdenCompra::ESTADO_BORRADOR) {
                            return '';
                        }
                        return Html::a('<i class="fas fa-trash"></i>', $url, [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Eliminar',
                            'data-confirm' => '¿Está seguro que desea eliminar esta orden de compra?',
                        ]);
                    },
                ],
            ],
        ],
    ]); ?>

</div>
