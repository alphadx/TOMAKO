<?php

declare(strict_types=1);

/**
 * Vista de inicialización de base de datos.
 *
 * @var yii\web\View $this
 * @var array{roles: int, idiomas: int, parametros: int, inicializado: bool} $estado
 * @var \app\components\services\DatabaseInitService $servicio
 */

use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Administracion') . ' — ' . Yii::t('app', 'Base de Datos');
$this->params['breadcrumbs'] = [
    Yii::t('app', 'Administracion'),
    Yii::t('app', 'Base de Datos'),
];
?>

<div class="ts-page-header">
    <h1 class="ts-page-title">⚙️ <?= Yii::t('app', 'Inicialización del Sistema') ?></h1>
</div>

<!-- ── Estado actual ───────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="ts-kpi-card <?= $estado['roles']     > 0 ? 'ts-kpi-success' : 'ts-kpi-warning' ?>">
            <div class="ts-kpi-icon" style="background:hsl(145,60%,88%)">👑</div>
            <div>
                <div class="ts-kpi-value"><?= $estado['roles'] ?></div>
                <div class="ts-kpi-label">Roles registrados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="ts-kpi-card <?= $estado['idiomas']   > 0 ? 'ts-kpi-success' : 'ts-kpi-warning' ?>">
            <div class="ts-kpi-icon" style="background:hsl(200,70%,88%)">🌐</div>
            <div>
                <div class="ts-kpi-value"><?= $estado['idiomas'] ?></div>
                <div class="ts-kpi-label">Idiomas activos</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="ts-kpi-card <?= $estado['parametros'] > 0 ? 'ts-kpi-success' : 'ts-kpi-warning' ?>">
            <div class="ts-kpi-icon" style="background:hsl(213,90%,90%)">⚙️</div>
            <div>
                <div class="ts-kpi-value"><?= $estado['parametros'] ?></div>
                <div class="ts-kpi-label">Parámetros configurados</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="ts-kpi-card <?= $estado['inicializado'] ? 'ts-kpi-success' : 'ts-kpi-danger' ?>">
            <div class="ts-kpi-icon" style="background:<?= $estado['inicializado'] ? 'hsl(145,60%,88%)' : 'hsl(354,70%,88%)' ?>">
                <?= $estado['inicializado'] ? '✅' : '⚠️' ?>
            </div>
            <div>
                <div class="ts-kpi-value" style="font-size:1.1rem">
                    <?= $estado['inicializado'] ? 'Inicializado' : 'Pendiente' ?>
                </div>
                <div class="ts-kpi-label">Estado general</div>
            </div>
        </div>
    </div>
</div>

<!-- ── Acción de inicialización ────────────────────────── -->
<div class="ts-form-card">
    <h5 class="ts-section-title">Inicializar / Sincronizar Datos Maestros</h5>
    <p class="text-muted mb-3" style="font-size:.9rem">
        Esta acción es <strong>idempotente</strong>: crea los registros que no existen
        (roles base, idiomas, parámetros). Los registros ya existentes no serán modificados.
    </p>

    <?php if ($estado['inicializado']): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" role="alert">
            ✅ El sistema ya se encuentra inicializado. Puede ejecutar la acción nuevamente para sincronizar registros faltantes.
        </div>
    <?php else: ?>
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
            ⚠️ El sistema aún no ha sido inicializado. Ejecute la acción para crear los datos base.
        </div>
    <?php endif ?>

    <?= Html::beginForm(Url::to(['/admin/database-init']), 'post') ?>
        <?= Html::submitButton(
            '🚀 ' . ($estado['inicializado'] ? 'Sincronizar datos maestros' : 'Inicializar sistema'),
            [
                'class'   => 'btn btn-primary',
                'data-confirm' => '¿Confirma que desea ejecutar la inicialización de datos maestros?',
            ]
        ) ?>
    <?= Html::endForm() ?>
</div>

<!-- ── Datos que se crearán ────────────────────────────── -->
<div class="ts-form-card mt-3">
    <h5 class="ts-section-title">Datos que se crean (si no existen)</h5>

    <div class="row g-3">
        <div class="col-md-4">
            <h6 class="fw-bold">Roles</h6>
            <ul class="list-unstyled" style="font-size:.875rem">
                <li>👑 administrador</li>
                <li>💼 operador</li>
                <li>🔧 mecánico</li>
            </ul>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold">Idiomas</h6>
            <ul class="list-unstyled" style="font-size:.875rem">
                <li>🇨🇱 Español (Chile) — predeterminado</li>
                <li>🇺🇸 English (US)</li>
            </ul>
        </div>
        <div class="col-md-4">
            <h6 class="fw-bold">Parámetros del sistema</h6>
            <ul class="list-unstyled" style="font-size:.875rem">
                <li>taller.nombre, taller.rut, taller.email</li>
                <li>sistema.moneda (CLP)</li>
                <li>sistema.timezone (America/Santiago)</li>
                <li>sistema.sesion.timeout (3600s)</li>
                <li>sistema.cache.ttl (300s)</li>
                <li>+ otros parámetros base</li>
            </ul>
        </div>
    </div>
</div>
