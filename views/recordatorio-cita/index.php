<?php
/**
 * Vista de Recordatorios Automáticos de Citas - HU-019
 * @var yii\data\ArrayDataProvider $citasMananaProvider
 * @var yii\data\ArrayDataProvider $citasHoyProvider
 * @var int $totalRecordatoriosEnviados
 * @var int $totalPendientes
 * @var bool $plantillaExiste
 */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Recordatorios Automáticos de Citas';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="recordatorio-cita-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- KPIs -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Citas para Hoy</h5>
                    <h3 class="mb-0"><?= $citasHoyProvider->getTotalCount() ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Recordatorios Pendientes</h5>
                    <h3 class="mb-0"><?= $totalPendientes ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Enviados Hoy</h5>
                    <h3 class="mb-0"><?= $totalRecordatoriosEnviados ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Plantilla Activa</h5>
                    <h3 class="mb-0">
                        <?= $plantillaExiste ? '✅ Sí' : '❌ No' ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones -->
    <div class="row mb-4">
        <div class="col-md-12">
            <?= Html::button(
                '<i class="fas fa-envelope"></i> Enviar Recordatorios (24h)',
                [
                    'class' => 'btn btn-primary btn-lg',
                    'id' => 'btn-enviar-recordatorios',
                ]
            ) ?>
            <?= Html::a(
                '<i class="fas fa-cog"></i> Configurar Plantillas',
                ['configuracion'],
                ['class' => 'btn btn-secondary btn-lg ml-2']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-history"></i> Historial',
                ['historial'],
                ['class' => 'btn btn-info btn-lg ml-2']
            ) ?>
        </div>
    </div>

    <!-- Citas con recordatorio pendiente (mañana) -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i> 
                Recordatorios Pendientes - Citas para Mañana (<?= date('d/m/Y', strtotime('+1 day')) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $citasMananaProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => '{items}',
                'emptyText' => 'No hay citas confirmadas para mañana que requieran recordatorio.',
                'columns' => [
                    [
                        'label' => 'Hora',
                        'attribute' => 'hora_inicio',
                        'contentOptions' => ['class' => 'fw-bold'],
                    ],
                    [
                        'label' => 'Cliente',
                        'value' => fn($model) => Html::encode($model->cliente->getFullName()),
                    ],
                    [
                        'label' => 'Vehículo',
                        'value' => fn($model) => Html::encode($model->vehiculo->marca_modelo ?? 'N/A'),
                    ],
                    [
                        'label' => 'Email',
                        'value' => fn($model) => Html::encode($model->cliente->email ?? 'Sin email'),
                    ],
                    [
                        'label' => 'Estado',
                        'format' => 'raw',
                        'value' => fn() => '<span class="badge bg-warning text-dark">Pendiente</span>',
                    ],
                ],
            ]); ?>
        </div>
    </div>

    <!-- Citas de hoy -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">
                <i class="fas fa-calendar-check"></i> 
                Citas Confirmadas - Hoy (<?= date('d/m/Y') ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <?= GridView::widget([
                'dataProvider' => $citasHoyProvider,
                'tableOptions' => ['class' => 'table table-hover mb-0'],
                'layout' => '{items}',
                'emptyText' => 'No hay citas confirmadas para hoy.',
                'columns' => [
                    [
                        'label' => 'Hora',
                        'attribute' => 'hora_inicio',
                        'contentOptions' => ['class' => 'fw-bold'],
                    ],
                    [
                        'label' => 'Cliente',
                        'value' => fn($model) => Html::encode($model->cliente->getFullName()),
                    ],
                    [
                        'label' => 'Vehículo',
                        'value' => fn($model) => Html::encode($model->vehiculo->marca_modelo ?? 'N/A'),
                    ],
                    [
                        'label' => 'Servicios',
                        'value' => fn($model) => $model->getTiempoAproximadoFormateado(),
                    ],
                    [
                        'label' => 'Estado',
                        'format' => 'raw',
                        'value' => fn($model) => '<span class="badge bg-' . $model->getEstadoBadgeClass() . '">'
                            . Html::encode($model->getEstadoLabel()) . '</span>',
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>

<?php
$js = <<<JS
$(document).ready(function() {
    $('#btn-enviar-recordatorios').click(function() {
        if (!confirm('¿Está seguro de enviar los recordatorios de citas para mañana?')) {
            return;
        }

        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enviando...');

        $.post('enviar-recordatorios', function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
                btn.prop('disabled', false);
            }
        }, 'json').fail(function() {
            alert('Error al conectar con el servidor.');
            btn.prop('disabled', false);
        });
    });
});
JS;

$this->registerJs($js);
?>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>