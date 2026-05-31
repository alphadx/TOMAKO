<?php
declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\PlantillaChecklist $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['/servicio/index']];
$this->params['breadcrumbs'][] = ['label' => 'Plantillas de Checklist', 'url' => ['plantillas-index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="plantilla-checklist-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="fas fa-edit"></i> Editar', ['plantillas-update', 'id' => $model->id], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('<i class="fas fa-copy"></i> Duplicar', ['plantillas-duplicate', 'id' => $model->id], [
                'class' => 'btn btn-outline-secondary',
                'data-method' => 'post',
                'data-confirm' => '¿Duplicar esta plantilla?',
            ]) ?>
            <?= Html::a('<i class="fas fa-trash"></i> Eliminar', ['plantillas-delete', 'id' => $model->id], [
                'class' => 'btn btn-outline-danger',
                'data-method' => 'post',
                'data-confirm' => '¿Está seguro de eliminar esta plantilla?',
            ]) ?>
            <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['plantillas-index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Información de la Plantilla</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'servicio_id',
                                'value' => $model->servicio->nombre ?? '-',
                            ],
                            'nombre',
                            'descripcion:ntext',
                            [
                                'attribute' => 'activa',
                                'format' => 'boolean',
                                'label' => 'Estado',
                            ],
                            [
                                'attribute' => 'created_at',
                                'format' => 'datetime',
                            ],
                            [
                                'attribute' => 'updated_at',
                                'format' => 'datetime',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><strong>Resumen</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5 class="text-muted mb-1">Total de Items</h5>
                        <h2 class="mb-0"><?= count($model->items) ?></h2>
                    </div>
                    <div class="mb-3">
                        <h5 class="text-muted mb-1">Items Obligatorios</h5>
                        <h2 class="mb-0 text-primary">
                            <?php 
                            $obligatorios = 0;
                            foreach ($model->items as $item) {
                                if ($item->obligatorio) $obligatorios++;
                            }
                            echo $obligatorios;
                            ?>
                        </h2>
                    </div>
                    <hr>
                    <?= Html::a(
                        '<i class="fas fa-edit"></i> Editar Plantilla',
                        ['plantillas-update', 'id' => $model->id],
                        ['class' => 'btn btn-primary w-100']
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Items de la plantilla -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-list"></i> Items del Checklist</strong>
        </div>
        <div class="card-body">
            <?php if (empty($model->items)): ?>
                <p class="text-muted mb-0">
                    <em>No hay items en esta plantilla.</em>
                </p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Orden</th>
                            <th>Descripción</th>
                            <th style="width: 100px;">Obligatorio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($model->items as $item): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= $item->orden ?></span></td>
                                <td><?= Html::encode($item->descripcion) ?></td>
                                <td>
                                    <?php if ($item->obligatorio): ?>
                                        <span class="badge bg-danger">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark">No</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
