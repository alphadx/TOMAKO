<?php
/** @var yii\web\View $this */
/** @var app\models\Servicio $model */
/** @var array $categorias */

use yii\helpers\Html;

$this->title = 'Editar: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="servicio-update">
    <h1 class="h3 mb-4"><i class="bi bi-pencil-square me-2"></i><?= Html::encode($this->title) ?></h1>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'categorias' => $categorias]) ?>
        </div>
    </div>
</div>
