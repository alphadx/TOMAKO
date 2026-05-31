<?php
/** @var yii\web\View $this */
/** @var app\models\Pago $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Pago #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Pagos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-cash-coin me-2"></i><?= Html::encode($this->title) ?>
            <span class="badge <?= $model->getEstadoBadgeClass() ?> ms-2"><?= Html::encode($model->getEstadoLabel()) ?></span>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($model->estado === 'pendiente'): ?>
                <?= Html::a(
                    '<i class="bi bi-check-circle me-1"></i>Confirmar',
                    ['confirmar', 'id' => $model->id],
                    [
                        'class' => 'btn btn-success btn-sm',
                        'data'  => [
                            'method' => 'post',
                            'confirm' => '¿Confirmar este pago?',
                        ],
                    ]
                ) ?>
                <?= Html::a(
                    '<i class="bi bi-x-circle me-1"></i>Anular',
                    ['anular', 'id' => $model->id],
                    [
                        'class' => 'btn btn-danger btn-sm',
                        'data'  => [
                            'method' => 'post',
                            'confirm' => '¿Anular este pago?',
                        ],
                    ]
                ) ?>
            <?php elseif ($model->estado === 'pagado'): ?>
                <?= Html::a(
                    '<i class="bi bi-x-circle me-1"></i>Anular',
                    ['anular', 'id' => $model->id],
                    [
                        'class' => 'btn btn-outline-danger btn-sm',
                        'data'  => [
                            'method' => 'post',
                            'confirm' => '¿Anular este pago?',
                        ],
                    ]
                ) ?>
            <?php endif; ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?= DetailView::widget([
                'model'      => $model,
                'options'    => ['class' => 'table table-bordered mb-0'],
                'attributes' => [
                    'id',
                    ['label' => 'Orden', 'format' => 'raw',
                     'value' => $model->orden
                        ? Html::a(Html::encode($model->orden->codigo), ['/orden/view', 'id' => $model->orden_id])
                        : '—'],
                    ['label' => 'Cliente', 'format' => 'raw',
                     'value' => $model->orden && $model->orden->cliente
                        ? Html::encode($model->orden->cliente->nombre)
                        : '—'],
                    ['label' => 'Monto', 'value' => \app\components\helpers\FormatHelper::moneda($model->monto)],
                    ['label' => 'Método de Pago', 'value' => $model->getMetodoPagoLabel()],
                    'referencia',
                    ['label' => 'Estado', 'format' => 'raw',
                     'value' => '<span class="badge ' . $model->getEstadoBadgeClass() . '">' . Html::encode($model->getEstadoLabel()) . '</span>'],
                    ['label' => 'Fecha de Pago',
                     'value' => $model->pagado_at ? date('d/m/Y H:i', $model->pagado_at) : '—'],
                    'notas',
                    ['label' => 'Registrado por',
                     'value' => $model->usuario ? Html::encode($model->usuario->username) : 'Sistema'],
                    ['label' => 'Creado', 'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—'],
                ],
            ]) ?>
        </div>
    </div>
</div>
