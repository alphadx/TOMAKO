<?php
/** @var yii\web\View $this */
/** @var app\models\Rol $model */
/** @var array $permisos  Permisos agrupados por módulo */
/** @var int[] $asignados IDs de permisos asignados */

use yii\helpers\Html;

$this->title = 'Rol: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="rol-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-shield-check me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Información del Rol</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">ID:</dt>
                        <dd class="col-7"><?= $model->id ?></dd>
                        <dt class="col-5">Nombre:</dt>
                        <dd class="col-7"><strong><?= Html::encode($model->nombre) ?></strong></dd>
                        <dt class="col-5">Descripción:</dt>
                        <dd class="col-7"><?= Html::encode($model->descripcion ?? '—') ?></dd>
                        <dt class="col-5">Estado:</dt>
                        <dd class="col-7">
                            <?= $model->activo
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-danger">Inactivo</span>' ?>
                        </dd>
                        <dt class="col-5">Usuarios:</dt>
                        <dd class="col-7">
                            <span class="badge bg-primary"><?= count($model->usuarios) ?></span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Permisos Asignados</strong></div>
                <div class="card-body">
                    <?php if (empty($permisos)): ?>
                        <p class="text-muted">No hay permisos definidos en el sistema.</p>
                    <?php else: ?>
                        <?php foreach ($permisos as $modulo => $listaPermisos): ?>
                        <div class="mb-3">
                            <h6 class="text-uppercase text-muted small fw-bold mb-2">
                                <i class="bi bi-grid me-1"></i><?= Html::encode(ucfirst($modulo)) ?>
                            </h6>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($listaPermisos as $permiso): ?>
                                    <?php $activo = in_array($permiso->id, $asignados, true); ?>
                                    <span class="badge <?= $activo ? 'bg-success' : 'bg-light text-muted border' ?>">
                                        <?php if ($activo): ?><i class="bi bi-check-circle me-1"></i><?php endif; ?>
                                        <?= Html::encode($permiso->nombre) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
