<?php
/**
 * Vista de Asignación de Mecánicos por Turno - HU-017
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Asignación de Mecánicos por Turno';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asignacion-turno-index">
    <h1><?= Html::encode($this->title) ?></h1>

    <!-- Filtros -->
    <?php $form = ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'form-inline mb-4'],
    ]); ?>
    
    <div class="form-group mr-3">
        <label for="fecha" class="control-label">Fecha:</label>
        <input type="date" name="fecha" id="fecha" class="form-control" value="<?= $fecha ?>">
    </div>

    <div class="form-group mr-3">
        <label for="turno" class="control-label">Turno:</label>
        <select name="turno" id="turno" class="form-control">
            <option value="manana" <?= $turno === 'manana' ? 'selected' : '' ?>>Mañana (8:00 - 13:00)</option>
            <option value="tarde" <?= $turno === 'tarde' ? 'selected' : '' ?>>Tarde (14:00 - 19:00)</option>
        </select>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Filtrar</button>
        <?= Html::a('Dashboard', ['dashboard'], ['class' => 'btn btn-info ml-2']) ?>
    </div>

    <?php ActiveForm::end(); ?>

    <!-- Panel de Técnicos Disponibles -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Técnicos Disponibles</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= Html::encode($tecnico->getFullName()) ?></strong><br>
                                    <small class="text-muted">
                                        <?= $tecnico->especialidad ? Html::encode($tecnico->especialidad->nombre) : 'General' ?>
                                    </small>
                                </div>
                                <span class="badge badge-success">Disponible</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Citas del Día - Turno <?= ucfirst($turno) ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($citasPorHora)): ?>
                        <p class="text-muted text-center">No hay citas programadas para este turno.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($horasTurno as $hora): ?>
                                <div class="hour-slot mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="hour-badge mr-3">
                                            <span class="badge badge-secondary"><?= $hora ?></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <?php if (isset($citasPorHora[$hora])): ?>
                                                <?php foreach ($citasPorHora[$hora] as $cita): ?>
                                                    <div class="card mb-2">
                                                        <div class="card-body py-2 px-3">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <strong><?= Html::encode($cita->cliente->getFullName()) ?></strong>
                                                                    <small class="text-muted d-block">
                                                                        <?= Html::encode($cita->vehiculo->marca_modelo ?? 'Vehículo') ?>
                                                                    </small>
                                                                </div>
                                                                <div>
                                                                    <span class="badge badge-<?= $cita->getEstadoBadgeClass() ?>">
                                                                        <?= $cita->getEstadoLabel() ?>
                                                                    </span>
                                                                    <button class="btn btn-sm btn-outline-primary ml-2 btn-asignar" 
                                                                            data-cita-id="<?= $cita->id ?>"
                                                                            data-hora="<?= $hora ?>">
                                                                        <i class="fas fa-user-plus"></i> Asignar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">Sin citas en este horario</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación -->
    <div class="modal fade" id="modalAsignacion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Asignar Técnico</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="form-asignar">
                        <input type="hidden" id="cita_id" name="cita_id">
                        <input type="hidden" id="fecha_asignar" name="fecha" value="<?= $fecha ?>">
                        <input type="hidden" id="hora_asignar" name="hora">
                        
                        <div class="form-group">
                            <label for="tecnico_select">Seleccionar Técnico:</label>
                            <select id="tecnico_select" name="tecnico_id" class="form-control" required>
                                <option value="">-- Seleccione --</option>
                                <?php foreach ($tecnicos as $tecnico): ?>
                                    <option value="<?= $tecnico->id ?>">
                                        <?= Html::encode($tecnico->getFullName()) ?> - 
                                        <?= $tecnico->especialidad ? Html::encode($tecnico->especialidad->nombre) : 'General' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btn-confirmar-asignar">Asignar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
$(document).ready(function() {
    // Abrir modal de asignación
    $('.btn-asignar').click(function() {
        var citaId = $(this).data('cita-id');
        var hora = $(this).data('hora');
        
        $('#cita_id').val(citaId);
        $('#hora_asignar').val(hora);
        $('#modalAsignacion').modal('show');
    });

    // Confirmar asignación
    $('#btn-confirmar-asignar').click(function() {
        var tecnicoId = $('#tecnico_select').val();
        var citaId = $('#cita_id').val();
        
        if (!tecnicoId) {
            alert('Por favor seleccione un técnico');
            return;
        }

        $.post('asignar', {
            tecnico_id: tecnicoId,
            cita_id: citaId,
            fecha: $('#fecha_asignar').val(),
            hora: $('#hora_asignar').val()
        }, function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        }, 'json');
        
        $('#modalAsignacion').modal('hide');
    });
});
JS;

$this->registerJs($js);
?>

<style>
.hour-badge {
    min-width: 80px;
}
.timeline {
    border-left: 2px solid #dee2e6;
    padding-left: 20px;
}
.hour-slot {
    position: relative;
}
.hour-slot:before {
    content: '';
    position: absolute;
    left: -26px;
    top: 15px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6c757d;
}
</style>
