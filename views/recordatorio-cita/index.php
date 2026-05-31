<?php
/**
 * Vista de Recordatorios Automáticos de Citas - HU-019
 */

use yii\helpers\Html;

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
                    <h3 class="mb-0"><?= count($citasHoy) ?></h3>
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
        <div class="card-body">
            <?php if (empty($citasManana)): ?>
                <p class="text-muted text-center mb-0">
                    No hay citas confirmadas para mañana que requieran recordatorio.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Email</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citasManana as $cita): ?>
                                <tr>
                                    <td><strong><?= $cita->hora_inicio ?></strong></td>
                                    <td><?= Html::encode($cita->cliente->getFullName()) ?></td>
                                    <td><?= Html::encode($cita->vehiculo->marca_modelo ?? 'N/A') ?></td>
                                    <td><?= Html::encode($cita->cliente->email ?? 'Sin email') ?></td>
                                    <td>
                                        <span class="badge badge-warning">Pendiente</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
        <div class="card-body">
            <?php if (empty($citasHoy)): ?>
                <p class="text-muted text-center mb-0">
                    No hay citas confirmadas para hoy.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Cliente</th>
                                <th>Vehículo</th>
                                <th>Servicios</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($citasHoy as $cita): ?>
                                <tr>
                                    <td><strong><?= $cita->hora_inicio ?></strong></td>
                                    <td><?= Html::encode($cita->cliente->getFullName()) ?></td>
                                    <td><?= Html::encode($cita->vehiculo->marca_modelo ?? 'N/A') ?></td>
                                    <td><?= $cita->getTiempoAproximadoFormateado() ?></td>
                                    <td>
                                        <span class="badge badge-<?= $cita->getEstadoBadgeClass() ?>">
                                            <?= $cita->getEstadoLabel() ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
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
