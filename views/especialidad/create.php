<?php
/** @var yii\web\View $this */
/** @var app\models\Especialidad $model */

use yii\helpers\Html;

$this->title = 'Nueva Especialidad';
$this->params['breadcrumbs'][] = ['label' => 'Técnicos', 'url' => ['/tecnico/index']];
$this->params['breadcrumbs'][] = ['label' => 'Especialidades', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="especialidad-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-tags me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model]) ?>
        </div>
    </div>
</div>
