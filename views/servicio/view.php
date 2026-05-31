<?php
/** @var yii\web\View $this */
/** @var app\models\Servicio $model */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = $model->codigo . ' – ' . $model->nombre;
$this->params['breadcrumbs'][] = ['label' => 'Servicios', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$formatDuracion = static function (?int $duracion): string {
    if (empty($duracion)) {
        return '—';
    }

    if ($duracion < 60) {
        return $duracion . ' minutos';
    }

    $horas = intdiv($duracion, 60);
    $min   = $duracion % 60;

    if ($min === 0) {
        return $horas . ' horas';
    }

    return $horas . ' horas ' . $min . ' minutos';
};
?>

<div class="servicio-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-tools me-2"></i><?= Html::encode($model->nombre) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver', ['index'], ['class' => 'btn btn-outline-primary']) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><strong>Datos del Servicio</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'attributes' => [
                            'id',
                            'codigo',
                            'nombre',
                            'descripcion:text',
                            [
                                'label' => 'Categoría',
                                'value' => $model->categoria ? $model->categoria->nombre : '—',
                            ],
                            [
                                'label' => 'Precio Base',
                                'value' => '$' . number_format((float)$model->precio_base, 2, '.', ','),
                            ],
                            [
                                'label' => 'Duración Estimada',
                                'value' => $formatDuracion($model->duracion_estimada),
                            ],
                            [
                                'label'  => 'Estado',
                                'format' => 'raw',
                                'value'  => $model->status
                                    ? '<span class="badge bg-success">Activo</span>'
                                    : '<span class="badge bg-danger">Inactivo</span>',
                            ],
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
            <?php if ($model->status): ?>
            <div class="card border-danger mb-4">
                <div class="card-body text-center">
                    <?= Html::a(
                        '<i class="bi bi-x-circle me-1"></i>Desactivar Servicio',
                        ['deactivate', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-outline-danger btn-sm',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Desactivar este servicio?',
                        ]
                    ) ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card border-success mb-4">
                <div class="card-body text-center">
                    <?= Html::a(
                        '<i class="bi bi-check-circle me-1"></i>Activar Servicio',
                        ['activate', 'id' => $model->id],
                        [
                            'class'        => 'btn btn-outline-success btn-sm',
                            'data-method'  => 'post',
                            'data-confirm' => '¿Activar este servicio?',
                        ]
                    ) ?>
                </div>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Historial de precios -->
    <?php if (!empty($model->historialPrecios)): ?>
    <div class="card mt-2">
        <div class="card-header"><strong><i class="bi bi-clock-history me-1"></i>Historial de Precios</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha</th>
                        <th>Precio Anterior</th>
                        <th>Precio Nuevo</th>
                        <th>Modificado por</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($model->historialPrecios as $h): ?>
                    <tr>
                        <td><?= $h->created_at ? date('d/m/Y H:i', $h->created_at) : '—' ?></td>
                        <td>$<?= number_format((float)$h->precio_anterior, 2, '.', ',') ?></td>
                        <td>$<?= number_format((float)$h->precio_nuevo, 2, '.', ',') ?></td>
                        <td><?= Html::encode($h->usuario ? $h->usuario->getFullName() : '—') ?></td>
                        <td><?= Html::encode($h->motivo ?? '—') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>

    <!-- HU-028: Plantillas de Checklist asociadas -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-clipboard-list me-2"></i>Plantillas de Checklist</strong>
            <?= Html::a(
                '<i class="fas fa-plus"></i> Nueva Plantilla',
                ['/servicio/plantillas-create', 'servicioId' => $model->id],
                ['class' => 'btn btn-sm btn-success']
            ) ?>
        </div>
        <div class="card-body">
            <?php if (empty($model->plantillasChecklist)): ?>
                <p class="text-muted mb-0">
                    <em>No hay plantillas de checklist asociadas a este servicio.</em>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Items</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($model->plantillasChecklist as $plantilla): ?>
                                <tr>
                                    <td><?= Html::encode($plantilla->nombre) ?></td>
                                    <td><?= Html::encode($plantilla->descripcion ?? '-') ?></td>
                                    <td><span class="badge bg-info"><?= count($plantilla->items) ?> items</span></td>
                                    <td>
                                        <?php if ($plantilla->activa): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?= Html::a('<i class="fas fa-eye"></i>', ['/servicio/plantillas-view', 'id' => $plantilla->id], [
                                                'class' => 'btn btn-outline-info',
                                                'title' => 'Ver detalle',
                                            ]) ?>
                                            <?= Html::a('<i class="fas fa-edit"></i>', ['/servicio/plantillas-update', 'id' => $plantilla->id], [
                                                'class' => 'btn btn-outline-primary',
                                                'title' => 'Editar',
                                            ]) ?>
                                            <?= Html::a('<i class="fas fa-copy"></i>', ['/servicio/plantillas-duplicate', 'id' => $plantilla->id], [
                                                'class' => 'btn btn-outline-secondary',
                                                'title' => 'Duplicar',
                                                'data-method' => 'post',
                                                'data-confirm' => '¿Duplicar esta plantilla?',
                                            ]) ?>
                                            <?= Html::a('<i class="fas fa-trash"></i>', ['/servicio/plantillas-delete', 'id' => $plantilla->id], [
                                                'class' => 'btn btn-outline-danger',
                                                'title' => 'Eliminar',
                                                'data-method' => 'post',
                                                'data-confirm' => '¿Eliminar esta plantilla?',
                                            ]) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
