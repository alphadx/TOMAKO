<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, int|float|array> $kpis */
/** @var app\models\Cita[] $citasHoy */
/** @var app\models\OrdenServicio[] $ordenesPrioritarias */
/** @var app\models\InventoryItem[] $alertasStock */
/** @var array<string,int> $ordenesActivas */
/** @var array<int,array{icon:string,title:string,description:string,url:array}> $accesosRapidos */
/** @var array<string, array{is_visible: bool, sort_order: int, is_collapsed: bool}> $preferencias */
/** @var array<string, array{id: string, title: string, category: string}> $widgetsDisponibles */

use app\components\widgets\AccesosRapidosWidget;
use app\components\widgets\AlertasStockWidget;
use app\components\widgets\CitasHoyWidget;
use app\components\widgets\KpiCard;
use app\components\widgets\OrdenesActivasWidget;
use app\components\widgets\OrdenesPrioritariasWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

$this->title = 'Panel de Control';

$usuario = Yii::$app->user->identity;
$saludoNombre = $usuario !== null ? (string) $usuario->getFullName() : 'Usuario';
$saludoRol = $usuario !== null ? (string) ($usuario->rol->nombre ?? 'Sin rol') : 'Sin rol';

$this->registerJsFile('@web/js/dashboard.js', ['position' => View::POS_END]);

// Función helper para verificar si un widget/KPI es visible según preferencias
$esVisible = function(string $widgetId) use ($preferencias): bool {
    if (empty($preferencias)) {
        return true; // Por defecto todos son visibles si no hay preferencias
    }
    $pref = $preferencias[$widgetId] ?? null;
    return $pref !== null && $pref['is_visible'];
};

$renderWidgetSafe = static function (callable $render): string {
    try {
        return (string) $render();
    } catch (\Throwable $e) {
        Yii::warning('Fallo de widget dashboard: ' . $e->getMessage(), 'app.dashboard');
        return '<div class="ts-panel"><p class="text-muted mb-0">No se pudo cargar este bloque temporalmente.</p></div>';
    }
};
?>

<div class="site-dashboard" id="ts-dashboard-root" data-refresh-url="<?= Html::encode(Url::to(['/dashboard/refresh-kpi', 'kpi' => 'all'])) ?>" data-refresh-interval="60000">
    <section class="ts-dashboard-hero">
        <div>
            <h1>Hola, <?= Html::encode($saludoNombre) ?></h1>
            <p>Rol: <?= Html::encode($saludoRol) ?>. Resumen en tiempo real del taller para priorizar la operacion del dia.</p>
        </div>
        <div class="ts-dashboard-hero-actions">
            <a class="btn btn-primary" href="<?= Url::to(['/cita/create']) ?>">Nueva cita</a>
            <a class="btn btn-outline-primary" href="<?= Url::to(['/orden/create']) ?>">Nueva orden</a>
            <?php if ($usuario !== null && $usuario->isAdmin()): ?>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/dashboard/configurar']) ?>">
                    <i class="bi bi-gear"></i> Configurar Dashboard
                </a>
            <?php endif; ?>
        </div>
    </section>

    <section class="row g-3 mb-1">
        <?php if ($esVisible('kpi_servicios_activos')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="servicios_activos" aria-label="Servicios activos en proceso">
                <?= KpiCard::widget([
                    'titulo' => 'Servicios Activos',
                    'valor' => (int) ($kpis['servicios_activos'] ?? 0),
                    'icono' => '🔧',
                    'tipo' => 'info',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_citas_hoy')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="citas_hoy" aria-label="Cantidad de citas agendadas para hoy">
                <?= KpiCard::widget([
                    'titulo' => 'Citas Hoy',
                    'valor' => (int) ($kpis['citas_hoy'] ?? 0),
                    'icono' => '📅',
                    'tipo' => 'primary',
                    'url' => ['/cita/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_stock_bajo')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="stock_bajo" aria-label="Cantidad de articulos con stock bajo">
                <?= KpiCard::widget([
                    'titulo' => 'Stock Bajo',
                    'valor' => (int) ($kpis['stock_bajo'] ?? 0),
                    'icono' => '⚠️',
                    'tipo' => 'danger',
                    'subtitulo' => ((int) ($kpis['stock_bajo'] ?? 0)) > 0 ? 'Critico' : 'Estable',
                    'url' => ['/inventario/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_ingresos_mes')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="ingresos_mes" data-kpi-format="currency" aria-label="Ingresos acumulados del mes">
                <?= KpiCard::widget([
                    'titulo' => 'Ingresos Mes',
                    'valor' => Yii::$app->formatter->asCurrency((float) ($kpis['ingresos_mes'] ?? 0.0)),
                    'icono' => '💳',
                    'tipo' => 'success',
                    'url' => ['/pago/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_trabajos_listos')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="trabajos_listos" aria-label="Ordenes listas para entrega">
                <?= KpiCard::widget([
                    'titulo' => 'Trabajos Listos',
                    'valor' => (int) ($kpis['trabajos_listos'] ?? 0),
                    'icono' => '✅',
                    'tipo' => 'success',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_clientes_nuevos')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="clientes_nuevos" aria-label="Clientes nuevos en el mes actual">
                <?= KpiCard::widget([
                    'titulo' => 'Clientes Nuevos',
                    'valor' => (int) ($kpis['clientes_nuevos'] ?? 0),
                    'icono' => '👥',
                    'tipo' => 'info',
                    'url' => ['/cliente/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_valor_inventario')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="valor_inventario" data-kpi-format="currency" aria-label="Valor total del inventario activo">
                <?= KpiCard::widget([
                    'titulo' => 'Valor Inventario',
                    'valor' => Yii::$app->formatter->asCurrency((float) ($kpis['valor_inventario'] ?? 0.0)),
                    'icono' => '📦',
                    'tipo' => 'warning',
                    'url' => ['/inventario/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <!-- Nuevos indicadores implementados -->
        <?php if ($esVisible('kpi_tasa_entrega_tiempo')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_entrega_tiempo" data-kpi-format="percent" aria-label="Porcentaje de entregas a tiempo">
                <?= KpiCard::widget([
                    'titulo' => 'Entregas a Tiempo',
                    'valor' => number_format((float) ($kpis['tasa_entrega_tiempo'] ?? 0.0), 1) . '%',
                    'icono' => '⏱️',
                    'tipo' => 'success',
                    'subtitulo' => ((float) ($kpis['tasa_entrega_tiempo'] ?? 0.0)) >= 80 ? 'Excelente' : 'Mejorable',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tiempo_promedio_resolucion')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tiempo_promedio_resolucion" data-kpi-format="number" aria-label="Tiempo promedio de resolución en días">
                <?= KpiCard::widget([
                    'titulo' => 'Tiempo Promedio Resolución',
                    'valor' => number_format((float) ($kpis['tiempo_promedio_resolucion'] ?? 0.0), 1) . ' días',
                    'icono' => '🔧',
                    'tipo' => 'info',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tasa_cancelacion')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_cancelacion" data-kpi-format="percent" aria-label="Porcentaje de órdenes canceladas">
                <?= KpiCard::widget([
                    'titulo' => 'Tasa Cancelación',
                    'valor' => number_format((float) ($kpis['tasa_cancelacion'] ?? 0.0), 1) . '%',
                    'icono' => '❌',
                    'tipo' => ((float) ($kpis['tasa_cancelacion'] ?? 0.0)) <= 5 ? 'success' : 'danger',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_rotacion_inventario')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="rotacion_inventario" data-kpi-format="number" aria-label="Rotación de inventario mensual">
                <?= KpiCard::widget([
                    'titulo' => 'Rotación Inventario',
                    'valor' => number_format((float) ($kpis['rotacion_inventario'] ?? 0.0), 2),
                    'icono' => '🔄',
                    'tipo' => 'warning',
                    'subtitulo' => ((float) ($kpis['rotacion_inventario'] ?? 0.0)) >= 1 ? 'Óptima' : 'Lenta',
                    'url' => ['/inventario/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tasa_no_show')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_no_show" data-kpi-format="percent" aria-label="Porcentaje de ausentismo en citas">
                <?= KpiCard::widget([
                    'titulo' => 'Ausentismo Citas',
                    'valor' => number_format((float) ($kpis['tasa_no_show'] ?? 0.0), 1) . '%',
                    'icono' => '👻',
                    'tipo' => ((float) ($kpis['tasa_no_show'] ?? 0.0)) <= 10 ? 'success' : 'warning',
                    'url' => ['/cita/index'],
                ]) ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Nuevos 5 indicadores (6-10) -->
    <section class="row g-3 mb-1">
        <?php if ($esVisible('kpi_ingreso_promedio_orden')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="ingreso_promedio_orden" data-kpi-format="currency" aria-label="Ingreso promedio por orden">
                <?= KpiCard::widget([
                    'titulo' => 'Ingreso Promedio Orden',
                    'valor' => Yii::$app->formatter->asCurrency((float) ($kpis['ingreso_promedio_orden'] ?? 0.0)),
                    'icono' => '💰',
                    'tipo' => 'success',
                    'subtitulo' => ((float) ($kpis['ingreso_promedio_orden'] ?? 0.0)) >= 50000 ? 'Óptimo' : 'Mejorable',
                    'url' => ['/orden/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tasa_retencion_clientes')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_retencion_clientes" data-kpi-format="percent" aria-label="Tasa de retención de clientes">
                <?= KpiCard::widget([
                    'titulo' => 'Retención Clientes',
                    'valor' => number_format((float) ($kpis['tasa_retencion_clientes'] ?? 0.0), 1) . '%',
                    'icono' => '🤝',
                    'tipo' => ((float) ($kpis['tasa_retencion_clientes'] ?? 0.0)) >= 50 ? 'success' : 'warning',
                    'subtitulo' => ((float) ($kpis['tasa_retencion_clientes'] ?? 0.0)) >= 50 ? 'Excelente' : 'Por mejorar',
                    'url' => ['/cliente/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_productividad_tecnico')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="productividad_tecnico" data-kpi-format="number" aria-label="Productividad promedio por técnico">
                <?= KpiCard::widget([
                    'titulo' => 'Productividad Técnico',
                    'valor' => number_format((float) ($kpis['productividad_tecnico'] ?? 0.0), 1) . ' órdenes',
                    'icono' => '👨‍🔧',
                    'tipo' => 'info',
                    'subtitulo' => ((float) ($kpis['productividad_tecnico'] ?? 0.0)) >= 10 ? 'Alta' : 'Normal',
                    'url' => ['/tecnico/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tasa_quiebre_stock')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_quiebre_stock" data-kpi-format="percent" aria-label="Tasa de quiebre de stock">
                <?= KpiCard::widget([
                    'titulo' => 'Quiebre de Stock',
                    'valor' => number_format((float) ($kpis['tasa_quiebre_stock'] ?? 0.0), 1) . '%',
                    'icono' => '⚠️',
                    'tipo' => ((float) ($kpis['tasa_quiebre_stock'] ?? 0.0)) <= 10 ? 'success' : 'danger',
                    'subtitulo' => ((float) ($kpis['tasa_quiebre_stock'] ?? 0.0)) <= 10 ? 'Controlado' : 'Crítico',
                    'url' => ['/inventario/index'],
                ]) ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Terceros 5 indicadores (11-15) -->
    <section class="row g-3 mb-1">
        <?php if ($esVisible('kpi_frecuencia_servicio_vehiculo')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="frecuencia_servicio_vehiculo" data-kpi-format="number" aria-label="Frecuencia de servicio por vehículo en días">
                <?= KpiCard::widget([
                    'titulo' => 'Frecuencia Servicio Vehículo',
                    'valor' => number_format((float) ($kpis['frecuencia_servicio_vehiculo'] ?? 0.0), 0) . ' días',
                    'icono' => '🚗',
                    'tipo' => 'info',
                    'subtitulo' => ((float) ($kpis['frecuencia_servicio_vehiculo'] ?? 0.0)) > 0 && ((float) ($kpis['frecuencia_servicio_vehiculo'] ?? 0.0)) <= 90 ? 'Regular' : 'Esporádico',
                    'url' => ['/vehiculo/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_margen_bruto_servicio')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="margen_bruto_servicio" data-kpi-format="percent" aria-label="Margen bruto promedio de servicios">
                <?= KpiCard::widget([
                    'titulo' => 'Margen Bruto Servicio',
                    'valor' => number_format((float) ($kpis['margen_bruto_servicio'] ?? 0.0), 1) . '%',
                    'icono' => '📈',
                    'tipo' => ((float) ($kpis['margen_bruto_servicio'] ?? 0.0)) >= 30 ? 'success' : 'warning',
                    'subtitulo' => ((float) ($kpis['margen_bruto_servicio'] ?? 0.0)) >= 30 ? 'Óptimo' : 'Por mejorar',
                    'url' => ['/servicio/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_tasa_morosidad')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="tasa_morosidad" data-kpi-format="percent" aria-label="Tasa de morosidad en pagos">
                <?= KpiCard::widget([
                    'titulo' => 'Tasa de Morosidad',
                    'valor' => number_format((float) ($kpis['tasa_morosidad'] ?? 0.0), 1) . '%',
                    'icono' => '💳',
                    'tipo' => ((float) ($kpis['tasa_morosidad'] ?? 0.0)) <= 10 ? 'success' : 'danger',
                    'subtitulo' => ((float) ($kpis['tasa_morosidad'] ?? 0.0)) <= 10 ? 'Controlado' : 'Alto riesgo',
                    'url' => ['/pago/index'],
                ]) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('kpi_ocupacion_agenda')) : ?>
            <div class="col-12 col-sm-6 col-xl-3" data-kpi="ocupacion_agenda" data-kpi-format="percent" aria-label="Ocupación de la agenda">
                <?= KpiCard::widget([
                    'titulo' => 'Ocupación Agenda',
                    'valor' => number_format((float) ($kpis['ocupacion_agenda'] ?? 0.0), 1) . '%',
                    'icono' => '📅',
                    'tipo' => ((float) ($kpis['ocupacion_agenda'] ?? 0.0)) >= 70 ? 'success' : 'warning',
                    'subtitulo' => ((float) ($kpis['ocupacion_agenda'] ?? 0.0)) >= 70 ? 'Óptima' : 'Disponible',
                    'url' => ['/cita/index'],
                ]) ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="row g-3 mb-3">
        <?php if ($esVisible('widget_citas_hoy')) : ?>
            <div class="col-12 col-md-6">
                <?= $renderWidgetSafe(static fn(): string => CitasHoyWidget::widget(['citas' => $citasHoy])) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('widget_ordenes_prioritarias')) : ?>
            <div class="col-12 col-md-6">
                <?= $renderWidgetSafe(static fn(): string => OrdenesPrioritariasWidget::widget(['ordenes' => $ordenesPrioritarias])) ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="row g-3">
        <?php if ($esVisible('widget_ordenes_activas')) : ?>
            <div class="col-12 col-md-4">
                <?= $renderWidgetSafe(static fn(): string => OrdenesActivasWidget::widget(['ordenes' => $ordenesActivas])) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('widget_alertas_stock')) : ?>
            <div class="col-12 col-md-4">
                <?= $renderWidgetSafe(static fn(): string => AlertasStockWidget::widget(['alertas' => $alertasStock])) ?>
            </div>
        <?php endif; ?>
        <?php if ($esVisible('widget_accesos_rapidos')) : ?>
            <div class="col-12 col-md-4">
                <?= $renderWidgetSafe(static fn(): string => AccesosRapidosWidget::widget(['accesos' => $accesosRapidos])) ?>
            </div>
        <?php endif; ?>
    </section>
</div>
