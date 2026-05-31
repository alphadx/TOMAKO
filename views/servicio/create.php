<?php
/** @var yii\web\View $this */
/** @var app\models\Servicio $model */
/** @var array $categorias */

use yii\helpers\Html;

$this->title = 'Nuevo Servicio';
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="servicio-create">
    <h1 class="h3 mb-4"><i class="bi bi-tools me-2"></i><?= Html::encode($this->title) ?></h1>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'categorias' => $categorias]) ?>
        </div>
    </div>
</div>
