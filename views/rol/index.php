<?php
/** @var yii\web\View $this */
/** @var app\models\Rol[] $roles */

use yii\helpers\Html;

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
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Usuarios</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $rol): ?>
                    <tr>
                        <td><?= $rol->id ?></td>
                        <td><strong><?= Html::encode($rol->nombre) ?></strong></td>
                        <td class="text-muted"><?= Html::encode($rol->descripcion ?? '—') ?></td>
                        <td>
                            <?= $rol->activo
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-danger">Inactivo</span>' ?>
                        </td>
                        <td>
                            <span class="badge bg-primary rounded-pill">
                                <?= count($rol->usuarios) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <?= Html::a('<i class="bi bi-eye"></i><span>Ver</span>', ['view', 'id' => $rol->id],
                                ['class' => 'btn btn-sm btn-outline-primary me-1 ts-action-btn', 'title' => 'Ver']) ?>
                            <?= Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $rol->id],
                                ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
