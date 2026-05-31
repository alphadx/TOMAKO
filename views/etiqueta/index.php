<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

/** @var app\models\search\EtiquetaSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $totalEtiquetas */
/** @var int $totalClientesConEtiquetas */

$this->title = 'Etiquetas de Clientes';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="etiqueta-index">
    <div class="row mb-3">
        <div class="col-12">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Etiquetas</h5>
                    <p class="display-4"><?= $totalEtiquetas ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Clientes Etiquetados</h5>
                    <p class="display-4"><?= $totalClientesConEtiquetas ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Listado de Etiquetas</span>
                    <?= Html::a('<i class="bi bi-plus-lg"></i> Nueva Etiqueta', ['create'], [
                        'class' => 'btn btn-success btn-sm',
                    ]) ?>
                </div>
                <div class="card-body">
                    <?php Pjax::begin(); ?>
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel' => $searchModel,
                        'columns' => [
                            [
                                'attribute' => 'nombre',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::tag('span', $model->nombre, ['class' => 'badge bg-' . $model->color]);
                                },
                            ],
                            'descripcion:ntext',
                            [
                                'attribute' => 'status',
                                'format' => 'boolean',
                                'filter' => ['0' => 'Inactivo', '1' => 'Activo'],
                            ],
                            [
                                'label' => 'Clientes',
                                'value' => function ($model) {
                                    return $model->countClientes;
                                },
                            ],
                            [
                                'class' => 'yii\grid\ActionColumn',
                                'template' => '{view} {update} {delete} {export}',
                                'buttons' => [
                                    'view' => function ($url, $model) {
                                        return Html::a('<i class="bi bi-eye"></i>', $url, [
                                            'class' => 'btn btn-sm btn-outline-primary',
                                            'title' => 'Ver',
                                        ]);
                                    },
                                    'update' => function ($url, $model) {
                                        return Html::a('<i class="bi bi-pencil"></i>', $url, [
                                            'class' => 'btn btn-sm btn-outline-warning',
                                            'title' => 'Editar',
                                        ]);
                                    },
                                    'delete' => function ($url, $model) {
                                        return Html::a('<i class="bi bi-trash"></i>', $url, [
                                            'class' => 'btn btn-sm btn-outline-danger',
                                            'data-confirm' => '¿Está seguro que desea eliminar esta etiqueta?',
                                            'data-method' => 'post',
                                            'title' => 'Eliminar',
                                        ]);
                                    },
                                    'export' => function ($url, $model) {
                                        return Html::a('<i class="bi bi-download"></i>', ['/cliente/export-segmento', 'etiqueta_id' => $model->id], [
                                            'class' => 'btn btn-sm btn-outline-success',
                                            'title' => 'Exportar clientes con esta etiqueta',
                                        ]);
                                    },
                                ],
                            ],
                        ],
                    ]); ?>
                    <?php Pjax::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
