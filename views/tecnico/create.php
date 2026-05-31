<?php
/** @var yii\web\View $this */
/** @var app\models\Tecnico $model */

use yii\helpers\Html;

$this->title = 'Nuevo Técnico';
$this->params['breadcrumbs'][] = ['label' => 'Técnicos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="tecnico-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person-gear me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model]) ?>
        </div>
    </div>
</div>
