<?php
/** @var yii\web\View $this */
/** @var app\models\InventoryItem $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use app\models\InventoryMovement;

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Inventario', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$estadoLabels = ['sin_stock' => 'Sin Stock', 'bajo' => 'Stock Bajo', 'en_stock' => 'En Stock'];
?>

<div class="inventario-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-box me-2"></i><?= Html::encode($model->nombre) ?>
            <span class="badge bg-<?= $model->getEstadoStockClass() ?> ms-2">
                <?= $estadoLabels[$model->getEstadoStock()] ?? '' ?>
            </span>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?php if ($model->status): ?>
                <?= Html::a('<i class="bi bi-x-circle me-1"></i>Desactivar', ['deactivate', 'id' => $model->id], [
                    'class' => 'btn btn-outline-danger btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Desactivar este ítem?',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Detalle -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Información del Ítem</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'options'    => ['class' => 'table table-bordered'],
                        'attributes' => [
                            ['label' => 'SKU',      'format' => 'raw',
                             'value' => '<span class="badge bg-secondary font-monospace">' . Html::encode($model->sku) . '</span>'],
                            'nombre',
                            'descripcion:ntext',
                            ['label' => 'Categoría', 'value' => $model->categoria ? $model->categoria->nombre : '—'],
['label' => 'Precio Unitario', 'value' => '$ ' . number_format((float)$model->precio_unitario, 0, ',', '.')],
                            ['label' => 'Stock Actual', 'format' => 'raw',
                             'value' => '<strong>' . $model->cantidad . '</strong> ' . Html::encode($model->unidad ?? '')],
                            ['label' => 'Stock Mínimo', 'value' => $model->stock_minimo],
                            ['label' => 'Stock Máximo', 'value' => $model->stock_maximo ?: '—'],
                            'unidad',
                            ['label' => 'Ubicación', 'value' => $model->ubicacion ?: '—'],
                            ['label' => 'Estado', 'format' => 'raw',
                             'value' => $model->status ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'],
                            ['label' => 'Creado', 'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—'],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <!-- Formularios de movimiento -->
        <div class="col-md-4">
            <!-- Entrada -->
            <div class="card shadow-sm mb-3 border-success">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-box-arrow-in-down me-1"></i><strong>Registrar Entrada</strong>
                </div>
                <div class="card-body">
                    <?php $f = ActiveForm::begin(['action' => ['entrada', 'id' => $model->id], 'method' => 'post']); ?>
                    <div class="mb-2">
                        <label class="form-label small">Cantidad</label>
                        <input type="number" name="cantidad" min="1" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Referencia</label>
                        <input type="text" name="referencia" class="form-control form-control-sm" placeholder="Proveedor, N° factura...">
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">
                        <i class="bi bi-plus-circle me-1"></i>Registrar Entrada
                    </button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <!-- Ajuste -->
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning">
                    <i class="bi bi-sliders me-1"></i><strong>Ajuste de Stock</strong>
                </div>
                <div class="card-body">
                    <?php $f = ActiveForm::begin(['action' => ['ajuste', 'id' => $model->id], 'method' => 'post']); ?>
                    <div class="mb-2">
                        <label class="form-label small">Nueva Cantidad</label>
                        <input type="number" name="cantidad_nueva" min="0" value="<?= $model->cantidad ?>" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Motivo</label>
                        <input type="text" name="motivo" class="form-control form-control-sm" placeholder="Inventario físico, merma...">
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm w-100">
                        <i class="bi bi-arrow-repeat me-1"></i>Aplicar Ajuste
                    </button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de movimientos -->
    <div class="card shadow-sm mt-3">
        <div class="card-header"><strong><i class="bi bi-clock-history me-2"></i>Historial de Movimientos</strong></div>
        <div class="card-body p-0">
            <?php
            $movimientosProvider = new ArrayDataProvider([
                'allModels' => $model->movimientos,
                'pagination' => false,
            ]);
            ?>
            <?= GridView::widget([
                'dataProvider' => $movimientosProvider,
                'tableOptions' => ['class' => 'table table-sm table-striped mb-0'],
                'headerRowOptions' => ['class' => 'table-dark'],
                'layout' => '{items}',
                'emptyText' => 'Sin movimientos registrados.',
                'columns' => [
                    [
                        'label' => 'Fecha',
                        'value' => fn($model) => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—',
                    ],
                    [
                        'label' => 'Tipo',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $cls = match ($model->tipo) { 'entrada' => 'bg-success', 'salida' => 'bg-danger', default => 'bg-warning' };
                            $lbl = match ($model->tipo) { 'entrada' => 'Entrada', 'salida' => 'Salida', default => 'Ajuste' };
                            return '<span class="badge ' . $cls . '">' . $lbl . '</span>';
                        },
                    ],
                    [
                        'label' => 'Cantidad',
                        'format' => 'raw',
                        'value' => function ($model) {
                            $sign = $model->cantidad_delta >= 0 ? '+' : '';
                            $cls = $model->cantidad_delta >= 0 ? 'text-success' : 'text-danger';
                            return '<span class="fw-bold ' . $cls . '">' . $sign . $model->cantidad_delta . '</span>';
                        },
                    ],
                    [
                        'label' => 'Anterior',
                        'attribute' => 'cantidad_anterior',
                    ],
                    [
                        'label' => 'Nuevo',
                        'attribute' => 'cantidad_nueva',
                    ],
                    [
                        'label' => 'Referencia',
                        'value' => fn($model) => Html::encode($model->referencia ?? '—'),
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
