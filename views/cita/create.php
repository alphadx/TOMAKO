<?php
/** @var yii\web\View $this */
/** @var app\models\Cita $model */
/** @var app\models\Cliente[] $clientes */
/** @var app\models\Servicio[] $servicios */

use yii\helpers\Html;

$this->title = 'Nueva Cita';
$this->params['breadcrumbs'][] = ['label' => 'Citas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cita-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-calendar-plus me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'clientes' => $clientes, 'servicios' => $servicios]) ?>
        </div>
    </div>
</div>
