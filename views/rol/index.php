<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Roles del Sistema';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="rol-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-shield me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Rol', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <div class="card">
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
                    'id',
                    'nombre',
                    [
                        'attribute' => 'descripcion',
                        'value' => fn($model) => $model->descripcion ?? '—',
                        'contentOptions' => ['class' => 'text-muted'],
                    ],
                    [
                        'attribute' => 'activo',
                        'format' => 'raw',
                        'value' => fn($model) => $model->activo
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>',
                    ],
                    [
                        'label' => 'Usuarios',
                        'format' => 'raw',
                        'value' => fn($model) => '<span class="badge bg-primary rounded-pill">' . count($model->usuarios) . '</span>',
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update}',
                        'buttons' => [
                            'view' => fn($url, $model) => Html::a(
                                '<i class="bi bi-eye"></i><span>Ver</span>',
                                ['view', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-outline-primary me-1 ts-action-btn', 'title' => 'Ver']
                            ),
                            'update' => fn($url, $model) => Html::a(
                                '<i class="bi bi-pencil"></i><span>Editar</span>',
                                ['update', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']
                            ),
                        ],
                        'contentOptions' => ['class' => 'text-end'],
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>