<?php
/** @var yii\web\View $this */
/** @var app\models\Categoria $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Categoria;

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Categorías', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$tiposLabel = Categoria::getTiposList();
?>

<div class="categoria-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <?= $model->icono ? Html::encode($model->icono) . ' ' : '' ?>
            <?= Html::encode($this->title) ?>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a(
                '<i class="bi bi-trash me-1"></i>Eliminar',
                ['delete', 'id' => $model->id],
                [
                    'class'        => 'btn btn-outline-danger',
                    'data-method'  => 'post',
                    'data-confirm' => '¿Eliminar esta categoría? Solo se permite si está vacía.',
                ]
            ) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Datos de la Categoría</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'attributes' => [
                            'id',
                            'nombre',
                            'descripcion:text',
                            [
                                'label' => 'Categoría Padre',
                                'value' => $model->padre ? $model->padre->nombre : '— (raíz)',
                            ],
                            [
                                'label'  => 'Tipo',
                                'format' => 'raw',
                                'value'  => $tiposLabel[$model->tipo] ?? $model->tipo,
                            ],
                            'icono',
                            [
                                'label'  => 'Color',
                                'format' => 'raw',
                                'value'  => $model->color
                                    ? '<span class="badge" style="background:' . Html::encode($model->color) . '">' . Html::encode($model->color) . '</span>'
                                    : '—',
                            ],
                            'orden',
                            [
                                'label'  => 'Estado',
                                'format' => 'raw',
                                'value'  => $model->status
                                    ? '<span class="badge bg-success">Activo</span>'
                                    : '<span class="badge bg-danger">Inactivo</span>',
                            ],
                            [
                                'label' => 'Creado',
                                'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—',
                            ],
                            [
                                'label' => 'Actualizado',
                                'value' => $model->updated_at ? date('d/m/Y H:i', $model->updated_at) : '—',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if ($model->hijos): ?>
            <div class="card mb-4">
                <div class="card-header"><strong>Subcategorías (<?= count($model->hijos) ?>)</strong></div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($model->hijos as $hijo): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= Html::encode($hijo->nombre) ?>
                        <?= $hijo->status
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>' ?>
                    </li>
                    <?php endforeach ?>
                </ul>
            </div>
            <?php endif ?>

            <?php if ($model->status): ?>
            <div class="card border-danger">
                <div class="card-body text-center">
                    <?= Html::a(
                        '<i class="bi bi-x-circle me-1"></i>Desactivar Categoría',
                        ['deactivate', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-outline-danger btn-sm',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Desactivar esta categoría?',
                        ]
                    ) ?>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>
