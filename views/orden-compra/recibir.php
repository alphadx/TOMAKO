<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\OrdenCompra;

/** @var yii\web\View $this */
/** @var app\models\OrdenCompra $model */

$this->title = 'Recibir Orden de Compra: ' . $model->numero_orden;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Compra', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->numero_orden, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Recepción';
?>

<div class="orden-compra-recibir">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <p><strong>Proveedor:</strong> <?= $model->proveedor ? $model->proveedor->nombre : '-' ?></p>
        <p><strong>Fecha Esperada:</strong> <?= $model->fecha_entrega_esperada ?></p>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['recibir', 'id' => $model->id],
        'method' => 'POST',
    ]); ?>

    <?php if ($model->items && count($model->items) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Descripción</th>
                    <th>Cantidad Solicitada</th>
                    <th>Cantidad Recibida (Previo)</th>
                    <th>Pendiente</th>
                    <th>Cantidad a Recibir Ahora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($model->items as $index => $item): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td><?= Html::encode($item->descripcion) ?></td>
                        <td><?= $item->cantidad ?></td>
                        <td>
                            <span class="badge bg-<?= $item->esRecibidoCompleto() ? 'success' : ($item->esRecibidoParcial() ? 'info' : 'secondary') ?>">
                                <?= $item->cantidad_recibida ?>
                            </span>
                        </td>
                        <td><?= $item->getCantidadPendiente() ?></td>
                        <td style="width: 200px;">
                            <?php if (!$item->esRecibidoCompleto()): ?>
                                <input type="number" 
                                       name="cantidad_recibida[<?= $item->id ?>]" 
                                       class="form-control" 
                                       min="1" 
                                       max="<?= $item->getCantidadPendiente() ?>" 
                                       value="<?= $item->getCantidadPendiente() ?>"
                                       placeholder="Cantidad">
                            <?php else: ?>
                                <span class="text-success"><i class="fas fa-check"></i> Completo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="form-group mt-4">
            <?= Html::submitButton('<i class="fas fa-check"></i> Confirmar Recepción', ['class' => 'btn btn-success']) ?>
            <?= Html::a('Cancelar', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Esta orden no tiene items registrados.</div>
        <?= Html::a('Volver', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
    <?php endif; ?>

    <?php ActiveForm::end(); ?>

</div>
