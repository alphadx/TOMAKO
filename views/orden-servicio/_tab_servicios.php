<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/** @var app\models\OrdenServicio $model */
?>

<?php
$detallesProvider = new ArrayDataProvider([
    'allModels' => $model->detalles,
    'pagination' => false,
]);
?>
<?= GridView::widget([
    'dataProvider' => $detallesProvider,
    'tableOptions' => ['class' => 'table table-striped mb-0'],
    'layout' => '{items}',
    'columns' => [
        [
            'label' => 'Servicio',
            'value' => fn($model) => Html::encode($model->servicio?->nombre ?? 'N/A'),
        ],
        [
            'attribute' => 'cantidad',
            'contentOptions' => ['class' => 'text-end'],
            'headerOptions' => ['class' => 'text-end'],
        ],
        [
            'label' => 'Precio Unitario',
            'value' => fn($model) => number_format((float)$model->precio_unitario, 0, ',', '.'),
            'contentOptions' => ['class' => 'text-end'],
            'headerOptions' => ['class' => 'text-end'],
        ],
        [
            'label' => 'Subtotal',
            'value' => fn($model) => '<strong>' . number_format((float)$model->subtotal, 0, ',', '.') . '</strong>',
            'format' => 'raw',
            'contentOptions' => ['class' => 'text-end'],
            'headerOptions' => ['class' => 'text-end'],
        ],
        [
            'class' => 'yii\grid\ActionColumn',
            'template' => '{delete}',
            'buttons' => [
                'delete' => fn($url, $model) => Html::a(
                    '<i class="bi bi-trash"></i>',
                    '#',
                    ['class' => 'btn btn-sm btn-danger']
                ),
            ],
            'contentOptions' => ['class' => 'text-end'],
        ],
    ],
]) ?>

<div class="d-flex justify-content-end mt-2">
    <strong class="me-2">TOTAL:</strong>
    <strong>$<?= number_format((float)$model->total, 0, ',', '.') ?></strong>
</div>
