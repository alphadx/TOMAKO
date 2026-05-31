<?php
/**
 * Dashboard de Asignación - HU-017
 */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Dashboard de Carga de Trabajo';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="asignacion-turno-dashboard">
    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = \yii\widgets\ActiveForm::begin([
        'method' => 'get',
        'options' => ['class' => 'form-inline mb-4'],
    ]); ?>
    
    <div class="form-group mr-3">
        <label for="fecha_inicio" class="control-label">Desde:</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= $fechaInicio ?>">
    </div>

    <div class="form-group mr-3">
        <label for="fecha_fin" class="control-label">Hasta:</label>
        <input type="date" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= $fechaFin ?>">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>

    <?php ActiveForm::end(); ?>

    <div class="row">
        <?php foreach ($estadisticas as $estadistica): ?>
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?= Html::encode($estadistica['tecnico']->getFullName()) ?></h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            <?= $estadistica['tecnico']->especialidad ? Html::encode($estadistica['tecnico']->especialidad->nombre) : 'General' ?>
                        </p>
                        <div class="d-flex justify-content-between">
                            <span>Citas:</span>
                            <strong><?= $estadistica['citas_count'] ?></strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Horas Totales:</span>
                            <strong><?= $estadistica['horas_totales'] ?>h</strong>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
