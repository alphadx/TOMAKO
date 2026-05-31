<?php
declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, int|null> $stats */
/** @var app\models\Cita[] $proximasCitas */
/** @var app\models\OrdenServicio[] $ordenesPrioritarias */
/** @var array<int, array{icon:string,title:string,description:string,url:array}> $quickLinks */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Panel de Control';

$renderMetric = static function (?int $value): string {
    return $value === null ? 'N/D' : number_format($value, 0, ',', '.');
};
?>

<div class="site-dashboard">
    <section class="ts-dashboard-hero">
        <div>
            <h1>Operación diaria del taller</h1>
            <p>Vista unificada de agenda, órdenes de trabajo y estado operativo para tomar decisiones rápidas.</p>
        </div>
        <div class="ts-dashboard-hero-actions">
            <a class="btn btn-primary" href="<?= Url::to(['/cita/create']) ?>">Agendar cita</a>
            <a class="btn btn-outline-primary" href="<?= Url::to(['/orden/create']) ?>">Abrir orden</a>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-info">
                <div class="ts-kpi-icon bg-info-subtle">👥</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['clientesActivos'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Clientes activos</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-success">
                <div class="ts-kpi-icon bg-success-subtle">🚗</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['vehiculosActivos'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Vehículos activos</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-warning">
                <div class="ts-kpi-icon bg-warning-subtle">📅</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['citasHoy'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Citas para hoy</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-danger">
                <div class="ts-kpi-icon bg-danger-subtle">🔧</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['ordenesAbiertas'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Órdenes abiertas</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-warning">
                <div class="ts-kpi-icon bg-warning-subtle">📦</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['inventarioCritico'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Ítems en stock crítico</div>
                </div>
            </article>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <article class="ts-kpi-card ts-kpi-info">
                <div class="ts-kpi-icon bg-info-subtle">👨‍🔧</div>
                <div>
                    <div class="ts-kpi-value"><?= Html::encode($renderMetric($stats['tecnicosActivos'] ?? null)) ?></div>
                    <div class="ts-kpi-label">Técnicos activos</div>
                </div>
            </article>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="ts-panel">
                <div class="ts-panel-header">
                    <h2>Próximas citas</h2>
                    <a href="<?= Url::to(['/cita/index']) ?>" class="btn btn-sm btn-outline-secondary">Ver agenda</a>
                </div>
                <?php if (empty($proximasCitas)): ?>
                    <p class="text-muted mb-0">No hay citas próximas para mostrar.</p>
                <?php else: ?>
                    <ul class="ts-list-clean">
                        <?php foreach ($proximasCitas as $cita): ?>
                            <li>
                                <div>
                                    <strong><?= Html::encode($cita->cliente->nombre ?? 'Sin cliente') ?></strong>
                                    <div class="small text-muted">
                                        <?= Html::encode(($cita->vehiculo->patente ?? 'Sin patente') . ' · ' . ($cita->vehiculo->marca ?? '') . ' ' . ($cita->vehiculo->modelo ?? '')) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold"><?= Html::encode($cita->fecha) ?></div>
                                    <div class="small text-muted"><?= Html::encode(substr((string) $cita->hora_inicio, 0, 5)) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="ts-panel">
                <div class="ts-panel-header">
                    <h2>Órdenes prioritarias</h2>
                    <a href="<?= Url::to(['/orden/index']) ?>" class="btn btn-sm btn-outline-secondary">Ver órdenes</a>
                </div>
                <?php if (empty($ordenesPrioritarias)): ?>
                    <p class="text-muted mb-0">No hay órdenes prioritarias pendientes.</p>
                <?php else: ?>
                    <ul class="ts-list-clean">
                        <?php foreach ($ordenesPrioritarias as $orden): ?>
                            <li>
                                <div>
                                    <strong><?= Html::encode($orden->codigo) ?></strong>
                                    <div class="small text-muted">
                                        <?= Html::encode(($orden->cliente->nombre ?? 'Sin cliente') . ' · ' . ($orden->vehiculo->patente ?? 'Sin patente')) ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?= Html::encode($orden->getPrioridadBadgeClass()) ?>"><?= Html::encode(ucfirst($orden->prioridad)) ?></span>
                                    <div class="small text-muted mt-1"><?= Html::encode($orden->getEstadosList()[$orden->estado] ?? $orden->estado) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="ts-panel">
        <div class="ts-panel-header">
            <h2>Accesos rápidos</h2>
        </div>
        <div class="row g-3">
            <?php foreach ($quickLinks as $link): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <a class="ts-quick-link" href="<?= Url::to($link['url']) ?>">
                        <div class="ts-quick-link-icon"><?= Html::encode($link['icon']) ?></div>
                        <div>
                            <h3><?= Html::encode($link['title']) ?></h3>
                            <p><?= Html::encode($link['description']) ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>
