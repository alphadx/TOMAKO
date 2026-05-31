<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use kartik\date\DatePicker;

/**
 * @var yii\web\View $this
 * @var app\models\search\EvaluacionProveedorSearch $searchModel
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $rankingMensual
 * @var int $mesActual
 * @var int $anioActual
 */

$this->title = 'Reporte de Evaluaciones por Período';
$this->params['breadcrumbs'][] = ['label' => 'Evaluaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
?>

<div class="evaluacion-proveedor-reporte">
    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Volver al Listado', ['index'], ['class' => 'btn btn-secondary']) ?>
    </p>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'options' => ['class' => 'row g-3 align-items-end'],
            ]); ?>
            
            <div class="col-md-3">
                <?= $form->field($searchModel, 'periodo_mes')->dropDownList($meses, ['prompt' => 'Todos los meses']) ?>
            </div>
            
            <div class="col-md-3">
                <?= $form->field($searchModel, 'periodo_anio')->textInput(['type' => 'number', 'min' => 2020, 'max' => date('Y') + 5]) ?>
            </div>
            
            <div class="col-md-3">
                <?= $form->field($searchModel, 'fecha_evaluacion')->widget(DatePicker::classname(), [
                    'language' => 'es',
                    'dateFormat' => 'yyyy-MM-dd',
                    'options' => ['class' => 'form-control', 'placeholder' => 'Fecha específica'],
                    'pluginOptions' => ['todayHighlight' => true],
                ]) ?>
            </div>
            
            <div class="col-md-3">
                <?= Html::submitButton('Filtrar', ['class' => 'btn btn-primary w-100']) ?>
            </div>
            
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Ranking del mes actual -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">🏆 Ranking de Proveedores - <?= $meses[$mesActual] ?> <?= $anioActual ?></h5>
        </div>
        <div class="card-body">
            <?php if (empty($rankingMensual)): ?>
                <p class="text-muted mb-0">No hay evaluaciones registradas para este período.</p>
            <?php else: ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>Proveedor</th>
                            <th class="text-center">Cantidad Evaluaciones</th>
                            <th class="text-center">Puntaje Promedio</th>
                            <th class="text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rankingMensual as $index => $ranking): ?>
                            <tr class="<?= $index < 3 ? 'table-success' : '' ?>">
                                <td>
                                    <?php if ($index === 0): ?>
                                        🥇
                                    <?php elseif ($index === 1): ?>
                                        🥈
                                    <?php elseif ($index === 2): ?>
                                        🥉
                                    <?php else: ?>
                                        <?= $index + 1 ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= $ranking->proveedor->nombre ?? 'N/A' ?></strong></td>
                                <td class="text-center"><?= $ranking->cantidad_evaluaciones ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $ranking->puntaje_prom >= 4 ? 'success' : ($ranking->puntaje_prom >= 3 ? 'warning' : 'danger') ?> fs-6">
                                        <?= number_format($ranking->puntaje_prom, 2) ?>/5.0
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($ranking->puntaje_prom >= 4): ?>
                                        <span class="text-success">✓ Excelente</span>
                                    <?php elseif ($ranking->puntaje_prom >= 3): ?>
                                        <span class="text-warning">⚠ Regular</span>
                                    <?php else: ?>
                                        <span class="text-danger">✗ Deficiente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detalle de evaluaciones -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">📋 Detalle de Evaluaciones</h5>
        </div>
        <div class="card-body">
            <?= GridView::widget([
                'dataProvider' => $dataProvider,
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'attribute' => 'proveedor_id',
                        'label' => 'Proveedor',
                        'value' => function($model) {
                            return is_array($model) ? ($model['proveedor']['nombre'] ?? 'N/A') : ($model->proveedor->nombre ?? 'N/A');
                        },
                    ],
                    [
                        'attribute' => 'puntaje_prom',
                        'label' => 'Puntaje Promedio',
                        'format' => 'raw',
                        'value' => function($model) {
                            $puntaje = is_array($model) ? $model['puntaje_prom'] : $model->puntaje_prom;
                            return "<strong>" . number_format($puntaje, 2) . "/5.0</strong>";
                        },
                    ],
                    [
                        'attribute' => 'cantidad_evaluaciones',
                        'label' => 'Evaluaciones',
                        'format' => 'raw',
                        'value' => function($model) {
                            $cantidad = is_array($model) ? $model['cantidad_evaluaciones'] : $model->cantidad_evaluaciones;
                            return "<span class=\"badge bg-primary\">{$cantidad}</span>";
                        },
                    ],
                ],
            ]); ?>
        </div>
    </div>
</div>
