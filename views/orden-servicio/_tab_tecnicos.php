<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

/** @var app\models\OrdenServicio $model */
?>

<?php if (empty($model->asignaciones)): ?>
    <p class="text-muted">Sin técnicos asignados</p>
<?php else: ?>
    <?php
    $asignacionesProvider = new ArrayDataProvider([
        'allModels' => $model->asignaciones,
        'pagination' => false,
    ]);
    ?>
    <?= GridView::widget([
        'dataProvider' => $asignacionesProvider,
        'tableOptions' => ['class' => 'table table-striped mb-0'],
        'layout' => '{items}',
        'columns' => [
            [
                'label' => 'Técnico',
                'value' => fn($model) => Html::encode($model->tecnico?->nombre ?? 'N/A'),
            ],
            [
                'label' => 'Especialidad',
                'value' => fn($model) => Html::encode($model->tecnico?->especialidad?->nombre ?? 'N/A'),
            ],
            [
                'label' => 'Asignado',
                'value' => fn($model) => $model->asignado_at ? date('d/m/Y H:i', $model->asignado_at) : 'N/A',
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{remove}',
                'buttons' => [
                    'remove' => fn($url, $model) => Html::a(
                        '<i class="bi bi-x"></i>',
                        '#',
                        ['class' => 'btn btn-sm btn-danger']
                    ),
                ],
                'contentOptions' => ['class' => 'text-end'],
            ],
        ],
    ]); ?>
<?php endif ?>
