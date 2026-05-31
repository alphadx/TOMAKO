<?php
/**
 * Vista para mostrar el detalle de un seguimiento
 * @var yii\web\View $this
 * @var app\models\Seguimiento $model
 */

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Seguimiento;

$this->title = 'Seguimiento #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Agenda de Seguimientos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="seguimiento-view">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-calendar-check"></i> <?= Html::encode($this->title) ?></h1>
        <div>
            <?php if ($model->isPendiente()): ?>
                <?= Html::a(
                    '<i class="fas fa-edit"></i> Completar/Editar',
                    ['update', 'id' => $model->id],
                    ['class' => 'btn btn-primary']
                ) ?>
            <?php endif; ?>
            <?= Html::a(
                '<i class="fas fa-arrow-left"></i> Volver',
                ['index'],
                ['class' => 'btn btn-secondary']
            ) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Información del Seguimiento</h5>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'options' => ['class' => 'table table-bordered'],
                        'attributes' => [
                            'id',
                            [
                                'attribute' => 'orden_servicio_id',
                                'value' => $model->ordenServicio?->codigo ?? '-',
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'cliente_id',
                                'value' => $model->cliente?->nombre_completo ?? '-',
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'tipo',
                                'value' => $model->tipoLabel,
                            ],
                            [
                                'attribute' => 'estado',
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
                                'value' => $model->fecha_programada ? date('d/m/Y H:i', $model->fecha_programada) : '-',
                            ],
                            [
                                'attribute' => 'fecha_realizacion',
                                'value' => $model->fecha_realizacion ? date('d/m/Y H:i', $model->fecha_realizacion) : '-',
                            ],
                            [
                                'attribute' => 'realizado_por',
                                'value' => $model->realizadoPor?->nombre_completo ?? '-',
                            ],
                            [
                                'attribute' => 'resultado',
                                'format' => 'ntext',
                            ],
                            [
                                'attribute' => 'satisfaccion',
                                'value' => function($model) {
                                    if ($model->satisfaccion === null) return '-';
                                    return str_repeat('⭐', $model->satisfaccion) . 
                                           str_repeat('☆', 5 - $model->satisfaccion);
                                },
                                'format' => 'raw',
                            ],
                            [
                                'attribute' => 'nps_score',
                                'value' => $model->nps_score !== null ? number_format($model->nps_score, 1) . '/10' : '-',
                            ],
                            [
                                'attribute' => 'recomendariamos',
                                'value' => function($model) {
                                    if ($model->recomendariamos === null) return '-';
                                    return $model->recomendariamos ? 'Sí' : 'No';
                                },
                            ],
                            [
                                'attribute' => 'observaciones',
                                'format' => 'ntext',
                            ],
                            [
                                'attribute' => 'created_at',
                                'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '-',
                            ],
                            [
                                'attribute' => 'updated_at',
                                'value' => $model->updated_at ? date('d/m/Y H:i', $model->updated_at) : '-',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Información de la orden -->
            <?php if ($model->ordenServicio): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Orden de Servicio</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Código:</strong> <?= Html::encode($model->ordenServicio->codigo) ?></p>
                        <p><strong>Estado:</strong> <span class="badge badge-<?= $model->ordenServicio->estado ?>"><?= $model->ordenServicio->estadoLabel ?></span></p>
                        <p><strong>Vehículo:</strong> <?= Html::encode($model->ordenServicio->vehiculo?->fullDescription ?? '-') ?></p>
                        <?= Html::a(
                            'Ver Orden',
                            ['/orden-servicio/view', 'id' => $model->ordenServicio->id],
                            ['class' => 'btn btn-sm btn-info']
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Información del cliente -->
            <?php if ($model->cliente): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5><i class="fas fa-user"></i> Cliente</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?= Html::encode($model->cliente->nombre_completo) ?></p>
                        <?php if ($model->cliente->telefono): ?>
                            <p><strong>Teléfono:</strong> <?= Html::encode($model->cliente->telefono) ?></p>
                        <?php endif; ?>
                        <?php if ($model->cliente->email): ?>
                            <p><strong>Email:</strong> <?= Html::encode($model->cliente->email) ?></p>
                        <?php endif; ?>
                        <?= Html::a(
                            'Ver Cliente',
                            ['/cliente/view', 'id' => $model->cliente->id],
                            ['class' => 'btn btn-sm btn-info']
                        ) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Acciones rápidas -->
            <?php if ($model->isPendiente()): ?>
                <div class="card bg-light">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Completar con resultado:</strong></p>
                        <?= Html::beginForm(['completar', 'id' => $model->id], 'post') ?>
                            <div class="form-group">
                                <select name="resultado" class="form-control form-control-sm" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="Cliente satisfecho">✅ Cliente satisfecho</option>
                                    <option value="Cliente con observaciones">⚠️ Cliente con observaciones</option>
                                    <option value="No se pudo contactar">❌ No se pudo contactar</option>
                                    <option value="Cliente requiere seguimiento adicional">🔄 Requiere seguimiento adicional</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Satisfacción (1-5):</label>
                                <select name="satisfaccion" class="form-control form-control-sm">
                                    <option value="">-</option>
                                    <option value="1">1 ⭐</option>
                                    <option value="2">2 ⭐⭐</option>
                                    <option value="3">3 ⭐⭐⭐</option>
                                    <option value="4">4 ⭐⭐⭐⭐</option>
                                    <option value="5">5 ⭐⭐⭐⭐⭐</option>
                                </select>
                            </div>
                            <div class="form-group form-check">
                                <input type="checkbox" name="recomendariamos" class="form-check-input" value="1" id="recomendariamos">
                                <label class="form-check-label" for="recomendariamos">¿Nos recomendaría?</label>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm btn-block">
                                <i class="fas fa-check"></i> Completar
                            </button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
