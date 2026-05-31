<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Especialidades';
$this->params['breadcrumbs'][] = ['label' => 'Técnicos', 'url' => ['/tecnico/index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="especialidad-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-tags me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Especialidad', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'tableOptions' => ['class' => 'table table-hover table-striped align-middle mb-0'],
                'headerRowOptions' => ['class' => 'table-dark'],
                'layout' => '{items}{pager}',
                'pager' => [
                    'class' => \yii\bootstrap5\LinkPager::class,
                    'options' => ['class' => 'pagination justify-content-center mt-3'],
                ],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    'nombre',
                    [
                        'attribute' => 'descripcion',
                        'value' => fn($model) => $model->descripcion ?? '—',
                        'contentOptions' => ['class' => 'text-muted small'],
                    ],
                    [
                        'label' => 'Técnicos',
                        'format' => 'raw',
                        'value' => fn($model) => '<span class="badge bg-info text-dark">' . count($model->tecnicos) . '</span>',
                    ],
                    [
                        'attribute' => 'status',
                        'format' => 'raw',
                        'value' => fn($model) => $model->status
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>',
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{update} {deactivate}',
                        'buttons' => [
                            'update' => fn($url, $model) => Html::a(
                                '<i class="bi bi-pencil"></i><span>Editar</span>',
                                ['update', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']
                            ),
                            'deactivate' => fn($url, $model) => $model->status
                                ? Html::a(
                                    '<i class="bi bi-x-circle"></i><span>Desactivar</span>',
                                    ['deactivate', 'id' => $model->id],
                                    [
                                        'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                        'title'        => 'Desactivar',
                                        'data-method'  => 'post',
                                        'data-confirm' => '¿Desactivar esta especialidad?',
                                    ]
                                )
                                : '',
                        ],
                        'contentOptions' => ['class' => 'text-end'],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>