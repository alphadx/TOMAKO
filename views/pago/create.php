<?php
/** @var yii\web\View $this */
/** @var app\models\Pago $model */
/** @var app\models\OrdenServicio[] $ordenes */
/** @var array<int, string> $metodosPago */

use yii\helpers\Html;

$this->title = 'Registrar Pago';
$this->params['breadcrumbs'][] = ['label' => 'Pagos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-create">
    <div class="d-flex align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-cash-coin me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'ordenes' => $ordenes, 'metodosPago' => $metodosPago]) ?>
        </div>
    </div>
</div>
