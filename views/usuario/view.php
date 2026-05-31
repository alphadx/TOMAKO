<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="usuario-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="btn-group">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?php if ($model->activo): ?>
                <?= Html::a('<i class="bi bi-person-x me-1"></i>Desactivar', ['deactivate', 'id' => $model->id], [
                    'class' => 'btn btn-danger',
                    'data-method'  => 'post',
                    'data-confirm' => '¿Desactivar este usuario?',
                ]) ?>
            <?php endif; ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= DetailView::widget([
                'model'      => $model,
                'attributes' => [
                    'id',
                    'username',
                    'email',
                    'nombre',
                    'apellido',
                    [
                        'label'   => 'Nombre Completo',
                        'value'   => $model->getFullName(),
                    ],
                    [
                        'label'   => 'Rol',
                        'value'   => $model->rol->nombre ?? '—',
                        'format'  => 'raw',
                        'content' => fn($model) => $model->rol
                            ? '<span class="badge bg-secondary">' . Html::encode($model->rol->nombre) . '</span>'
                            : '<span class="text-muted">—</span>',
                    ],
                    [
                        'attribute' => 'activo',
                        'format'    => 'raw',
                        'value'     => $model->activo
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-danger">Inactivo</span>',
                    ],
                    [
                        'attribute' => 'ultimo_login',
                        'value'     => $model->ultimo_login ? date('d/m/Y H:i:s', $model->ultimo_login) : '—',
                    ],
                    [
                        'attribute' => 'created_at',
                        'value'     => $model->created_at ? date('d/m/Y H:i:s', $model->created_at) : '—',
                    ],
                    [
                        'attribute' => 'updated_at',
                        'value'     => $model->updated_at ? date('d/m/Y H:i:s', $model->updated_at) : '—',
                    ],
                ],
            ]) ?>
        </div>
    </div>
</div>
