<?php

declare(strict_types=1);

/**
 * Layout principal de TOMAKO (autenticado).
 * Incluye sidebar colapsable, topbar, toasts y footer.
 *
 * @var yii\web\View $this
 * @var string       $content
 */

use app\assets\AppAsset;
use app\components\services\NotificacionService;
use app\components\widgets\FlashMessages;
use app\models\Permiso;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\helpers\Url;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description'] ?? '']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);

$usuario = Yii::$app->user->isGuest ? null : Yii::$app->user->identity;
$urlActual = Yii::$app->request->url;
$notifCount = 0;

if ($usuario !== null) {
    $notifCount = (new NotificacionService())->getCountNoLeidas((int) $usuario->id);
}

// Menú del sidebar: [icono, etiqueta, ruta, modulo]
$menuItems = [
    ['icono' => '📊', 'label' => Yii::t('app', 'Panel de Control'), 'url' => ['/dashboard/index'], 'modulo' => 'dashboard'],
    ['icono' => '👥', 'label' => Yii::t('app', 'Clientes'),         'url' => ['/cliente/index'], 'modulo' => 'cliente',
        'submenu' => [
            ['label' => 'Listado', 'url' => ['/cliente/index']],
            ['label' => 'Etiquetas', 'url' => ['/etiqueta/index']],
        ]
    ],
    ['icono' => '🚗', 'label' => Yii::t('app', 'Vehiculos'),        'url' => ['/vehiculo/index'], 'modulo' => 'vehiculo'],
    ['icono' => '📅', 'label' => Yii::t('app', 'Citas'),            'url' => ['/cita/index'], 'modulo' => 'cita'],
    [
        'icono' => '🔧', 
        'label' => Yii::t('app', 'Ordenes de Servicio'), 
        'url' => ['/orden/index'], 
        'modulo' => 'orden',
        'submenu' => [
            ['label' => 'Vista nueva', 'url' => ['/orden/index']],
            ['label' => 'Listado operativo', 'url' => ['/orden-servicio/index']],
            ['label' => 'Tablero Kanban', 'url' => ['/orden-servicio/kanban']],
            ['label' => 'Reporte Técnico', 'url' => ['/orden-servicio/reporte-tecnico']],
            ['label' => 'Reporte Checklist', 'url' => ['/orden-servicio/reporte-checklist']],
        ]
    ],
    ['icono' => '💳', 'label' => Yii::t('app', 'Pagos'), 'url' => ['/pago/index'], 'modulo' => 'pago'],
    ['icono' => '🔔', 'label' => Yii::t('app', 'Notificaciones'), 'url' => ['/notificacion/index']],
    ['icono' => '📦', 'label' => Yii::t('app', 'Inventario'),       'url' => ['/inventario/index'], 'modulo' => 'inventario',
        'submenu' => [
            ['label' => 'Listado', 'url' => ['/inventario/index']],
            ['label' => 'Proveedores', 'url' => ['/proveedor/index']],
            ['label' => 'Órdenes de Compra', 'url' => ['/orden-compra/index']],
        ]
    ],
    ['icono' => '🛠️', 'label' => Yii::t('app', 'Servicios'),        'url' => ['/servicio/index'], 'modulo' => 'servicio',
        'submenu' => [
            ['label' => 'Listado', 'url' => ['/servicio/index']],
            ['label' => 'Rentabilidad', 'url' => ['/servicio/rentabilidad']],
            ['label' => 'Calculadora', 'url' => ['/calculadora/index']],
        ]
    ],
    ['icono' => '📋', 'label' => Yii::t('app', 'Seguimiento'),      'url' => ['/seguimiento/index'], 'modulo' => 'seguimiento',
        'submenu' => [
            ['label' => 'Agenda', 'url' => ['/seguimiento/index']],
            ['label' => 'Pendientes', 'url' => ['/seguimiento/pendientes']],
            ['label' => 'Reportes', 'url' => ['/seguimiento/reportes']],
        ]
    ],
    ['icono' => '🗂️', 'label' => Yii::t('app', 'Categorias'),       'url' => ['/categoria/index'], 'modulo' => 'categoria'],
    ['icono' => '🚙', 'label' => Yii::t('app', 'Marcas y Modelos'),  'url' => ['/marca-modelo/index'], 'modulo' => 'marca'],
    ['icono' => '👨‍🔧', 'label' => Yii::t('app', 'Tecnicos'),         'url' => ['/tecnico/index'], 'modulo' => 'tecnico'],
    ['icono' => '🎯', 'label' => Yii::t('app', 'Especialidades'),   'url' => ['/especialidad/index'], 'modulo' => 'especialidad'],
    ['icono' => '📘', 'label' => Yii::t('app', 'Manual de Usuario'), 'url' => ['/manual/index'], 'modulo' => 'manual'],
    ['icono' => '⚙️',  'label' => Yii::t('app', 'Configuracion'),   'url' => ['/admin/database'], 'grupo' => true, 'modulo' => 'admin'],
    ['icono' => '📜', 'label' => Yii::t('app', 'Auditoria'),        'url' => ['/audit-log/index'], 'modulo' => 'admin'],
    ['icono' => '🛡️', 'label' => Yii::t('app', 'Roles'),            'url' => ['/rol/index'], 'modulo' => 'rol'],
    ['icono' => '👤', 'label' => Yii::t('app', 'Usuarios'),         'url' => ['/usuario/index'], 'modulo' => 'usuario'],
];

$permisosUsuario = [];
if ($usuario !== null && (int) $usuario->rol_id !== 1) {
    try {
        $permisosUsuario = Permiso::find()
            ->alias('p')
            ->innerJoin('{{%rol_permiso}} rp', 'rp.permiso_id = p.id')
            ->where(['rp.rol_id' => (int) $usuario->rol_id])
            ->select('p.nombre')
            ->column();
    } catch (\Throwable) {
        $permisosUsuario = [];
    }
}

$menuItems = array_values(array_filter($menuItems, static function (array $item) use ($usuario, $permisosUsuario): bool {
    if ($usuario === null) {
        return false;
    }

    if ((int) $usuario->rol_id === 1) {
        return true;
    }

    $modulo = (string) ($item['modulo'] ?? '');
    if ($modulo === '') {
        return true;
    }

    if ($permisosUsuario === []) {
        return true;
    }

    // Compatibilidad: se aceptan permisos en español (modulo.ver) y estilo view.
    return in_array($modulo . '.ver', $permisosUsuario, true)
        || in_array($modulo . '.view', $permisosUsuario, true);
}));
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <title><?= Html::encode($this->title) ?> – TOMAKO</title>
    <?php $this->head() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@400;500;600;700&family=Space+Grotesk:wght@500&display=swap" rel="stylesheet">
    <style>
        .ts-brand-name span { color: hsl(354, 78%, 56%); }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<!-- ── Topbar ──────────────────────────────────────────── -->
<header id="ts-topbar" role="banner">
    <button class="ts-sidebar-toggle" id="ts-sidebar-toggle" aria-label="<?= Yii::t('app', 'Menú') ?>" aria-expanded="true">☰</button>
    <a href="<?= Yii::$app->homeUrl ?>" class="ts-brand" aria-label="Inicio TOMAKO">
        <img src="<?= Yii::getAlias('@web/logo.png') ?>" alt="TOMAKO" class="ts-brand-logo">
        <span class="ts-brand-copy">
            <strong class="ts-brand-name">TO<span>MAKO</span></strong>
        </span>
    </a>

    <div class="ts-topbar-actions">
        <?php if ($usuario !== null): ?>
            <div class="me-2 px-2 py-1 ts-user-chip">
                <div class="ts-user-chip-label">Bienvenido, <?= Html::encode($usuario->getFullName()) ?></div>
                <div>
                    <span class="badge bg-info text-dark mt-1"><?= Html::encode($usuario->rol->nombre ?? 'Sin rol') ?></span>
                </div>
            </div>
            <a href="<?= \yii\helpers\Url::to(['/notificacion/index']) ?>" class="btn btn-sm btn-outline-light position-relative me-2" title="Notificaciones">
                <span>🔔</span>
                <span id="notif-badge" data-count-url="<?= \yii\helpers\Url::to(['/notificacion/contador-json']) ?>" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $notifCount > 0 ? '' : 'd-none' ?>">
                    <?= $notifCount > 99 ? '99+' : (int) $notifCount ?>
                </span>
            </a>
            <?= Html::beginForm(['/site/change-language'], 'post', ['class' => 'd-flex gap-2 me-2']) ?>
                <?= Html::dropDownList(
                    'lang',
                    Yii::$app->language,
                    ['es-CL' => 'Español', 'en-US' => 'English'],
                    ['class' => 'form-select form-select-sm']
                ) ?>
                <button class="btn btn-sm btn-outline-light" type="submit">Idioma</button>
            <?= Html::endForm() ?>
            <span class="ts-username">
                <?= Html::encode(property_exists($usuario, 'username') ? $usuario->username : '') ?>
            </span>
            <?= Html::beginForm(Url::to(['/logout']), 'post') ?>
                <?= Html::submitButton('↩ ' . Yii::t('app', 'Cerrar sesion'), ['class' => 'btn btn-sm btn-outline-light ms-2']) ?>
            <?= Html::endForm() ?>
        <?php endif ?>
    </div>
</header>

<!-- ── Sidebar ─────────────────────────────────────────── -->
<nav id="ts-sidebar" role="navigation" aria-label="Menú principal">
    <div class="ts-sidebar-brand">
        <div class="ts-sidebar-brand-copy">
            <small>Tu auto necesita TOMAKO</small>
        </div>
    </div>
    <?php
    $grupoAbierto = false;
    foreach ($menuItems as $item):
        if (!empty($item['grupo']) && !$grupoAbierto):
            echo '<hr class="ts-divider" style="margin:.5rem 0">';
            $grupoAbierto = true;
        endif;
        
        // Verificar si el item tiene submenu
        $tieneSubmenu = !empty($item['submenu']);
        $submenuActivo = false;
        
        if ($tieneSubmenu) {
            foreach ($item['submenu'] as $subitem) {
                if (str_starts_with($urlActual, \yii\helpers\Url::to($subitem['url']))) {
                    $submenuActivo = true;
                    break;
                }
            }
        }
        
        $activo = str_starts_with($urlActual, \yii\helpers\Url::to($item['url']));
        // Por defecto los submenus aparecen cerrados
        $mostrarSubmenu = false;
    ?>
        <?php if ($tieneSubmenu): ?>
            <div class="ts-nav-item ts-nav-group <?= ($activo || $submenuActivo) ? 'active' : '' ?>" 
                 onclick="toggleSubmenu('submenu-<?= md5($item['label']) ?>')">
                <span class="ts-nav-icon"><?= $item['icono'] ?></span>
                <span class="ts-nav-label"><?= Html::encode($item['label']) ?></span>
                <span class="ts-nav-arrow">▶</span>
            </div>
            <div class="ts-submenu" id="submenu-<?= md5($item['label']) ?>" style="display: none;">
                <?php foreach ($item['submenu'] as $subitem): ?>
                    <?php $subActivo = str_starts_with($urlActual, \yii\helpers\Url::to($subitem['url'])); ?>
                    <a href="<?= \yii\helpers\Url::to($subitem['url']) ?>"
                       class="ts-submenu-item <?= $subActivo ? 'active' : '' ?>">
                        <?= Html::encode($subitem['label']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <a href="<?= \yii\helpers\Url::to($item['url']) ?>"
               class="ts-nav-item <?= $activo ? 'active' : '' ?>"
               title="<?= Html::encode($item['label']) ?>">
                <span class="ts-nav-icon"><?= $item['icono'] ?></span>
                <span class="ts-nav-label"><?= Html::encode($item['label']) ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach ?>

<script>
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    if (submenu) {
        const isHidden = window.getComputedStyle(submenu).display === 'none';
        submenu.style.display = isHidden ? 'block' : 'none';
        // Toggle flecha
        const arrow = submenu.previousElementSibling?.querySelector('.ts-nav-arrow');
        if (arrow) {
            arrow.textContent = isHidden ? '▼' : '▶';
        }
        // Toggle clase active en el grupo padre
        const group = submenu.previousElementSibling;
        if (group) {
            group.classList.toggle('active', isHidden);
        }
    }
}
</script>

</nav>

<!-- ── Toast container ─────────────────────────────────── -->
<div id="ts-toast-container" aria-live="polite" aria-atomic="true">
    <?= FlashMessages::widget() ?>
</div>

<!-- ── Contenido principal ─────────────────────────────── -->
<main id="ts-main-content" role="main">
    <?php if (!empty($this->params['breadcrumbs'])): ?>
        <?= Breadcrumbs::widget([
            'links'         => $this->params['breadcrumbs'],
            'options'       => ['class' => 'mb-3'],
            'homeLink'      => ['label' => Yii::t('app', 'Inicio'), 'url' => Yii::$app->homeUrl],
        ]) ?>
    <?php endif ?>

    <?= $content ?>

    <?php
    $versionSistema = '1.0.0';
    if (class_exists(\app\models\ParametroSistema::class)) {
        try {
            $versionSistema = \app\models\ParametroSistema::getValor('sistema.version', $versionSistema);
        } catch (\Throwable) {
            // Si falla la carga/configuracion, mantener version por defecto sin romper el layout.
        }
    }
    ?>
    <footer id="ts-footer">
        &copy; <?= date('Y') ?> ID3.CL - TOMAKO &mdash; v<?= Html::encode((string) $versionSistema) ?>
    </footer>
</main>

<script>
(function () {
    var btn     = document.getElementById('ts-sidebar-toggle');
    var sidebar = document.getElementById('ts-sidebar');
    var main    = document.getElementById('ts-main-content');
    var key     = 'ts_sidebar_collapsed';
    var collapsed = localStorage.getItem(key) === '1';

    function aplicar() {
        if (collapsed) {
            sidebar.classList.add('collapsed');
            main.classList.add('sidebar-collapsed');
            btn.setAttribute('aria-expanded', 'false');
        } else {
            sidebar.classList.remove('collapsed');
            main.classList.remove('sidebar-collapsed');
            btn.setAttribute('aria-expanded', 'true');
        }
    }

    aplicar();

    btn.addEventListener('click', function () {
        if (window.innerWidth < 768) {
            sidebar.classList.toggle('open');
        } else {
            collapsed = !collapsed;
            localStorage.setItem(key, collapsed ? '1' : '0');
            aplicar();
        }
    });

    // Auto-cerrar toasts
    document.querySelectorAll('.ts-toast-close').forEach(function (btn) {
        btn.addEventListener('click', function () {
            btn.closest('.ts-toast').remove();
        });
    });
    document.querySelectorAll('.ts-toast[data-auto-close]').forEach(function (toast) {
        setTimeout(function () { toast.remove(); }, 5000);
    });

    var logoutForm = document.querySelector('form[action$="/logout"]');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function (e) {
            if (!window.confirm('¿Confirmar cierre de sesión?')) {
                e.preventDefault();
            }
        });
    }
}());
</script>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
