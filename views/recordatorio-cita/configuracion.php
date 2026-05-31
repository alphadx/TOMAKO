<?php
/**
 * Configuración de Recordatorios - HU-019
 */

use yii\helpers\Html;

$this->title = 'Configuración de Recordatorios';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="recordatorio-cita-config">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Configure las plantillas de email para los recordatorios automáticos de citas.
    </div>

    <div class="card">
        <div class="card-header">
            <h5>Plantillas Disponibles</h5>
        </div>
        <div class="card-body">
            <?php if (empty($plantillas)): ?>
                <p class="text-muted">No hay plantillas configuradas para recordatorios de citas.</p>
                <?= Html::a('Crear Nueva Plantilla', ['/notificacion/crear-plantilla'], ['class' => 'btn btn-primary']) ?>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Canal</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plantillas as $plantilla): ?>
                            <tr>
                                <td><code><?= Html::encode($plantilla->codigo) ?></code></td>
                                <td><?= Html::encode($plantilla->nombre) ?></td>
                                <td><?= Html::encode($plantilla->canal) ?></td>
                                <td>
                                    <?php if ($plantilla->activo): ?>
                                        <span class="badge badge-success">Activa</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactiva</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= Html::a('Editar', ['/notificacion/editar-plantilla', 'id' => $plantilla->id], ['class' => 'btn btn-sm btn-outline-primary']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
