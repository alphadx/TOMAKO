<?php
/** @var yii\web\View $this */
/** @var app\models\Marca[] $marcas */

use yii\helpers\Html;

$this->title = 'Mantenedor de Marcas y Modelos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="marca-modelo-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-car-front me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Marca', ['create-marca'], ['class' => 'btn btn-success']) ?>
    </div>

    <div class="row">
        <?php foreach ($marcas as $marca): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-car-front-fill me-2"></i><?= Html::encode($marca->nombre) ?></h5>
                    <div class="btn-group btn-group-sm">
                        <?= Html::a('<i class="bi bi-pencil"></i>', ['update-marca', 'id' => $marca->id], [
                            'class' => 'btn btn-light',
                            'title' => 'Editar marca',
                        ]) ?>
                        <?= Html::a('<i class="bi bi-trash"></i>', ['delete-marca', 'id' => $marca->id], [
                            'class' => 'btn btn-light',
                            'title' => 'Eliminar marca',
                            'data-confirm' => '¿Está seguro que desea eliminar esta marca? Esta acción no se puede deshacer.',
                            'data-method' => 'post',
                        ]) ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary"><?= count($marca->modelos) ?> modelo(s)</span>
                        <?= Html::a('<i class="bi bi-plus-sm me-1"></i>Agregar Modelo', ['create-modelo', 'marcaId' => $marca->id], [
                            'class' => 'btn btn-sm btn-outline-primary',
                        ]) ?>
                    </div>
                    
                    <?php if (empty($marca->modelos)): ?>
                        <p class="text-muted fst-italic"><small>No hay modelos registrados</small></p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($marca->modelos as $modelo): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <span><i class="bi bi-circle me-2 small"></i><?= Html::encode($modelo->nombre) ?></span>
                                <div class="btn-group btn-group-sm">
                                    <?= Html::a('<i class="bi bi-pencil"></i>', ['update-modelo', 'id' => $modelo->id], [
                                        'class' => 'btn btn-outline-secondary',
                                        'title' => 'Editar modelo',
                                    ]) ?>
                                    <?= Html::a('<i class="bi bi-trash"></i>', ['delete-modelo', 'id' => $modelo->id], [
                                        'class' => 'btn btn-outline-danger',
                                        'title' => 'Eliminar modelo',
                                        'data-confirm' => '¿Está seguro que desea eliminar este modelo?',
                                        'data-method' => 'post',
                                    ]) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-transparent">
                    <?= Html::a('Ver todos los modelos <i class="bi bi-arrow-right"></i>', ['view', 'id' => $marca->id], [
                        'class' => 'btn btn-sm btn-outline-primary w-100',
                    ]) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($marcas)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>No hay marcas registradas. Comience creando una nueva marca.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
