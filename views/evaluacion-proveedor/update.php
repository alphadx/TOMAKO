<?php

/**
 * @var yii\web\View $this
 * @var app\models\EvaluacionProveedor $model
 * @var array $proveedores
 * @var array $listaOrdenes
 */

$this->title = 'Editar Evaluación de Proveedor';
$this->params['breadcrumbs'][] = ['label' => 'Evaluaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->proveedor->nombre ?? 'Evaluación #' . $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="evaluacion-proveedor-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'proveedores' => $proveedores,
        'listaOrdenes' => $listaOrdenes,
    ]) ?>
</div>
