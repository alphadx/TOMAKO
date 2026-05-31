<?php
/**
 * Vista de Valorización de Inventario - HU-014
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\DetailView;

$this->title = 'Valorización de Inventario';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="inventario-report-valorizacion">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Valor Total Inventario</h5>
                    <h3 class="mb-0">$<?= number_format((float)$valorTotal, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Unidades</h5>
                    <h3 class="mb-0"><?= $totalItems ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Ítems Sin Stock</h5>
                    <h3 class="mb-0"><?= $itemsSinStock ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title">Ítems Bajo Stock</h5>
                    <h3 class="mb-0"><?= $itemsBajoStock ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Exportar -->
    <div class="row mb-3">
        <div class="col-md-12">
            <?= Html::a(
                '<i class="fas fa-file-csv"></i> Exportar a CSV',
                ['export-valorizacion-csv'],
                [
                    'class' => 'btn btn-success',
                    'data' => ['method' => 'post'],
                ]
            ) ?>
        </div>
    </div>

    <!-- Valorización por Categoría -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Valorización por Categoría</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th class="text-right">Cantidad de Ítems</th>
                        <th class="text-right">Unidades Totales</th>
                        <th class="text-right">Valor Total</th>
                        <th class="text-right">% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($valorPorCategoria as $categoria => $datos): ?>
                        <tr>
                            <td><strong><?= Html::encode($categoria) ?></strong></td>
                            <td class="text-right"><?= $datos['items'] ?></td>
                            <td class="text-right"><?= number_format((int)$datos['cantidad'], 0, ',', '.') ?></td>
                            <td class="text-right">$<?= number_format((float)$datos['valor'], 0, ',', '.') ?></td>
                            <td class="text-right">
                                <?= $valorTotal > 0 ? number_format((float)(($datos['valor'] / $valorTotal) * 100), 1) : 0 ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-active">
                        <th>Total General</th>
                        <th class="text-right"><?= count($valorPorCategoria) ?></th>
                        <th class="text-right"><?= number_format((int)$totalItems, 0, ',', '.') ?></th>
                        <th class="text-right">$<?= number_format((float)$valorTotal, 0, ',', '.') ?></th>
                        <th class="text-right">100%</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Detalle de Ítems -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Detalle de Ítems Valorizados</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => new \yii\data\ArrayDataProvider([
                    'allModels' => array_map(function($item) {
                        return [
                            'sku' => $item->sku,
                            'nombre' => $item->nombre,
                            'categoria' => $item->categoria ? $item->categoria->nombre : '',
                            'precio_unitario' => $item->precio_unitario,
                            'cantidad' => $item->cantidad,
                            'valor_total' => $item->precio_unitario * $item->cantidad,
                            'stock_minimo' => $item->stock_minimo,
                            'estado_stock' => $item->getEstadoStock(),
                        ];
                    }, $items),
                    'pagination' => [
                        'pageSize' => 20,
                    ],
                    'sort' => [
                        'attributes' => ['nombre', 'cantidad', 'valor_total'],
                        'defaultOrder' => ['valor_total' => SORT_DESC],
                    ],
                ]),
                'filterModel' => null,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'sku',
                    'nombre',
                    'categoria',
                    [
                        'attribute' => 'precio_unitario',
                        'format' => ['currency', 'CLP'],
                        'contentOptions' => ['class' => 'text-right'],
                    ],
                    [
                        'attribute' => 'cantidad',
                        'contentOptions' => ['class' => 'text-right'],
                    ],
                    [
                        'attribute' => 'valor_total',
                        'label' => 'Valor Total',
                        'value' => function($model) {
                            return '$' . number_format((float)$model['valor_total'], 0, ',', '.');
                        },
                        'contentOptions' => ['class' => 'text-right font-weight-bold'],
                    ],
                    [
                        'attribute' => 'estado_stock',
                        'label' => 'Estado',
                        'value' => function($model) {
                            $class = match($model['estado_stock']) {
                                'sin_stock' => 'badge badge-danger',
                                'bajo' => 'badge badge-warning',
                                default => 'badge badge-success',
                            };
                            return "<span class=\"{$class}\">" . 
                                match($model['estado_stock']) {
                                    'sin_stock' => 'Sin Stock',
                                    'bajo' => 'Bajo Stock',
                                    default => 'En Stock',
                                } . '</span>';
                        },
                        'format' => 'raw',
                    ],
                ],
                'options' => ['class' => 'table-responsive'],
                'tableOptions' => ['class' => 'table table-bordered table-hover'],
            ]) ?>
        </div>
    </div>
</div>

<style>
.text-right {
    text-align: right;
}
.font-weight-bold {
    font-weight: bold;
}
</style>
