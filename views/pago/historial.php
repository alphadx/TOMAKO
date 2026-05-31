<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $orden */
/** @var app\models\Pago[] $pagos */
/** @var float $saldoPendiente */

use yii\helpers\Html;

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
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Metodo</th>
                    <th>Estado</th>
                    <th>Referencia</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Sin pagos registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><?= (int) $pago->id ?></td>
                            <td><?= $pago->created_at ? date('d/m/Y H:i', (int) $pago->created_at) : '—' ?></td>
                            <td><?= \app\components\helpers\FormatHelper::moneda($pago->monto) ?></td>
                            <td><?= Html::encode($pago->getMetodoPagoLabel()) ?></td>
                            <td>
                                <span class="badge <?= Html::encode($pago->getEstadoBadgeClass()) ?>">
                                    <?= Html::encode($pago->getEstadoLabel()) ?>
                                </span>
                            </td>
                            <td><?= Html::encode((string) $pago->referencia) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
