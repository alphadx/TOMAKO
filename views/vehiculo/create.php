<?php
/** @var yii\web\View $this */
/** @var app\models\Vehiculo $model */
/** @var array<int,string> $clientes */

use yii\helpers\Html;

$this->title = 'Nuevo Vehículo';
$this->params['breadcrumbs'][] = ['label' => 'Vehículos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="vehiculo-create">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-car-front me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <?= $this->render('_form', ['model' => $model, 'clientes' => $clientes]) ?>
        </div>
    </div>
</div>

<?php
// Registrar el script de autoformateo de patente
$this->registerJsFile('@web/js/vehiculo-patente-formatter.js', ['depends' => [\yii\web\JqueryAsset::class]]);
?>
