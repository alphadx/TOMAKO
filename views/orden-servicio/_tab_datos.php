<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\OrdenServicio;

/** @var app\models\OrdenServicio $model */
?>

<div class="row">
    <div class="col-md-6">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-borderless'],
            'attributes' => [
                [
                    'label' => 'Cliente',
                    'value' => $model->cliente?->nombre ?? 'N/A',
                ],
                [
                    'label' => 'Vehículo',
                    'value' => implode(' - ', array_filter([
                        $model->vehiculo?->patente ?? null,
                        trim(($model->vehiculo?->marca ?? '') . ' ' . ($model->vehiculo?->modelo ?? '')),
                    ])) ?: 'N/A',
                ],
                [
                    'label' => 'Cita Relacionada',
                    'value' => $model->cita_id ? ($model->cita?->codigo ?? 'N/A') : 'N/A',
                ],
                [
                    'attribute' => 'estado',
                    'format' => 'raw',
                    'value' => $model->getEstadoBadgeClass(),
                ],
                [
                    'attribute' => 'prioridad',
                    'format' => 'raw',
                    'value' => $model->getPrioridadBadge(),
                ],
            ],
        ]) ?>
    </div>
    <div class="col-md-6">
        <?= DetailView::widget([
            'model' => $model,
            'options' => ['class' => 'table table-borderless'],
            'attributes' => [
                [
                    'attribute' => 'total',
                    'format' => ['decimal', 0],
                    'value' => (float) $model->total,
                ],
                [
                    'attribute' => 'opened_at',
                    'format' => 'datetime',
                    'value' => $model->opened_at,
                ],
                [
                    'attribute' => 'closed_at',
                    'format' => 'datetime',
                    'value' => $model->closed_at ?: null,
                ],
                [
                    'attribute' => 'created_at',
                    'format' => 'datetime',
                    'value' => $model->created_at,
                ],
                [
                    'attribute' => 'updated_at',
                    'format' => 'datetime',
                    'value' => $model->updated_at,
                ],
            ],
        ]) ?>
    </div>
</div>

<?php if ($model->notas_generales): ?>
<div class="mt-3">
    <h6>Notas Generales</h6>
    <p><?= nl2br(Html::encode($model->notas_generales)) ?></p>
</div>
<?php endif ?>