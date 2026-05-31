<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use app\models\OrdenCompra;
use app\models\OrdenCompraItem;

/** @var yii\web\View $this */
/** @var app\models\OrdenCompra $model */
/** @var app\models\OrdenCompraItem $itemModel */
/** @var array $listaInventario */

$this->title = 'Orden de Compra: ' . $model->numero_orden;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Compra', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-compra-view">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($model->estado === OrdenCompra::ESTADO_BORRADOR): ?>
                <?= Html::a('Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-warning']) ?>
                <?= Html::a('Enviar al Proveedor', ['enviar', 'id' => $model->id], [
                    'class' => 'btn btn-primary',
                    'data-confirm' => '¿Está seguro que desea enviar esta orden al proveedor?',
                ]) ?>
                <?= Html::a('Eliminar', ['delete', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data-confirm' => '¿Está seguro que desea eliminar esta orden de compra?',
                ]) ?>
            <?php endif; ?>
            
            <?php if (in_array($model->estado, [OrdenCompra::ESTADO_ENVIADA, OrdenCompra::ESTADO_RECIBIDA_PARCIAL])): ?>
                <?= Html::a('Registrar Recepción', ['recibir', 'id' => $model->id], ['class' => 'btn btn-success']) ?>
            <?php endif; ?>
            
            <?php if (!in_array($model->estado, [OrdenCompra::ESTADO_RECIBIDA_COMPLETO, OrdenCompra::ESTADO_CANCELADA])): ?>
                <?= Html::a('Cancelar Orden', ['cancelar', 'id' => $model->id], [
                    'class' => 'btn btn-secondary',
                    'data-confirm' => '¿Está seguro que desea cancelar esta orden?',
                ]) ?>
            <?php endif; ?>
            
            <?= Html::a('< Volver', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <!-- Estado -->
    <div class="alert alert-<?= match($model->estado) {
        OrdenCompra::ESTADO_BORRADOR => 'secondary',
        OrdenCompra::ESTADO_ENVIADA => 'warning',
        OrdenCompra::ESTADO_RECIBIDA_PARCIAL => 'info',
        OrdenCompra::ESTADO_RECIBIDA_COMPLETO => 'success',
        OrdenCompra::ESTADO_CANCELADA => 'danger',
        default => 'secondary',
    } ?>">
        <strong>Estado:</strong> <?= OrdenCompra::getEstadosOpciones()[$model->estado] ?? $model->estado ?>
    </div>

    <div class="row">
        <div class="col-md-6">
            <?= DetailView::widget([
                'model' => $model,
                'attributes' => [
                    'numero_orden',
                    [
                        'attribute' => 'proveedor_id',
                        'value' => $model->proveedor ? $model->proveedor->nombre : '-',
                    ],
                    'fecha_emision:date',
                    'fecha_entrega_esperada:date',
                    'fecha_entrega_real:date',
                    [
                        'attribute' => 'total_monto',
'value' => '$' . number_format((float)$model->total_monto, 0, ',', '.'),
                    ],
                    'observaciones:ntext',
                    [
                        'attribute' => 'created_by',
                        'value' => $model->createdBy ? $model->createdBy->username : '-',
                    ],
                    'created_at:datetime',
                ],
            ]) ?>
        </div>
    </div>

    <!-- Items de la orden -->
    <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Items de la Orden</h3>
            <?php if ($model->estado === OrdenCompra::ESTADO_BORRADOR): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarItem">
                    <i class="fas fa-plus"></i> Agregar Item
                </button>
            <?php endif; ?>
        </div>

        <?php if ($model->items && count($model->items) > 0): ?>
            <?php
            $itemsProvider = new ArrayDataProvider([
                'allModels' => $model->items,
                'pagination' => false,
            ]);

            $columns = [
                ['class' => 'yii\grid\SerialColumn'],
                [
                    'label' => 'Descripción',
                    'attribute' => 'descripcion',
                ],
                [
                    'label' => 'Cantidad',
                    'attribute' => 'cantidad',
                ],
                [
                    'label' => 'Recibida',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $cls = $model->esRecibidoCompleto() ? 'bg-success' : ($model->esRecibidoParcial() ? 'bg-info' : 'bg-secondary');
                        return '<span class="badge ' . $cls . '">' . $model->cantidad_recibida . '</span>';
                    },
                ],
                [
                    'label' => 'Pendiente',
                    'value' => fn($model) => $model->getCantidadPendiente(),
                ],
                [
                    'label' => 'Precio Unit.',
                    'value' => fn($model) => '$' . number_format((float)$model->precio_unitario, 0, ',', '.'),
                ],
                [
                    'label' => 'Subtotal',
                    'value' => fn($model) => '$' . number_format((float)$model->subtotal, 0, ',', '.'),
                ],
                [
                    'label' => '% Recibido',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $pct = $model->getPorcentajeRecibido();
                        $cls = $model->esRecibidoCompleto() ? 'success' : 'warning';
                        return '<div class="progress" style="height: 20px;">'
                            . '<div class="progress-bar bg-' . $cls . '" role="progressbar" style="width: ' . $pct . '%;" '
                            . 'aria-valuenow="' . $pct . '" aria-valuemin="0" aria-valuemax="100">'
                            . $pct . '%</div></div>';
                    },
                ],
            ];

            if ($model->estado === OrdenCompra::ESTADO_BORRADOR) {
                $columns[] = [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{delete}',
                    'buttons' => [
                        'delete' => fn($url, $model) => Html::a(
                            '<i class="fas fa-trash"></i>',
                            ['eliminar-item', 'id' => $model->id],
                            ['class' => 'btn btn-sm btn-outline-danger', 'data-confirm' => '¿Eliminar este item?']
                        ),
                    ],
                ];
            }
            ?>
            <?= GridView::widget([
                'dataProvider' => $itemsProvider,
                'tableOptions' => ['class' => 'table table-bordered table-striped mb-0'],
                'layout' => '{items}',
                'columns' => $columns,
            ]) ?>
            <div class="d-flex justify-content-end mt-2">
                <strong class="me-2">Total:</strong>
                <strong>$<?= number_format((float)$model->total_monto, 0, ',', '.') ?></strong>
            </div>
        <?php else: ?>
            <div class="alert alert-info">Esta orden no tiene items. Agregue items antes de enviar.</div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal para agregar item -->
<div class="modal fade" id="modalAgregarItem" tabindex="-1" aria-labelledby="modalAgregarItemLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <?php $form = \yii\widgets\ActiveForm::begin([
                'action' => ['agregar-item', 'id' => $model->id],
                'method' => 'POST',
                'options' => ['data-pjax' => true],
            ]); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAgregarItemLabel">Agregar Item a la Orden</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= $form->field($itemModel, 'inventory_item_id')->dropDownList($listaInventario, ['prompt' => 'Seleccionar del inventario...', 'id' => 'inventory_select'])->label('Seleccionar del Inventario (Opcional)') ?>
                    
                    <?= $form->field($itemModel, 'descripcion')->textInput(['maxlength' => true, 'id' => 'item_descripcion']) ?>
                    
                    <?= $form->field($itemModel, 'cantidad')->input('number', ['min' => 1, 'value' => 1]) ?>
                    
                    <?= $form->field($itemModel, 'precio_unitario')->input('number', ['step' => '0.01', 'min' => 0]) ?>
                    
                    <?= $form->field($itemModel, 'observaciones')->textarea(['rows' => 3]) ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <?= Html::submitButton('Agregar Item', ['class' => 'btn btn-success']) ?>
                </div>
            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>
</div>

<?php
// Script para autocompletar descripción cuando se selecciona un item del inventario
$script = <<<JS
document.getElementById('inventory_select').addEventListener('change', function() {
    // Aquí se podría hacer una llamada AJAX para obtener los datos del item seleccionado
    // Por ahora, solo es un placeholder para futura implementación
    console.log('Item seleccionado:', this.value);
});
JS;
$this->registerJs($script);
?>
