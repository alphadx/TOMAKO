<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var array $roles */

use yii\helpers\Html;

$this->title = 'Editar Usuario: ' . $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->getFullName(), 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="usuario-update">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-pencil-square me-2"></i><?= Html::encode($this->title) ?>
            <small class="text-muted fs-6 ms-2">ID: <?= $model->id ?></small>
        </h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'roles' => $roles, 'isUpdate' => true]) ?>
        </div>
    </div>
</div>
