<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\OrdenCompra;

/** @var yii\web\View $this */
/** @var app\models\OrdenCompra $model */
/** @var array $proveedores */

$this->title = 'Actualizar Orden de Compra';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Compra', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->numero_orden, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Actualizar';
?>

<div class="orden-compra-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'proveedores' => $proveedores,
    ]) ?>

</div>
