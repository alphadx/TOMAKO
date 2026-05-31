<?php
/** @var yii\web\View $this */
/** @var app\models\Rol $model */
/** @var array $permisos */

use yii\helpers\Html;

$this->title = 'Nuevo Rol';
$this->params['breadcrumbs'][] = ['label' => 'Roles', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="rol-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-shield-plus me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'permisos' => $permisos, 'asignados' => []]) ?>
        </div>
    </div>
</div>
