<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\OrdenServicio $model */
?>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Servicio</th>
            <th class="text-end">Cantidad</th>
            <th class="text-end">Precio Unitario</th>
            <th class="text-end">Subtotal</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($model->detalles as $detalle): ?>
            <tr>
                <td><?= Html::encode($detalle->servicio->nombre ?? 'N/A') ?></td>
                <td class="text-end"><?= $detalle->cantidad ?></td>
                <td class="text-end"><?= number_format((float)$detalle->precio_unitario, 0, ',', '.') ?></td>
                <td class="text-end"><strong><?= number_format((float)$detalle->subtotal, 0, ',', '.') ?></strong></td>
                <td class="text-end">
                    <?= Html::a('<i class="bi bi-trash"></i>', '#', ['class' => 'btn btn-sm btn-danger']) ?>
                </td>
            </tr>
        <?php endforeach ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-end">TOTAL:</th>
            <th class="text-end"><strong>$<?= number_format((float)$model->total, 0, ',', '.') ?></strong></th>
            <th></th>
        </tr>
    </tfoot>
</table>
