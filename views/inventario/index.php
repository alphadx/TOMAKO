<?php
/** @var yii\web\View $this */
/** @var app\models\search\InventoryItemSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array{total:int,alertas:int,valor_total:float} $kpis */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\InventoryItem;
use app\models\Categoria;

$this->title = 'Inventario';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="inventario-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-boxes me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-qr-code-scan me-1"></i>Escanear QR', ['qr-scan'], ['class' => 'btn btn-outline-info']) ?>
            <?= Html::a('<i class="bi bi-qr-code me-1"></i>Buscar QR', ['qr-search'], ['class' => 'btn btn-outline-info']) ?>
            <?= Html::a('<i class="bi bi-download me-1"></i>CSV', ['export-csv'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Ítem', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-primary"><?= $kpis['total'] ?></div>
                    <div class="text-muted small"><i class="bi bi-box me-1"></i>Total Insumos</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-<?= $kpis['alertas'] > 0 ? 'danger' : 'success' ?>"><?= $kpis['alertas'] ?></div>
                    <div class="text-muted small"><i class="bi bi-exclamation-triangle me-1"></i>Alertas Stock Bajo</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
<div class="fs-1 fw-bold text-success">$ <?= number_format((float)($kpis['valor_total']), 0, ',', '.') ?></div>
                    <div class="text-muted small"><i class="bi bi-currency-dollar me-1"></i>Valor Inventario</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <div class="fs-1 fw-bold text-info"><i class="bi bi-arrow-repeat"></i></div>
                    <div class="text-muted small"><i class="bi bi-clock me-1"></i>Actualización en Tiempo Real</div>
                    <small class="text-success">● Activo</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre...'])->label('Nombre') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'sku')->textInput(['placeholder' => 'SKU...'])->label('SKU') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'categoria_id')->dropDownList(
                        \yii\helpers\ArrayHelper::map(
                            Categoria::find()->where(['status' => 1])->orderBy('nombre')->all(),
                            'id', 'nombre'
                        ),
                        ['prompt' => 'Todas las categorías']
                    )->label('Categoría') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'estado_stock')->dropDownList([
                        ''          => 'Todos',
                        'sin_stock' => 'Sin Stock',
                        'bajo'      => 'Stock Bajo',
                        'en_stock'  => 'En Stock',
                    ])->label('Estado Stock') ?>
                </div>
                <div class="col-md-1">
                    <?= $form->field($searchModel, 'status')->dropDownList(['' => 'Todos', '1' => 'Activo', '0' => 'Inactivo'])->label('Estado') ?>
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1 ts-filter-actions">
                    <button type="submit" class="btn btn-primary ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'label'   => 'SKU',
                'format'  => 'raw',
                'value'   => fn($m) => '<span class="badge bg-secondary font-monospace">' . Html::encode($m->sku) . '</span>',
            ],
            'nombre',
            [
                'label' => 'Categoría',
                'value' => fn($m) => $m->categoria ? $m->categoria->nombre : '—',
            ],
            [
                'label'  => 'Precio',
'value'  => fn($m) => '$ ' . number_format((float)$m->precio_unitario, 0, ',', '.'),
            ],
            [
                'label'  => 'Stock',
                'format' => 'raw',
                'value'  => function ($m) {
                    $pct = $m->stock_maximo ? min(100, (int) ($m->cantidad / $m->stock_maximo * 100)) : 0;
                    $class = $m->getEstadoStockClass();
                    return '<div class="d-flex align-items-center gap-2">'
                         . '<span class="fw-bold">' . $m->cantidad . '</span>'
                         . ($m->stock_maximo
                             ? '<div class="progress flex-grow-1" style="height:6px">'
                               . '<div class="progress-bar bg-' . $class . '" style="width:' . $pct . '%"></div>'
                               . '</div>'
                             : '')
                         . '</div>';
                },
            ],
            [
                'label'  => 'Estado',
                'format' => 'raw',
                'value'  => function ($m) {
                    $estado = $m->getEstadoStock();
                    $class  = $m->getEstadoStockClass();
                    $labels = ['sin_stock' => 'Sin Stock', 'bajo' => 'Stock Bajo', 'en_stock' => 'En Stock'];
                    return '<span class="badge bg-' . $class . '">' . ($labels[$estado] ?? $estado) . '</span>';
                },
            ],
            [
                'label'   => 'Ítem',
                'format'  => 'raw',
                'content' => fn($m) => $m->status
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {deactivate}',
                'buttons'  => [
                    'view' => fn($url, $m) => Html::a('<i class="bi bi-eye"></i><span>Ver</span>', ['view', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']),
                    'update' => fn($url, $m) => Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']),
                    'deactivate' => fn($url, $m) => $m->status
                        ? Html::a('<i class="bi bi-x-circle"></i><span>Desactivar</span>', ['deactivate', 'id' => $m->id], [
                            'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Desactivar este ítem?',
                        ])
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>
