<?php
declare(strict_types=1);

use yii\helpers\Html;
use app\models\OrdenServicio;

/** @var app\models\OrdenServicio $model */
?>

<div class="row">
    <div class="col-md-6">
        <table class="table table-borderless">
            <tr>
                <th>Cliente:</th>
                <td><?= Html::encode($model->cliente->nombre ?? 'N/A') ?></td>
            </tr>
            <tr>
                <th>Vehículo:</th>
                <td><?= Html::encode($model->vehiculo->patente ?? 'N/A') ?> - <?= Html::encode($model->vehiculo->marca ?? '') ?> <?= Html::encode($model->vehiculo->modelo ?? '') ?></td>
            </tr>
            <tr>
                <th>Cita Relacionada:</th>
                <td><?= $model->cita_id ? Html::encode($model->cita->codigo ?? 'N/A') : 'N/A' ?></td>
            </tr>
            <tr>
                <th>Estado:</th>
                <td><?= Html::raw($model->getEstadoBadgeClass()) ?></td>
            </tr>
            <tr>
                <th>Prioridad:</th>
                <td><?= Html::raw($model->getPrioridadBadge()) ?></td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table table-borderless">
            <tr>
                <th>Total:</th>
                <td class="text-end"><strong>$<?= number_format($model->total, 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <th>Abierta:</th>
                <td><?= $model->opened_at ? date('d/m/Y H:i', $model->opened_at) : 'N/A' ?></td>
            </tr>
            <tr>
                <th>Cerrada:</th>
                <td><?= $model->closed_at ? date('d/m/Y H:i', $model->closed_at) : 'Abierta' ?></td>
            </tr>
            <tr>
                <th>Creada:</th>
                <td><?= date('d/m/Y H:i', $model->created_at) ?></td>
            </tr>
            <tr>
                <th>Actualizada:</th>
                <td><?= date('d/m/Y H:i', $model->updated_at) ?></td>
            </tr>
        </table>
    </div>
</div>

<?php if ($model->notas_generales): ?>
<div class="mt-3">
    <h6>Notas Generales</h6>
    <p><?= nl2br(Html::encode($model->notas_generales)) ?></p>
</div>
<?php endif ?>
