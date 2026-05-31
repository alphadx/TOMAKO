<?php
/** @var yii\web\View $this */
/** @var app\models\Especialidad[] $especialidades */

use yii\helpers\Html;

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
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Técnicos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($especialidades as $i => $esp): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><strong><?= Html::encode($esp->nombre) ?></strong></td>
                        <td class="text-muted small"><?= Html::encode($esp->descripcion ?? '—') ?></td>
                        <td>
                            <span class="badge bg-info text-dark"><?= count($esp->tecnicos) ?></span>
                        </td>
                        <td>
                            <?= $esp->status
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-secondary">Inactivo</span>' ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?= Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $esp->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']) ?>
                                <?php if ($esp->status): ?>
                                    <?= Html::a('<i class="bi bi-x-circle"></i><span>Desactivar</span>', ['deactivate', 'id' => $esp->id], [
                                        'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                        'title'        => 'Desactivar',
                                        'data-method'  => 'post',
                                        'data-confirm' => '¿Desactivar esta especialidad?',
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($especialidades)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-3">No hay especialidades registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
