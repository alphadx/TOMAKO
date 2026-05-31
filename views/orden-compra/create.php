<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\OrdenCompra $model */
/** @var array $proveedores */

$this->title = 'Crear Orden de Compra';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Compra', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-compra-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'proveedores' => $proveedores,
    ]) ?>

</div>
