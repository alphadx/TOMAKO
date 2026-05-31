<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */
/** @var app\models\Cliente[] $clientes */
/** @var app\models\Servicio[] $servicios */
/** @var app\models\Tecnico[] $tecnicos */

use yii\helpers\Html;

$this->title = 'Editar Orden ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->codigo, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="orden-update">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-pencil me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'clientes' => $clientes, 'servicios' => $servicios, 'tecnicos' => $tecnicos]) ?>
        </div>
    </div>
</div>
