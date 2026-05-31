<?php
/** @var yii\web\View $this */
/** @var app\models\Tecnico $model */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\widgets\ActiveForm;

$this->title = $model->getFullName();
$this->params['breadcrumbs'][] = ['label' => 'Técnicos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="tecnico-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-person-gear me-2"></i><?= Html::encode($this->title) ?>
        </h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?php if ($model->status): ?>
                <?= Html::a('<i class="bi bi-person-x me-1"></i>Desactivar', ['deactivate', 'id' => $model->id], [
                    'class' => 'btn btn-outline-danger btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Desactivar este técnico?',
                ]) ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Datos del técnico -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header"><strong>Información del Técnico</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'options'    => ['class' => 'table table-bordered'],
                        'attributes' => [
                            'nombre',
                            'apellido',
                            ['label' => 'RUT', 'value' => $model->rut ?: '—'],
                            'email:email',
                            'telefono',
                            [
                                'label'  => 'Especialidad',
                                'format' => 'raw',
                                'value'  => $model->especialidad
                                    ? '<span class="badge bg-info text-dark">' . Html::encode($model->especialidad->nombre) . '</span>'
                                    : '—',
                            ],
                            ['label' => 'Costo/Hora', 'value' => $model->costo_hora ? '$ ' . number_format((float)$model->costo_hora, 0, ',', '.') : '—'],
                            ['label' => 'Estado', 'format' => 'raw',
                             'value' => $model->status ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'],
                            ['label' => 'Registrado', 'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—'],
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Listado de certificaciones -->
            <div class="card shadow-sm mt-3">
                <div class="card-header"><strong><i class="bi bi-award me-2"></i>Certificaciones</strong></div>
                <?php if (empty($model->certificaciones)): ?>
                    <div class="card-body text-center text-muted py-3">Sin certificaciones registradas.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Título</th>
                                    <th>Entidad</th>
                                    <th>Emisión</th>
                                    <th>Vencimiento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($model->certificaciones as $cert): ?>
                                <tr>
                                    <td><?= Html::encode($cert->titulo) ?></td>
                                    <td><?= Html::encode($cert->entidad_emisora ?? '—') ?></td>
                                    <td><?= $cert->fecha_emision ? date('d/m/Y', strtotime($cert->fecha_emision)) : '—' ?></td>
                                    <td>
                                        <?php if ($cert->fecha_vencimiento): ?>
                                            <?php $vence = strtotime($cert->fecha_vencimiento); ?>
                                            <span class="<?= $vence < time() ? 'text-danger' : '' ?>">
                                                <?= date('d/m/Y', $vence) ?>
                                                <?= $vence < time() ? '<span class="badge bg-danger ms-1">Vencida</span>' : '' ?>
                                            </span>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario agregar certificación -->
        <div class="col-md-5">
            <?php if ($model->foto_path): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong>Foto</strong></div>
                <div class="card-body text-center">
                    <img src="<?= Yii::$app->request->baseUrl . '/' . $model->foto_path ?>"
                         alt="Foto del técnico" class="img-fluid rounded" style="max-height:200px">
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-plus-circle me-1"></i><strong>Agregar Certificación</strong>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['action' => ['add-certificacion', 'id' => $model->id], 'method' => 'post']); ?>
                    <div class="mb-2">
                        <label class="form-label small">Título <span class="text-danger">*</span></label>
                        <input type="text" name="Certificacion[titulo]" class="form-control form-control-sm" required maxlength="150">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Entidad Emisora</label>
                        <input type="text" name="Certificacion[entidad_emisora]" class="form-control form-control-sm" maxlength="100">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Fecha de Emisión</label>
                        <input type="date" name="Certificacion[fecha_emision]" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Fecha de Vencimiento</label>
                        <input type="date" name="Certificacion[fecha_vencimiento]" class="form-control form-control-sm">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-floppy me-1"></i>Guardar Certificación
                    </button>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
