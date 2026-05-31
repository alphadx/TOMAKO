<?php
/** @var yii\web\View $this */
/** @var app\models\Cita $model */
/** @var array<int, array<string, mixed>> $auditoria */

use yii\helpers\Html;
use yii\widgets\DetailView;

$this->title = 'Cita #' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Citas', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$timeline = [
    'pendiente'   => ['icon' => 'bi-clock',         'color' => 'warning'],
    'confirmada'  => ['icon' => 'bi-check-circle',   'color' => 'primary'],
    'en_progreso' => ['icon' => 'bi-wrench',         'color' => 'info'],
    'completada'  => ['icon' => 'bi-check-all',      'color' => 'success'],
    'cancelada'   => ['icon' => 'bi-x-circle',       'color' => 'secondary'],
    'no_show'     => ['icon' => 'bi-person-x',       'color' => 'danger'],
];
$estadosOrden = ['pendiente', 'confirmada', 'en_progreso', 'completada'];
?>

<div class="cita-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-check me-2"></i><?= Html::encode($this->title) ?>
            <span class="badge <?= $model->getEstadoBadgeClass() ?> ms-2"><?= Html::encode($model->getEstadoLabel()) ?></span>
        </h1>
        <div class="d-flex gap-2">
            <?php if ($model->estado === 'pendiente'): ?>
                <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar', ['update', 'id' => $model->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                <?= Html::a('<i class="bi bi-check-lg me-1"></i>Confirmar', ['confirmar', 'id' => $model->id], [
                    'class' => 'btn btn-primary btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Confirmar esta cita?',
                ]) ?>
            <?php endif; ?>
            <?php if ($model->estado === 'confirmada'): ?>
                <?= Html::a('<i class="bi bi-play-fill me-1"></i>Iniciar Servicio', ['iniciar-servicio', 'id' => $model->id], [
                    'class' => 'btn btn-success btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Iniciar servicio y crear orden?',
                ]) ?>
                <?= Html::a('<i class="bi bi-person-x me-1"></i>No Show', ['no-show', 'id' => $model->id], [
                    'class' => 'btn btn-warning btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Marcar como No Show?',
                ]) ?>
            <?php endif; ?>
            <?php if (!in_array($model->estado, ['cancelada', 'completada', 'no_show'], true)): ?>
                <?= Html::a('<i class="bi bi-x-circle me-1"></i>Cancelar', ['cancelar', 'id' => $model->id], [
                    'class' => 'btn btn-outline-danger btn-sm', 'data-method' => 'post', 'data-confirm' => '¿Cancelar esta cita?',
                ]) ?>
            <?php endif; ?>
            <?php
                $clienteEmail = trim((string) ($model->cliente?->email ?? ''));
                $emailAsunto = rawurlencode('Información de tu cita en TOMAKO');
                $emailBody = rawurlencode('Hola ' . ($model->cliente?->nombre ?? '') . ', te recordamos tu cita del ' . $model->fecha . ' a las ' . substr($model->hora_inicio, 0, 5) . '.');
                $mailto = $clienteEmail !== '' ? 'mailto:' . $clienteEmail . '?subject=' . $emailAsunto . '&body=' . $emailBody : '#';
            ?>
            <?= Html::a('<i class="bi bi-envelope me-1"></i>Email', $mailto, [
                'class' => $clienteEmail !== '' ? 'btn btn-outline-primary btn-sm' : 'btn btn-outline-secondary btn-sm disabled',
                'title' => $clienteEmail !== '' ? 'Contactar cliente' : 'Cliente sin email registrado',
            ]) ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Información principal -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="bi bi-info-circle me-1"></i>Información de la Cita</strong></div>
                <div class="card-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'options'    => ['class' => 'table table-bordered mb-0'],
                        'attributes' => [
                            ['label' => 'Fecha',       'value' => date('d/m/Y', strtotime($model->fecha))],
                            ['label' => 'Hora',        'value' => substr($model->hora_inicio, 0, 5) . ' – ' . substr($model->hora_fin, 0, 5)],
                            ['label' => 'Cliente',     'format' => 'raw',
                             'value' => $model->cliente
                                ? Html::a(Html::encode($model->cliente->nombre), ['/cliente/view', 'id' => $model->cliente_id])
                                : '—'],
                            ['label' => 'Vehículo',    'format' => 'raw',
                             'value' => $model->vehiculo
                                ? '<span class="badge bg-dark me-1">' . Html::encode($model->vehiculo->patente) . '</span>' . Html::encode($model->vehiculo->marca . ' ' . $model->vehiculo->modelo)
                                : '—'],
                            ['label' => 'Estado',      'format' => 'raw',
                             'value' => '<span class="badge ' . $model->getEstadoBadgeClass() . '">' . Html::encode($model->getEstadoLabel()) . '</span>'],
                            ['label' => 'Notas',       'value' => $model->notas ?: '—'],
                            ['label' => 'Registrado',  'value' => $model->created_at ? date('d/m/Y H:i', $model->created_at) : '—'],
                        ],
                    ]) ?>
                </div>
            </div>

            <!-- Servicios asociados -->
            <div class="card shadow-sm mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="bi bi-wrench me-1"></i>Servicios Solicitados</strong>
                    <?php if (!empty($model->servicios)): ?>
                        <span class="badge bg-info text-dark" title="Tiempo aproximado total">
                            <i class="bi bi-clock me-1"></i><?= Html::encode($model->getTiempoAproximadoFormateado()) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (empty($model->servicios)): ?>
                    <div class="card-body text-muted text-center">Sin servicios registrados.</div>
                <?php else: ?>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2">
                            <?php
                                $tipoColors = [
                                    'servicio' => '#0d6efd',
                                    'insumo' => '#198754',
                                    'ambos' => '#6f42c1',
                                ];
                            ?>
                            <?php foreach ($model->servicios as $s): ?>
                                <?php
                                    $tipo = (string) ($s->categoria->tipo ?? 'servicio');
                                    $bg = (string) ($s->categoria->color ?? ($tipoColors[$tipo] ?? '#6c757d'));
                                    $tipoLabel = ucfirst($tipo);
                                    $duracion = $s->duracion_estimada ?? 0;
                                ?>
                                <span class="badge rounded-pill"
                                      style="background-color: <?= Html::encode($bg) ?>; color: #fff; padding: .45rem .7rem;"
                                      title="<?= Html::encode($s->categoria->nombre ?? $tipoLabel) ?> - <?= $duracion ?> min">
                                    <?= Html::encode($s->nombre) ?>
                                    <span class="ms-1">•</span>
                                    <?= Html::encode($tipoLabel) ?>
                                    <?php if ($duracion > 0): ?>
                                        <span class="ms-1 opacity-75">(<?= $duracion ?>m)</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="small text-muted mt-2">Colores por tipo/categoría para identificar rápidamente los servicios solicitados.</div>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($model->orden): ?>
            <div class="card shadow-sm mt-3 border-success">
                <div class="card-header bg-success text-white"><strong><i class="bi bi-file-text me-1"></i>Orden de Servicio</strong></div>
                <div class="card-body">
                    <?= Html::a(
                        '<i class="bi bi-eye me-1"></i>Ver Orden ' . Html::encode($model->orden->codigo),
                        ['/orden/view', 'id' => $model->orden->id],
                        ['class' => 'btn btn-success']
                    ) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($model->estado, ['pendiente', 'confirmada'], true)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header"><strong><i class="bi bi-arrow-repeat me-1"></i>Reprogramar Cita</strong></div>
                <div class="card-body">
                    <form method="post" action="<?= \yii\helpers\Url::to(['reprogramar', 'id' => $model->id]) ?>" class="row g-2">
                        <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
                        <div class="col-md-4">
                            <input type="date" name="fecha" class="form-control" value="<?= Html::encode($model->fecha) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <input type="time" name="hora_inicio" class="form-control" value="<?= Html::encode(substr((string) $model->hora_inicio, 0, 5)) ?>" required>
                        </div>
                        <div class="col-md-3">
                            <input type="time" name="hora_fin" class="form-control" value="<?= Html::encode(substr((string) $model->hora_fin, 0, 5)) ?>" required>
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mt-3">
                <div class="card-header"><strong><i class="bi bi-journal-text me-1"></i>Auditoría</strong></div>
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-shield-lock fs-1 mb-3 d-block"></i>
                    <p class="mb-2">La auditoría de citas ha sido movida al módulo de Auditoría.</p>
                    <p class="small">
                        <?= \yii\helpers\Html::a(
                            '<i class="bi bi-box-arrow-in-right me-1"></i>Ver auditoría de citas',
                            ['/auditoria/index', 'tipo' => 'cita'],
                            ['class' => 'btn btn-outline-primary btn-sm']
                        ) ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Línea de tiempo del estado -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="bi bi-activity me-1"></i>Estado</strong></div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($estadosOrden as $e):
                            $info = $timeline[$e];
                            $activo = $model->estado === $e;
                            $pasado = array_search($e, $estadosOrden) < array_search($model->estado, $estadosOrden);
                        ?>
                        <div class="d-flex align-items-center gap-2 <?= !$activo && !$pasado ? 'text-muted' : '' ?>">
                            <i class="bi <?= $info['icon'] ?> text-<?= $activo ? $info['color'] : ($pasado ? 'success' : 'secondary') ?> fs-5"></i>
                            <span class="<?= $activo ? 'fw-bold' : '' ?>"><?= Html::encode(\app\models\Cita::getEstadosList()[$e] ?? $e) ?></span>
                            <?php if ($activo): ?>
                                <span class="badge <?= $model->getEstadoBadgeClass() ?> ms-auto">Actual</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php if (in_array($model->estado, ['cancelada', 'no_show'], true)):
                            $info = $timeline[$model->estado];
                        ?>
                        <hr>
                        <div class="d-flex align-items-center gap-2 fw-bold">
                            <i class="bi <?= $info['icon'] ?> text-<?= $info['color'] ?> fs-5"></i>
                            <span><?= Html::encode(\app\models\Cita::getEstadosList()[$model->estado] ?? $model->estado) ?></span>
                            <span class="badge <?= $model->getEstadoBadgeClass() ?> ms-auto">Actual</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
