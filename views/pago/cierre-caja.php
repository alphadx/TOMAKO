<?php
/** @var yii\web\View $this */
/** @var app\models\CierreCaja|null $cierreActual */
/** @var yii\data\ActiveDataProvider $cierresDataProvider */
/** @var array<string, float> $totalesMetodo */
/** @var string $desde */
/** @var string $hasta */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Cierre de Caja';
$this->params['breadcrumbs'][] = ['label' => 'Pagos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-cierre-caja">
    <h1 class="h4 mb-3"><?= Html::encode($this->title) ?></h1>

    <?php if ($cierreActual === null): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header">Abrir caja</div>
            <div class="card-body">
                <form method="post" action="<?= \yii\helpers\Url::to(['abrir-caja']) ?>" class="row g-2 align-items-end">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="col-md-4">
                        <label class="form-label">Monto inicial</label>
                        <input type="number" name="monto_inicial" min="0" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Abrir caja</button>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header">Caja abierta #<?= (int) $cierreActual->id ?> (<?= Html::encode($cierreActual->fecha) ?>)</div>
            <div class="card-body">
                <p class="mb-1"><strong>Monto inicial:</strong> <?= \app\components\helpers\FormatHelper::moneda($cierreActual->monto_inicial) ?></p>
                <?php if (!empty($totalesMetodo)): ?>
                    <ul class="mb-3">
                        <?php foreach ($totalesMetodo as $metodo => $monto): ?>
                            <li><?= Html::encode($metodo) ?>: <?= \app\components\helpers\FormatHelper::moneda($monto) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <form method="post" action="<?= \yii\helpers\Url::to(['cerrar-caja', 'id' => $cierreActual->id]) ?>" class="row g-2 align-items-end">
                    <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>
                    <div class="col-md-4">
                        <label class="form-label">Monto final contado</label>
                        <input type="number" name="monto_final" min="0" step="0.01" class="form-control" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success">Cerrar caja</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header">Historico de cierres</div>
        <div class="card-body">
            <form method="get" class="row g-2 mb-3">
                <input type="hidden" name="r" value="pago/cierre-caja">
                <div class="col-md-3">
                    <label class="form-label">Desde</label>
                    <input type="date" name="desde" value="<?= Html::encode($desde) ?>" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="hasta" value="<?= Html::encode($hasta) ?>" class="form-control">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-outline-primary">Filtrar</button>
                </div>
            </form>

            <?= GridView::widget([
                'dataProvider' => $cierresDataProvider,
                'tableOptions' => ['class' => 'table table-striped table-hover'],
                'columns' => [
                    'id',
                    'fecha',
                    [
                        'label' => 'Usuario',
                        'value' => static fn($m) => $m->usuario ? $m->usuario->username : '—',
                    ],
                    [
                        'label' => 'Inicial',
                        'value' => static fn($m) => \app\components\helpers\FormatHelper::moneda($m->monto_inicial),
                    ],
                    [
                        'label' => 'Esperado',
                        'value' => static fn($m) => \app\components\helpers\FormatHelper::moneda($m->monto_esperado),
                    ],
                    [
                        'label' => 'Final',
                        'value' => static fn($m) => \app\components\helpers\FormatHelper::moneda($m->monto_final),
                    ],
                    [
                        'label' => 'Diferencia',
                        'value' => static fn($m) => \app\components\helpers\FormatHelper::moneda($m->diferencia),
                    ],
                    'estado',
                ],
            ]) ?>
        </div>
    </div>
</div>
