<?php
/**
 * Vista índice para Seguimientos - Agenda de seguimientos post-servicio
 * @var yii\web\View $this
 * @var app\models\search\SeguimientoSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 */

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Seguimiento;

$this->title = 'Agenda de Seguimientos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="seguimiento-index">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-calendar-check"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a(
                '<i class="fas fa-clock"></i> Pendientes Hoy',
                ['pendientes'],
                ['class' => 'btn btn-info']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-chart-line"></i> Reportes',
                ['reportes'],
                ['class' => 'btn btn-success']
            ) ?>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'filterModel' => $searchModel,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    
                    [
                        'attribute' => 'orden_servicio_id',
                        'label' => 'Orden',
                        'value' => fn($model) => $model->ordenServicio?->codigo ?? '-',
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'cliente_id',
                        'label' => 'Cliente',
                        'value' => fn($model) => $model->cliente?->nombre_completo ?? '-',
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'tipo',
                        'filter' => Seguimiento::TIPOS,
                        'value' => fn($model) => $model->tipoLabel,
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'estado',
                        'filter' => Seguimiento::ESTADOS,
                        'value' => function($model) {
                            $clases = [
                                'pendiente' => 'badge-warning',
                                'completado' => 'badge-success',
                                'omitido' => 'badge-secondary',
                                'fallido' => 'badge-danger',
                            ];
                            $clase = $clases[$model->estado] ?? 'badge-secondary';
                            return "<span class='badge {$clase}'>{$model->estadoLabel}</span>";
                        },
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'fecha_programada',
                        'value' => fn($model) => date('d/m/Y H:i', $model->fecha_programada),
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'satisfaccion',
                        'value' => function($model) {
                            if ($model->satisfaccion === null) return '-';
                            $stars = str_repeat('<i class="fas fa-star text-warning"></i>', $model->satisfaccion);
                            $stars .= str_repeat('<i class="fas fa-star text-muted"></i>', 5 - $model->satisfaccion);
                            return $stars;
                        },
                        'format' => 'raw',
                        'filter' => false,
                    ],
                    
                    [
                        'header' => 'Acciones',
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{view} {update}',
                        'buttons' => [
                            'view' => fn($url, $model) => Html::a(
                                '<i class="fas fa-eye"></i>',
                                $url,
                                ['class' => 'btn btn-sm btn-info', 'title' => 'Ver detalle']
                            ),
                            'update' => fn($url, $model) => $model->isPendiente() ? Html::a(
                                '<i class="fas fa-edit"></i>',
                                $url,
                                ['class' => 'btn btn-sm btn-primary', 'title' => 'Completar/Editar']
                            ) : '',
                        ],
                        'visibleButtons' => [
                            'delete' => false,
                        ],
                    ],
                ],
                'pager' => [
                    'options' => ['class' => 'pagination pagination-sm'],
                ],
            ]) ?>
        </div>
    </div>
</div>

<style>
.seguimiento-index .badge {
    min-width: 80px;
}
</style>
