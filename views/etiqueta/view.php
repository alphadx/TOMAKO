<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var app\models\Etiqueta $model */

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Etiquetas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="etiqueta-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <span class="badge bg-<?= $model->color ?> me-2"><?= Html::encode($model->nombre) ?></span>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Detalle de Etiqueta</strong>
                    <?= $model->status
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>' ?>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'attributes' => [
                            'id',
                            'nombre',
                            [
                                'attribute' => 'color',
                                'format' => 'raw',
                                'value' => function ($model) {
                                    return Html::tag('span', $model->getColoresList()[$model->color] ?? $model->color, [
                                        'class' => 'badge bg-' . $model->color
                                    ]);
                                },
                            ],
                            'descripcion:ntext',
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
            <!-- Estadísticas de la etiqueta -->
            <div class="card mb-4">
                <div class="card-header"><strong>Resumen</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">Clientes con esta etiqueta</span>
                        <span class="badge bg-primary"><?= $model->countClientes ?></span>
                    </div>
                </div>
            </div>

            <?php if ($model->status): ?>
            <div class="card border-danger">
                <div class="card-body text-center">
                    <?= Html::a(
                        '<i class="bi bi-trash me-1"></i>Eliminar Etiqueta',
                        ['delete', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-outline-danger btn-sm',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Está seguro que desea eliminar esta etiqueta? Esta acción no se puede deshacer.',
                        ]
                    ) ?>
                </div>
            </div>
            <?php endif ?>

            <!-- Lista de clientes con esta etiqueta -->
            <?php if ($model->countClientes > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <strong>Clientes Etiquetados</strong>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($model->clientes->limit(10)->all() as $cliente): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= Html::encode($cliente->nombre) ?>
                                <?= Html::a(
                                    '<i class="bi bi-eye"></i>',
                                    ['/cliente/view', 'id' => $cliente->id],
                                    ['class' => 'btn btn-sm btn-outline-info', 'title' => 'Ver cliente']
                                ) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($model->countClientes > 10): ?>
                        <div class="card-footer text-center">
                            <small class="text-muted">+<?= $model->countClientes - 10 ?> clientes más</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
