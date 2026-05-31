<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $orden */
/** @var app\models\Pago[] $pagos */
/** @var float $saldoPendiente */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

$this->title = 'Historial de Pagos - ' . $orden->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Pagos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-historial">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('Registrar Pago', ['create', 'orden_id' => $orden->id], ['class' => 'btn btn-primary btn-sm']) ?>
    </div>

    <div class="alert alert-info">
        <strong>Total orden:</strong> <?= \app\components\helpers\FormatHelper::moneda($orden->total) ?> |
        <strong>Saldo pendiente:</strong> <?= \app\components\helpers\FormatHelper::moneda($saldoPendiente) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php
            $pagosProvider = new ArrayDataProvider([
                'allModels' => $pagos,
                'pagination' => false,
            ]);
            ?>
            <?= GridView::widget([
                'dataProvider' => $pagosProvider,
                'tableOptions' => ['class' => 'table table-striped mb-0'],
                'layout' => '{items}',
                'emptyText' => 'Sin pagos registrados.',
                'columns' => [
                    'id',
                    [
                        'label' => 'Fecha',
                        'value' => fn($model) => $model->created_at ? date('d/m/Y H:i', (int) $model->created_at) : '—',
                    ],
                    [
                        'label' => 'Monto',
                        'value' => fn($model) => \app\components\helpers\FormatHelper::moneda($model->monto),
                    ],
                    [
                        'label' => 'Metodo',
                        'value' => fn($model) => Html::encode($model->getMetodoPagoLabel()),
                    ],
                    [
                        'label' => 'Estado',
                        'format' => 'raw',
                        'value' => fn($model) => '<span class="badge ' . Html::encode($model->getEstadoBadgeClass()) . '">'
                            . Html::encode($model->getEstadoLabel()) . '</span>',
                    ],
                    [
                        'label' => 'Referencia',
                        'value' => fn($model) => Html::encode((string) $model->referencia),
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>