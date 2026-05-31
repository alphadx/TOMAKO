<?php
declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\search\PlantillaChecklistSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int|null $servicioId */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

$this->title = 'Plantillas de Checklist';
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['/servicio/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="plantilla-checklist-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <?= Html::a(
            '<i class="fas fa-plus-circle"></i> Nueva Plantilla',
            ['plantillas-create', 'servicioId' => $servicioId],
            ['class' => 'btn btn-success']
        ) ?>
    </div>

    <?php if ($servicioId): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Mostrando plantillas para el servicio ID: <?= $servicioId ?>
            <?= Html::a('Ver todas', ['plantillas-index'], ['class' => 'btn btn-sm btn-outline-info ms-2']) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php Pjax::begin(['id' => 'plantillas-pjax']); ?>
            
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'tableOptions' => ['class' => 'table table-hover'],
                'columns' => [
                    [
                        'attribute' => 'id',
                        'headerOptions' => ['style' => 'width: 80px;'],
                    ],
                    [
                        'attribute' => 'servicio_id',
                        'value' => function ($model) {
                            return $model->servicio->nombre ?? '-';
                        },
                        'filter' => \app\models\Servicio::find()->orderBy(['nombre' => SORT_ASC])->all(),
                        'filter' => \yii\helpers\ArrayHelper::map(
                            \app\models\Servicio::find()->orderBy(['nombre' => SORT_ASC])->all(),
                            'id',
                            'nombre'
                        ),
                    ],
                    'nombre',
                    [
                        'attribute' => 'descripcion',
                        'format' => 'ntext',
                        'contentOptions' => ['style' => 'max-width: 300px; word-wrap: break-word;'],
                    ],
                    [
                        'attribute' => 'activa',
                        'format' => 'boolean',
                        'filter' => ['No', 'Sí'],
                        'headerOptions' => ['style' => 'width: 100px;'],
                    ],
                    [
                        'label' => 'Items',
                        'value' => function ($model) {
                            return count($model->items);
                        },
                        'headerOptions' => ['style' => 'width: 80px;'],
                    ],
                    [
                        'class' => '\yii\grid\ActionColumn',
                        'header' => 'Acciones',
                        'headerOptions' => ['style' => 'width: 150px;'],
                        'template' => '{view} {update} {duplicate} {delete}',
                        'buttons' => [
                            'view' => function ($url, $model) {
                                return Html::a('<i class="fas fa-eye"></i>', $url, [
                                    'class' => 'btn btn-sm btn-outline-info',
                                    'title' => 'Ver detalle',
                                ]);
                            },
                            'update' => function ($url, $model) {
                                return Html::a('<i class="fas fa-edit"></i>', $url, [
                                    'class' => 'btn btn-sm btn-outline-primary',
                                    'title' => 'Editar',
                                ]);
                            },
                            'duplicate' => function ($url, $model) {
                                return Html::a('<i class="fas fa-copy"></i>', $url, [
                                    'class' => 'btn btn-sm btn-outline-secondary',
                                    'title' => 'Duplicar',
                                    'data-method' => 'post',
                                    'data-confirm' => '¿Está seguro de duplicar esta plantilla?',
                                ]);
                            },
                            'delete' => function ($url, $model) {
                                return Html::a('<i class="fas fa-trash"></i>', $url, [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Eliminar',
                                    'data-method' => 'post',
                                    'data-confirm' => '¿Está seguro de eliminar esta plantilla? Esta acción no se puede deshacer.',
                                ]);
                            },
                        ],
                    ],
                ],
                'pager' => [
                    'class' => \yii\widgets\LinkPager::class,
                    'options' => ['class' => 'pagination justify-content-center'],
                ],
                'emptyText' => 'No hay plantillas registradas.',
                'emptyTextOptions' => ['class' => 'text-center text-muted py-4'],
            ]) ?>

            <?php Pjax::end(); ?>
        </div>
    </div>
</div>
