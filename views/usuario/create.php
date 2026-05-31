<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var array $roles */

use yii\helpers\Html;

$this->title = 'Nuevo Usuario';
$this->params['breadcrumbs'][] = ['label' => 'Usuarios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="usuario-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person-plus me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'roles' => $roles, 'isUpdate' => false]) ?>
        </div>
    </div>
</div>
