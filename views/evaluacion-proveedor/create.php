<?php

/**
 * @var yii\web\View $this
 * @var app\models\EvaluacionProveedor $model
 * @var array $proveedores
 * @var array $listaOrdenes
 */

$this->title = 'Crear Evaluación de Proveedor';
$this->params['breadcrumbs'][] = ['label' => 'Evaluaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="evaluacion-proveedor-create">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
        'proveedores' => $proveedores,
        'listaOrdenes' => $listaOrdenes,
    ]) ?>
</div>
