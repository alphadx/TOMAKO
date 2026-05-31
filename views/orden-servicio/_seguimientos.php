<?php
/**
 * Vista parcial para mostrar y gestionar seguimientos de una orden
 * @var app\models\OrdenServicio $orden
 * @var yii\data\ActiveDataProvider $seguimientosProvider
 */

use yii\helpers\Html;
use yii\widgets\ListView;
use app\models\Seguimiento;

?>

<div class="seguimientos-section">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4><i class="fas fa-phone-alt"></i> Seguimientos Post-Servicio</h4>
        
        <?php if ($orden->estado === 'entregada'): ?>
            <?php
            $seguimientoPendiente = Seguimiento::find()
                ->where(['orden_servicio_id' => $orden->id, 'estado' => 'pendiente'])
                ->one();
            ?>
            <?php if (!$seguimientoPendiente): ?>
                <?= Html::a(
                    '<i class="fas fa-calendar-plus"></i> Programar Seguimiento',
                    ['orden-servicio/programar-seguimiento', 'id' => $orden->id],
                    [
                        'class' => 'btn btn-success btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => '¿Programar un seguimiento para esta orden?',
                    ]
                ) ?>
            <?php else: ?>
                <span class="badge badge-info">
                    <i class="fas fa-clock"></i> Seguimiento pendiente para el 
                    <?= date('d/m/Y', $seguimientoPendiente->fecha_programada) ?>
                </span>
            <?php endif; ?>
        <?php else: ?>
            <small class="text-muted">El seguimiento se programa cuando la orden está entregada</small>
        <?php endif; ?>
    </div>

    <?php
    $seguimientos = $seguimientosProvider->getModels();
    ?>

    <?php if (empty($seguimientos)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay seguimientos registrados para esta orden.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Fecha Programada</th>
                        <th>Fecha Realización</th>
                        <th>Satisfacción</th>
                        <th>Realizado Por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seguimientos as $seguimiento): ?>
                        <tr class="<?= $seguimiento->isPendiente() ? 'table-warning' : '' ?>">
                            <td>
                                <span class="badge badge-secondary">
                                    <?= Html::encode($seguimiento->tipoLabel) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $estadoClass = match($seguimiento->estado) {
                                    'pendiente' => 'badge-warning',
                                    'completado' => 'badge-success',
                                    'omitido' => 'badge-secondary',
                                    'fallido' => 'badge-danger',
                                    default => 'badge-secondary',
                                };
                                ?>
                                <span class="badge <?= $estadoClass ?>">
                                    <?= Html::encode($seguimiento->estadoLabel) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', $seguimiento->fecha_programada) ?></td>
                            <td>
                                <?= $seguimiento->fecha_realizacion 
                                    ? date('d/m/Y H:i', $seguimiento->fecha_realizacion) 
                                    : '-' 
                                ?>
                            </td>
                            <td>
                                <?php if ($seguimiento->satisfaccion !== null): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?= $i <= $seguimiento->satisfaccion ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">(NPS: <?= number_format($seguimiento->nps_score ?? 0, 1) ?>)</small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $seguimiento->realizadoPor 
                                    ? Html::encode($seguimiento->realizadoPor->nombre_completo) 
                                    : '<em class="text-muted">No asignado</em>' 
                                ?>
                            </td>
                            <td>
                                <?php if ($seguimiento->isPendiente()): ?>
                                    <?= Html::a(
                                        '<i class="fas fa-check"></i>',
                                        ['seguimiento/update', 'id' => $seguimiento->id],
                                        [
                                            'class' => 'btn btn-sm btn-success',
                                            'title' => 'Completar seguimiento',
                                        ]
                                    ) ?>
                                <?php endif; ?>
                                
                                <?= Html::a(
                                    '<i class="fas fa-eye"></i>',
                                    ['seguimiento/view', 'id' => $seguimiento->id],
                                    [
                                        'class' => 'btn btn-sm btn-info',
                                        'title' => 'Ver detalle',
                                    ]
                                ) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= \yii\widgets\LinkPager::widget([
            'pagination' => $seguimientosProvider->getPagination(),
            'options' => ['class' => 'pagination pagination-sm'],
        ]) ?>
    <?php endif; ?>
</div>

<style>
.rating-stars i {
    font-size: 0.9rem;
}
.seguimientos-section {
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}
</style>
