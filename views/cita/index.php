<?php
/** @var yii\web\View $this */
/** @var app\models\search\CitaSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var int $citasActivas */
/** @var string $fechaActiva */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Cita;
use app\models\Cliente;

$this->title = 'Citas';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="cita-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-check me-2"></i><?= Html::encode($this->title) ?>
            <span class="badge bg-primary ms-2"><?= $citasActivas ?> activas hoy</span>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-calendar3 me-1"></i>Calendario', ['calendario'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Cita', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <!-- Filtros -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_desde')->input('date')->label('Desde') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'fecha_hasta')->input('date')->label('Hasta') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'estado')->dropDownList(
                        ['' => '— Todos —'] + Cita::getEstadosList(),
                        ['prompt' => false]
                    )->label('Estado') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'buscar')->textInput(['placeholder' => 'Cliente, vehículo...', 'id' => 'cita-buscar'])->label('Buscar') ?>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <?= $form->field($searchModel, 'ver_canceladas')->checkbox(['label' => 'Ver canceladas/no-show']) ?>
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
                'label'  => 'Fecha',
                'format' => 'raw',
                'value'  => fn($m) => '<strong>' . date('d/m/Y', strtotime($m->fecha)) . '</strong><br><small class="text-muted">' . substr($m->hora_inicio, 0, 5) . ' – ' . substr($m->hora_fin, 0, 5) . '</small>',
            ],
            [
                'label'  => 'Cliente',
                'format' => 'raw',
                'value'  => fn($m) => $m->cliente
                    ? Html::a(Html::encode($m->cliente->nombre), ['/cliente/view', 'id' => $m->cliente_id])
                    : '—',
            ],
            [
                'label'  => 'Vehículo',
                'format' => 'raw',
                'value'  => fn($m) => $m->vehiculo
                    ? '<span class="badge bg-dark">' . Html::encode($m->vehiculo->patente) . '</span> ' . Html::encode($m->vehiculo->marca . ' ' . $m->vehiculo->modelo)
                    : '—',
            ],
            [
                'label'  => 'Estado',
                'format' => 'raw',
                'value'  => fn($m) => '<span class="badge ' . $m->getEstadoBadgeClass() . '">' . Html::encode($m->getEstadoLabel()) . '</span>',
            ],
            [
                'label'  => 'Servicios',
                'format' => 'raw',
                'value'  => fn($m) => '<span class="badge bg-secondary">' . count($m->servicios) . '</span>',
            ],
            [
                'label'  => 'Tiempo Aprox.',
                'format' => 'raw',
                'value'  => fn($m) => !empty($m->servicios)
                    ? '<span class="badge bg-info text-dark"><i class="bi bi-clock me-1"></i>' . Html::encode($m->getTiempoAproximadoFormateado()) . '</span>'
                    : '<span class="text-muted">—</span>',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
                'buttons'  => [
                    'view'   => fn($url, $m) => Html::a('<i class="bi bi-eye"></i><span>Ver</span>',    ['view',   'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn',   'title' => 'Ver']),
                    'update' => fn($url, $m) => $m->estado === 'pendiente'
                        ? Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar'])
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>

<?php
$js = <<<JS
(function() {
    const form = document.getElementById('search-form');
    const input = document.getElementById('cita-buscar');
    if (!form || !input) {
        return;
    }

    let timer = null;
    input.addEventListener('input', function() {
        if (timer) {
            clearTimeout(timer);
        }
        timer = setTimeout(function() {
            form.submit();
        }, 300);
    });
})();
JS;
$this->registerJs($js);
?>
