<?php
/** @var yii\web\View $this */
/** @var app\models\Categoria $model */
/** @var array $tree */

use yii\helpers\Html;

$this->title = 'Editar: ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Categorías', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="categoria-update">
    <h1 class="h3 mb-4"><i class="bi bi-pencil-square me-2"></i><?= Html::encode($this->title) ?></h1>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'tree' => $tree]) ?>
        </div>
    </div>
</div>
