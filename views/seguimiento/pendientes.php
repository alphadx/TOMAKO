<?php
/**
 * Vista para mostrar los seguimientos pendientes del día
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var int $fecha
 */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Seguimientos Pendientes de Hoy';
$this->params['breadcrumbs'][] = ['label' => 'Agenda de Seguimientos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="seguimiento-pendientes">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-calendar-day"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a(
                '<i class="fas fa-arrow-left"></i> Volver a Agenda',
                ['index'],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-chart-line"></i> Ver Reportes',
                ['reportes'],
                ['class' => 'btn btn-success']
            ) ?>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> 
        Mostrando seguimientos programados para <strong><?= date('d/m/Y', $fecha) ?></strong>.
        Total: <strong><?= $dataProvider->totalCount ?></strong> seguimiento(s) pendiente(s).
    </div>

    <?php if ($dataProvider->totalCount > 0): ?>
        <div class="card">
            <div class="card-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
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
                            'value' => function($model) {
                                $cliente = $model->cliente;
                                if (!$cliente) return '-';
                                return $cliente->nombre_completo . '<br>' . 
                                       '<small class="text-muted">' . ($cliente->telefono ?: $cliente->email ?: '') . '</small>';
                            },
                            'format' => 'raw',
                        ],
                        
                        [
                            'attribute' => 'tipo',
                            'value' => fn($model) => $model->tipoLabel,
                            'format' => 'raw',
                        ],
                        
                        [
                            'attribute' => 'fecha_programada',
                            'value' => fn($model) => date('H:i', $model->fecha_programada),
                            'format' => 'raw',
                        ],
                        
                        [
                            'header' => 'Acciones',
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{completar}',
                            'buttons' => [
                                'completar' => fn($url, $model) => Html::a(
                                    '<i class="fas fa-check-circle"></i> Completar',
                                    ['update', 'id' => $model->id],
                                    ['class' => 'btn btn-sm btn-success', 'title' => 'Completar seguimiento']
                                ),
                            ],
                            'visibleButtons' => [
                                'view' => false,
                                'update' => false,
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
    <?php else: ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            ¡Excelente! No hay seguimientos pendientes para hoy.
        </div>
    <?php endif; ?>
</div>
