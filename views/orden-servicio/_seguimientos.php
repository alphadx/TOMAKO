<?php
/**
 * Vista parcial para mostrar y gestionar seguimientos de una orden
 * @var app\models\OrdenServicio $orden
 * @var yii\data\ActiveDataProvider $seguimientosProvider
 */

use yii\helpers\Html;
use yii\grid\GridView;
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

    <?php if ($seguimientosProvider->getTotalCount() === 0): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay seguimientos registrados para esta orden.
        </div>
    <?php else: ?>
        <?= GridView::widget([
            'dataProvider' => $seguimientosProvider,
            'tableOptions' => ['class' => 'table table-hover table-sm mb-0'],
            'layout' => '{items}{pager}',
            'pager' => [
                'class' => \yii\widgets\LinkPager::class,
                'options' => ['class' => 'pagination pagination-sm justify-content-center mt-3'],
            ],
            'rowOptions' => fn($model) => $model->isPendiente() ? ['class' => 'table-warning'] : [],
            'columns' => [
                [
                    'label' => 'Tipo',
                    'format' => 'raw',
                    'value' => fn($model) => '<span class="badge bg-secondary">' . Html::encode($model->tipoLabel) . '</span>',
                ],
                [
                    'label' => 'Estado',
                    'format' => 'raw',
                    'value' => function ($model) {
                        $estadoClass = match($model->estado) {
                            'pendiente' => 'bg-warning text-dark',
                            'completado' => 'bg-success',
                            'omitido' => 'bg-secondary',
                            'fallido' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        return '<span class="badge ' . $estadoClass . '">' . Html::encode($model->estadoLabel) . '</span>';
                    },
                ],
                [
                    'label' => 'Fecha Programada',
                    'value' => fn($model) => date('d/m/Y H:i', $model->fecha_programada),
                ],
                [
                    'label' => 'Fecha Realización',
                    'value' => fn($model) => $model->fecha_realizacion ? date('d/m/Y H:i', $model->fecha_realizacion) : '-',
                ],
                [
                    'label' => 'Satisfacción',
                    'format' => 'raw',
                    'value' => function ($model) {
                        if ($model->satisfaccion === null) {
                            return '<span class="text-muted">-</span>';
                        }
                        $stars = '';
                        for ($i = 1; $i <= 5; $i++) {
                            $stars .= '<i class="fas fa-star ' . ($i <= $model->satisfaccion ? 'text-warning' : 'text-muted') . '" style="font-size:0.9rem"></i>';
                        }
                        $nps = number_format((float)($model->nps_score ?? 0), 1);
                        return '<div class="rating-stars">' . $stars . '</div><small class="text-muted">(NPS: ' . $nps . ')</small>';
                    },
                ],
                [
                    'label' => 'Realizado Por',
                    'format' => 'raw',
                    'value' => fn($model) => $model->realizadoPor
                        ? Html::encode($model->realizadoPor->nombre_completo)
                        : '<em class="text-muted">No asignado</em>',
                ],
                [
                    'class' => 'yii\grid\ActionColumn',
                    'template' => '{complete} {view}',
                    'buttons' => [
                        'complete' => fn($url, $model) => $model->isPendiente()
                            ? Html::a(
                                '<i class="fas fa-check"></i>',
                                ['seguimiento/update', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-success', 'title' => 'Completar seguimiento']
                            )
                            : '',
                        'view' => fn($url, $model) => Html::a(
                            '<i class="fas fa-eye"></i>',
                            ['seguimiento/view', 'id' => $model->id],
                            ['class' => 'btn btn-sm btn-info', 'title' => 'Ver detalle']
                        ),
                    ],
                ],
            ],
        ]); ?>
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
