<?php
/**
 * Vista para reportes de satisfacción y NPS
 * @var yii\web\View $this
 * @var array $estadisticas
 * @var yii\data\ActiveDataProvider $seguimientosProvider
 * @var int $inicio
 * @var int $fin
 */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

$this->title = 'Reportes de Satisfacción y NPS';
$this->params['breadcrumbs'][] = ['label' => 'Agenda de Seguimientos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="seguimiento-reportes">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-chart-line"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?= Html::a(
                '<i class="fas fa-arrow-left"></i> Volver a Agenda',
                ['index'],
                ['class' => 'btn btn-secondary']
            ) ?>
            <?= Html::a(
                '<i class="fas fa-clock"></i> Ver Pendientes',
                ['pendientes'],
                ['class' => 'btn btn-info']
            ) ?>
        </div>
    </div>

    <!-- Filtro de fechas -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="<?= Url::to(['reportes']) ?>" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Desde:</label>
                    <input type="date" name="inicio" class="form-control" value="<?= date('Y-m-d', $inicio) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Hasta:</label>
                    <input type="date" name="fin" class="form-control" value="<?= date('Y-m-d', $fin) ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total Seguimientos</h5>
                    <h2><?= $estadisticas['total_seguimientos'] ?></h2>
                    <small>En el período seleccionado</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Satisfacción Promedio</h5>
                    <h2><?= number_format($estadisticas['satisfaccion_promedio'], 1) ?>/5</h2>
                    <small>Estrellas promedio</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">NPS Promedio</h5>
                    <h2><?= number_format($estadisticas['nps_promedio'], 1) ?>/10</h2>
                    <small>Net Promoter Score</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Tasa de Recomendación</h5>
                    <h2><?= number_format($estadisticas['tasa_recomendacion'], 1) ?>%</h2>
                    <small><?= $estadisticas['recomendados'] ?> de <?= $estadisticas['total_seguimientos'] ?> clientes</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico simple de distribución NPS -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar"></i> Distribución NPS</h5>
        </div>
        <div class="card-body">
            <?php 
            $total = $estadisticas['recomendados'] + $estadisticas['no_recomendados'];
            $promotores = $total > 0 ? round(($estadisticas['recomendados'] / $total) * 100, 1) : 0;
            $detractores = $total > 0 ? round(($estadisticas['no_recomendados'] / $total) * 100, 1) : 0;
            ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $promotores ?>%">
                            Promotores (<?= $promotores ?>%)
                        </div>
                    </div>
                    <small class="text-muted">Clientes que nos recomendarían</small>
                </div>
                <div class="col-md-6">
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $detractores ?>%">
                            Detractores (<?= $detractores ?>%)
                        </div>
                    </div>
                    <small class="text-muted">Clientes que no nos recomendarían</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de seguimientos completados -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-list"></i> Seguimientos Completados en el Período</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $seguimientosProvider,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    
                    [
                        'attribute' => 'fecha_realizacion',
                        'value' => fn($model) => date('d/m/Y H:i', $model->fecha_realizacion),
                        'format' => 'raw',
                    ],
                    
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
                        'value' => fn($model) => $model->tipoLabel,
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'satisfaccion',
                        'label' => 'Satisfacción',
                        'value' => function($model) {
                            if ($model->satisfaccion === null) return '-';
                            $stars = str_repeat('<i class="fas fa-star text-warning"></i>', $model->satisfaccion);
                            $stars .= str_repeat('<i class="fas fa-star text-muted"></i>', 5 - $model->satisfaccion);
                            return $stars;
                        },
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'recomendariamos',
                        'label' => 'Recomendaría',
                        'value' => function($model) {
                            if ($model->recomendariamos === null) return '-';
                            return $model->recomendariamos 
                                ? '<span class="badge badge-success">Sí</span>' 
                                : '<span class="badge badge-danger">No</span>';
                        },
                        'format' => 'raw',
                    ],
                    
                    [
                        'attribute' => 'resultado',
                        'value' => fn($model) => substr($model->resultado ?? '', 0, 50) . (strlen($model->resultado ?? '') > 50 ? '...' : ''),
                        'format' => 'raw',
                    ],
                ],
                'pager' => [
                    'options' => ['class' => 'pagination pagination-sm'],
                ],
            ]) ?>
        </div>
    </div>
</div>
