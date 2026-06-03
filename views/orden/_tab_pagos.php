<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */
/** @var float $totalPagado */
/** @var float $saldoPendiente */

use yii\helpers\Html;

?>
<div class="orden-pagos-tab">
    <div class="mb-2 d-flex justify-content-between">
        <span>Total orden:</span>
        <strong>$ <?= number_format((float) $model->total, 0, ',', '.') ?></strong>
    </div>
    <div class="mb-2 d-flex justify-content-between">
        <span>Total pagado:</span>
        <strong class="text-success">$ <?= number_format((float)$totalPagado, 0, ',', '.') ?></strong>
    </div>
    <div class="mb-3 d-flex justify-content-between">
        <span>Saldo pendiente:</span>
        <strong class="<?= $saldoPendiente > 0 ? 'text-danger' : 'text-success' ?>">$ <?= number_format((float)$saldoPendiente, 0, ',', '.') ?></strong>
    </div>

    <div class="d-flex gap-2">
        <?= Html::a('Registrar pago', ['/pago/create', 'orden_id' => $model->id], ['class' => 'btn btn-sm btn-primary']) ?>
        <?= Html::a('Historial', ['/pago/historial', 'ordenId' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
    </div>
</div>
