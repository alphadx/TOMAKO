<?php
/** @var yii\web\View $this */
/** @var app\models\Cliente $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;
use app\models\Vehiculo;
use app\models\Etiqueta;
use app\models\ClienteEtiqueta;

$this->title = $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Clientes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Obtener vehículos del cliente
$vehiculos = Vehiculo::find()
    ->where(['cliente_id' => $model->id])
    ->orderBy(['created_at' => SORT_DESC])
    ->all();

// Obtener etiquetas del cliente
$etiquetasDisponibles = Etiqueta::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
$etiquetasAsignadas = $model->getEtiquetas()->where(['etiqueta.status' => 1])->all();
$etiquetasIds = array_column($etiquetasAsignadas, 'id');
?>

<div class="cliente-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Datos del Cliente</strong>
                    <?= $model->status
                        ? '<span class="badge bg-success">Activo</span>'
                        : '<span class="badge bg-danger">Inactivo</span>' ?>
                </div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'attributes' => [
                            'id',
                            'nombre',
                            [
                                'label'  => 'Correo',
                                'format' => 'email',
                                'value'  => $model->email,
                            ],
                            'telefono',
                            'rut',
                            [
                                'attribute' => 'cumpleanos',
                                'value' => $model->cumpleanos 
                                    ? date('d/m/Y', strtotime($model->cumpleanos)) 
                                    : '—',
                            ],
                            [
                                'attribute' => 'fuente',
                                'value' => $model->fuente 
                                    ? Html::encode(ucfirst($model->fuente)) 
                                    : '—',
                            ],
                            'direccion:text',
                            [
                                'attribute' => 'preferencias',
                                'value' => $model->preferencias ?: '—',
                                'format' => 'ntext',
                            ],
                            'notas:text',
                            [
                                'label' => 'Creado',
                                'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—',
                            ],
                            [
                                'label' => 'Actualizado',
                                'value' => $model->updated_at ? date('d/m/Y H:i', $model->updated_at) : '—',
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Estadísticas del cliente -->
            <div class="card mb-4">
                <div class="card-header"><strong>Resumen</strong></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1 border-bottom">
                        <span class="text-muted">Vehículos</span>
                        <span class="badge bg-primary"><?= count($vehiculos) ?></span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Órdenes</span>
                        <span class="badge bg-secondary">-</span>
                    </div>
                    <div class="d-flex justify-content-between py-1">
                        <span class="text-muted">Etiquetas</span>
                        <span class="badge bg-info"><?= count($etiquetasAsignadas) ?></span>
                    </div>
                </div>
            </div>

            <!-- Sección de Etiquetas -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-tags me-2"></i>Etiquetas</strong>
                </div>
                <div class="card-body">
                    <?php if (count($etiquetasAsignadas) > 0): ?>
                        <div class="mb-3">
                            <?php foreach ($etiquetasAsignadas as $etiqueta): ?>
                                <?= Html::tag('span', $etiqueta->nombre, [
                                    'class' => 'badge bg-' . $etiqueta->color . ' me-1 mb-1',
                                    'style' => 'font-size: 0.85rem;'
                                ]) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small">Este cliente no tiene etiquetas asignadas.</p>
                    <?php endif; ?>

                    <hr>

                    <h6 class="small text-muted mb-2">Asignar/Eliminar Etiquetas</h6>
                    <div id="etiquetas-container">
                        <?php foreach ($etiquetasDisponibles as $etiqueta): ?>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input etiqueta-checkbox" 
                                       type="checkbox" 
                                       data-etiqueta-id="<?= $etiqueta->id ?>"
                                       data-cliente-id="<?= $model->id ?>"
                                       <?= in_array($etiqueta->id, $etiquetasIds) ? 'checked' : '' ?>
                                       id="etiqueta-<?= $etiqueta->id ?>">
                                <label class="form-check-label" for="etiqueta-<?= $etiqueta->id ?>">
                                    <span class="badge bg-<?= $etiqueta->color ?>"><?= Html::encode($etiqueta->nombre) ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($model->status): ?>
            <div class="card border-danger">
                <div class="card-body text-center">
                    <?= Html::a(
                        '<i class="bi bi-person-x me-1"></i>Desactivar Cliente',
                        ['deactivate', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-outline-danger btn-sm',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Desactivar este cliente?',
                        ]
                    ) ?>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Sección de Vehículos del Cliente (HU-009) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-car-front me-2"></i>Vehículos Asociados</strong>
                    <?= Html::a(
                        '<i class="bi bi-plus-circle me-1"></i>Agregar Vehículo',
                        ['/vehiculo/create', 'Vehiculo[cliente_id]' => $model->id],
                        ['class' => 'btn btn-primary btn-sm']
                    ) ?>
                    <?= Html::button(
                        '<i class="bi bi-lightning-fill me-1"></i>Alta Rápida',
                        [
                            'class' => 'btn btn-outline-success btn-sm',
                            'data-bs-toggle' => 'modal',
                            'data-bs-target' => '#vehiculo-quick-modal',
                            'title' => 'Registro rápido de vehículo'
                        ]
                    ) ?>
                </div>
                <div class="card-body">
                    <?php if (count($vehiculos) > 0): ?>
                        <?php
                        $vehiculosProvider = new ArrayDataProvider([
                            'allModels' => $vehiculos,
                            'pagination' => false,
                        ]);
                        ?>
                        <?= GridView::widget([
                            'dataProvider' => $vehiculosProvider,
                            'tableOptions' => ['class' => 'table table-hover mb-0'],
                            'layout' => '{items}',
                            'columns' => [
                                [
                                    'attribute' => 'patente',
                                    'value' => fn($model) => '<strong>' . Html::encode($model->patente) . '</strong>',
                                    'format' => 'raw',
                                ],
                                'marca',
                                'modelo',
                                'anio',
                                [
                                    'label' => 'KM Actual',
                                    'value' => fn($model) => number_format((float)($model->km_actual ?? 0), 0, ',', '.') . ' km',
                                ],
                                [
                                    'label' => 'Última Mantención',
                                    'format' => 'raw',
                                    'value' => fn($model) => $model->ultima_mantencion_at
                                        ? '<span class="badge bg-info">' . date('d/m/Y', $model->ultima_mantencion_at) . '</span>'
                                        : '<span class="badge bg-secondary">Sin registro</span>',
                                ],
                                [
                                    'attribute' => 'status',
                                    'format' => 'raw',
                                    'value' => fn($model) => $model->status
                                        ? '<span class="badge bg-success">Activo</span>'
                                        : '<span class="badge bg-danger">Inactivo</span>',
                                ],
                                [
                                    'class' => 'yii\grid\ActionColumn',
                                    'template' => '{view} {update}',
                                    'buttons' => [
                                        'view' => fn($url, $model) => Html::a(
                                            '<i class="bi bi-eye"></i>',
                                            ['/vehiculo/view', 'id' => $model->id],
                                            ['class' => 'btn btn-sm btn-outline-info', 'title' => 'Ver']
                                        ),
                                        'update' => fn($url, $model) => Html::a(
                                            '<i class="bi bi-pencil"></i>',
                                            ['/vehiculo/update', 'id' => $model->id],
                                            ['class' => 'btn btn-sm btn-outline-warning', 'title' => 'Editar']
                                        ),
                                    ],
                                ],
                            ],
                        ]); ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-car-front display-4 text-muted"></i>
                            <p class="text-muted mt-2">Este cliente no tiene vehículos registrados</p>
                            <?= Html::a(
                                'Registrar primer vehículo',
                                ['/vehiculo/create', 'Vehiculo[cliente_id]' => $model->id],
                                ['class' => 'btn btn-primary']
                            ) ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de alta rápida de vehículo -->
    <?= $this->render('/vehiculo/_modal_create', [
        'modalId' => 'vehiculo-quick-modal',
        'formId' => 'vehiculo-quick-form',
        'vehiculoSelectId' => 'vehiculo_id',
        'clienteSelectId' => 'cliente_id',
        'clienteIdFijo' => $model->id,
        'clienteNombre' => $model->nombre,
    ]) ?>

    <!-- Script para gestión de etiquetas con AJAX -->
    <?php
    $this->registerJs(<<<JS
        $(document).ready(function() {
            $('.etiqueta-checkbox').on('change', function() {
                var checkbox = $(this);
                var etiquetaId = checkbox.data('etiqueta-id');
                var clienteId = checkbox.data('cliente-id');
                var asignado = checkbox.is(':checked');
                
                $.ajax({
                    url: '/cliente/asignar-etiqueta',
                    type: 'POST',
                    data: {
                        etiqueta_id: etiquetaId,
                        cliente_id: clienteId,
                        asignado: asignado
                    },
                    success: function(response) {
                        if (response.success) {
                            var mensaje = asignado ? 'Etiqueta asignada' : 'Etiqueta eliminada';
                            // Mostrar notificación toast o alerta suave
                            if (window.showToast) {
                                showToast(mensaje, 'success');
                            } else {
                                console.log(mensaje);
                            }
                        } else {
                            // Revertir el checkbox si hubo error
                            checkbox.prop('checked', !asignado);
                            alert(response.message || 'Error al actualizar la etiqueta');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Revertir el checkbox si hubo error
                        checkbox.prop('checked', !asignado);
                        alert('Error de comunicación: ' + error);
                    }
                });
            });
        });
JS
    );
    ?>
</div>
