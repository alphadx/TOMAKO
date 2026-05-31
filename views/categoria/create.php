<?php
/** @var yii\web\View $this */
/** @var app\models\Categoria $model */
/** @var array $tree */

use yii\helpers\Html;

$this->title = 'Nueva Categoría';
$this->params['breadcrumbs'][] = ['label' => 'Categorías', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="categoria-create">
    <h1 class="h3 mb-4"><i class="bi bi-tag me-2"></i><?= Html::encode($this->title) ?></h1>
    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'tree' => $tree]) ?>
        </div>
    </div>
</div>
