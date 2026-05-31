<?php
/** @var yii\web\View $this */
/** @var app\models\Rol $model */
/** @var array $permisos */
/** @var int[] $asignados */

use yii\helpers\Html;

$this->title = 'Editar Rol: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="rol-update">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-shield-shaded me-2"></i><?= Html::encode($this->title) ?>
        </h1>
    </div>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'permisos' => $permisos, 'asignados' => $asignados]) ?>
        </div>
    </div>
</div>
