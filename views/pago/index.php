<?php
/** @var yii\web\View $this */
/** @var app\models\search\PagoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array{total_cobrado: float, pagos_hoy: int, pagos_pendientes: int, pagos_anulados: int} $kpis */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Pago;

$this->title = 'Pagos';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="pago-index">

    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-cash-coin me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-graph-up me-1"></i>Reportes', ['reporte-ingresos'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-safe2 me-1"></i>Cierre de Caja', ['cierre-caja'], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-plus-circle me-1"></i>Registrar Pago', ['create'], ['class' => 'btn btn-primary']) ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card text-bg-success shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold"><?= \app\components\helpers\FormatHelper::moneda($kpis['total_cobrado']) ?></div>
                    <small>Total Cobrado</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-primary shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold"><?= $kpis['pagos_hoy'] ?></div>
                    <small>Pagos Hoy</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-warning shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold"><?= $kpis['pagos_pendientes'] ?></div>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card text-bg-secondary shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold"><?= $kpis['pagos_anulados'] ?></div>
                    <small>Anulados</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <?php $form = ActiveForm::begin(['method' => 'get', 'options' => ['class' => 'row g-2 align-items-end']]); ?>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'buscar')->textInput(['placeholder' => 'Buscar...'])->label('Buscar') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'estado')->dropDownList(
                        ['' => 'Todos'] + Pago::ESTADOS, ['class' => 'form-select']
                    )->label('Estado') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'metodo_pago')->dropDownList(
                        ['' => 'Todos'] + Pago::METODOS, ['class' => 'form-select']
                    )->label('Método') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_desde')->input('date')->label('Desde') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_hasta')->input('date')->label('Hasta') ?>
                </div>
                <div class="col-md-1 d-flex gap-1 ts-filter-actions">
                    <?= Html::submitButton('<i class="bi bi-search"></i><span>Buscar</span>', ['class' => 'btn btn-outline-primary ts-action-btn', 'title' => 'Filtrar']) ?>
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Grid -->
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns'      => [
            'id',
            [
                'label'  => 'Orden',
                'format' => 'raw',
                'value'  => fn($m) => $m->orden
                    ? Html::a(Html::encode($m->orden->codigo), ['/orden/view', 'id' => $m->orden_id])
                    : '—',
            ],
            [
                'label' => 'Cliente',
                'value' => fn($m) => $m->orden && $m->orden->cliente
                    ? $m->orden->cliente->nombre
                    : '—',
            ],
            [
                'label' => 'Monto',
                'value' => fn($m) => \app\components\helpers\FormatHelper::moneda($m->monto),
            ],
            [
                'label' => 'Método',
                'value' => fn($m) => $m->getMetodoPagoLabel(),
            ],
            [
                'label'  => 'Estado',
                'format' => 'raw',
                'value'  => fn($m) => '<span class="badge ' . $m->getEstadoBadgeClass() . '">' . Html::encode($m->getEstadoLabel()) . '</span>',
            ],
            [
                'label' => 'Fecha Pago',
                'value' => fn($m) => $m->pagado_at ? date('d/m/Y H:i', $m->pagado_at) : '—',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view}',
                'buttons'  => [
                    'view' => fn($url, $m) => Html::a('<i class="bi bi-eye"></i><span>Ver</span>', ['view', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']),
                ],
            ],
        ],
    ]); ?>
</div>
