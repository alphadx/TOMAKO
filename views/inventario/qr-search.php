<?php

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Buscar por QR';
$this->params['breadcrumbs'][] = ['label' => 'Inventario', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="inventario-qr-search">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-qr-code me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-qr-code-scan me-1"></i>Escanear QR', ['qr-scan'], ['class' => 'btn btn-primary']) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <form action="<?= Url::to(['inventario/qr-search']) ?>" method="GET" class="d-flex gap-2">
                        <input type="text" name="q" class="form-control"
                               value="<?= Html::encode($qr) ?>"
                               placeholder="Ingresa codigo QR o SKU..."
                               autofocus>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Buscar</button>
                    </form>
                </div>
            </div>

            <?php if ($model !== null): ?>
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white"><strong><i class="bi bi-check-circle me-2"></i>Producto Encontrado</strong></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3">
                            <?php if ($model->imagenDefault): ?>
                                <img src="<?= $model->imagenDefault->getUrl() ?>" alt="<?= Html::encode($model->nombre) ?>" class="rounded" style="width:80px;height:80px;object-fit:cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:80px;height:80px;"><i class="bi bi-box text-muted" style="font-size:2rem;"></i></div>
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1"><?= Html::encode($model->nombre) ?></h5>
                                <p class="mb-1 text-muted small"><span class="badge bg-secondary font-monospace"><?= Html::encode($model->sku) ?></span> <span class="ms-2">QR: <?= Html::encode($model->qr_code) ?></span></p>
                                <p class="mb-0"><span class="badge bg-<?= $model->getEstadoStockClass() ?>">Stock: <?= $model->cantidad ?> <?= $model->unidad ?></span></p>
                            </div>
                        </div>
                        <div class="mt-3 text-end">
                            <?= Html::a('<i class="bi bi-eye me-1"></i>Ver Detalle', ['view', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        </div>
                    </div>
                </div>
            <?php elseif ($qr !== ''): ?>
                <div class="card shadow-sm border-warning">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-search text-warning" style="font-size:3rem;"></i>
                        <h5 class="mt-3">Producto no encontrado</h5>
                        <p class="text-muted">No se encontro un producto con el codigo: <strong><?= Html::encode($qr) ?></strong></p>
                        <?= Html::a('<i class="bi bi-qr-code-scan me-1"></i>Escanear otro QR', ['qr-scan'], ['class' => 'btn btn-outline-primary']) ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-qr-code text-muted" style="font-size:3rem;"></i>
                        <h5 class="mt-3">Buscar producto por QR</h5>
                        <p class="text-muted">Ingresa un codigo QR o utiliza el escaner de camara.</p>
                        <?= Html::a('<i class="bi bi-qr-code-scan me-1"></i>Escanear QR con Camara', ['qr-scan'], ['class' => 'btn btn-primary btn-lg']) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>