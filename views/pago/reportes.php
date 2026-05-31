<?php
/** @var yii\web\View $this */
/** @var string $tipo */
/** @var string $desde */
/** @var string $hasta */
/** @var array<int, array<string, scalar>> $data */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $tipo === 'metodo' ? 'Reporte por Metodo de Pago' : 'Reporte de Ingresos';
$this->params['breadcrumbs'][] = ['label' => 'Pagos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-reportes">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('Ingresos', ['reporte-ingresos', 'desde' => $desde, 'hasta' => $hasta], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Por Metodo', ['reporte-por-metodo', 'desde' => $desde, 'hasta' => $hasta], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Exportar CSV', ['exportar-csv', 'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta], ['class' => 'btn btn-success btn-sm']) ?>
        </div>
    </div>

    <form method="get" class="row g-2 mb-3">
        <input type="hidden" name="r" value="pago/<?= $tipo === 'metodo' ? 'reporte-por-metodo' : 'reporte-ingresos' ?>">
        <div class="col-md-3">
            <label class="form-label">Desde</label>
            <input type="date" name="desde" value="<?= Html::encode($desde) ?>" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Hasta</label>
            <input type="date" name="hasta" value="<?= Html::encode($hasta) ?>" class="form-control">
        </div>
        <div class="col-md-2 align-self-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <?php if ($tipo === 'metodo'): ?>
                        <th>Metodo</th>
                    <?php else: ?>
                        <th>Fecha</th>
                    <?php endif; ?>
                    <th>Total</th>
                    <th>Cantidad</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="3" class="text-center text-muted py-4">Sin datos para el periodo.</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <td><?= Html::encode((string) ($row[$tipo === 'metodo' ? 'metodo' : 'fecha'] ?? '')) ?></td>
                            <td><?= \app\components\helpers\FormatHelper::moneda($row['total'] ?? 0) ?></td>
                            <td><?= (int) ($row['cantidad'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
