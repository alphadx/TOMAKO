<?php

declare(strict_types=1);

/**
 * Layout para pantalla de login y acceso público.
 * Incluye sidebar restringido para consistencia visual.
 *
 * @var yii\web\View $this
 * @var string       $content
 */

use app\assets\AppAsset;
use yii\bootstrap5\Html;

AppAsset::register($this);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1, shrink-to-fit=no']);
$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/x-icon', 'href' => Yii::getAlias('@web/favicon.ico')]);
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
        body {
            font-family: 'Inter', system-ui, sans-serif;
            background:
                radial-gradient(circle at 10% 15%, hsl(46, 55%, 94%), transparent 45%),
                radial-gradient(circle at 88% 82%, hsl(198, 40%, 91%), transparent 48%),
                hsl(36, 24%, 96%);
            min-height: 100vh;
            margin: 0;
            color: hsl(220, 16%, 18%);
        }
        .ts-login-page {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }
        .ts-login-sidebar {
            background: hsla(40, 30%, 98%, .88);
            border-right: 1px solid hsl(36, 16%, 84%);
            color: hsl(220, 14%, 26%);
            backdrop-filter: blur(6px);
            padding: 2rem 1.25rem;
        }
        .ts-login-sidebar-brand {
            text-align: center;
            margin-bottom: 1rem;
        }
        .ts-login-sidebar-brand img {
            width: 92px;
            height: 92px;
            object-fit: contain;
            filter: drop-shadow(0 8px 18px rgba(122, 82, 48, .2));
        }
        .ts-login-sidebar-brand strong {
            display: block;
            margin-top: .55rem;
            font-family: 'Montserrat', sans-serif;
            letter-spacing: .1em;
            font-weight: 700;
            font-size: .98rem;
            color: hsl(210, 18%, 20%);
        }
        .ts-login-sidebar-brand small {
            display: block;
            margin-top: .25rem;
            font-size: .72rem;
            color: hsl(210, 12%, 36%);
        }
        .ts-login-sidebar a {
            color: hsl(220, 14%, 24%);
            text-decoration: none;
            display: block;
            padding: .5rem .75rem;
            border-radius: .5rem;
            margin-bottom: .35rem;
            border: 1px solid transparent;
        }
        .ts-login-sidebar a:hover {
            background: hsl(42, 42%, 96%);
            border-color: hsl(35, 20%, 82%);
        }
        .ts-login-content {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .ts-login-wrapper {
            width: 100%;
            max-width: 420px;
        }
        .ts-login-card {
            background: hsla(0, 0%, 100%, .82);
            border: 1px solid hsl(35, 20%, 82%);
            border-radius: 0.75rem;
            box-shadow: 0 10px 35px rgba(34, 34, 34, .08);
            backdrop-filter: blur(4px);
            padding: 2.5rem 2rem;
        }
        .ts-login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .ts-login-brand img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            filter: drop-shadow(0 12px 24px rgba(122, 82, 48, .16));
        }
        .ts-login-brand h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.95rem;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: .1em;
            color: hsl(210, 18%, 18%);
            margin: 0.5rem 0 0;
        }
        .ts-login-brand h1 span { color: hsl(354, 78%, 56%); }
        .ts-login-brand p {
            font-family: 'Montserrat', sans-serif;
            color: hsl(32, 46%, 34%);
            font-size: 0.9rem;
            margin: 0;
        }
        @media (max-width: 900px) {
            .ts-login-page {
                grid-template-columns: 1fr;
            }
            .ts-login-sidebar {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<?php $this->beginBody() ?>

<div class="ts-login-page">
    <aside class="ts-login-sidebar" aria-label="Navegación restringida">
        <div class="ts-login-sidebar-brand">
            <img src="<?= Yii::getAlias('@web/logo.png') ?>" alt="Isotipo TOMAKO">
            <strong>TOMAKO</strong>
            <small>Tu auto necesita TOMAKO 🍅</small>
        </div>
        <p style="font-size:.85rem; opacity:.8; margin-bottom:1rem;">Navegación básica disponible sin autenticación.</p>
        <a href="<?= yii\helpers\Url::to(['/site/login']) ?>">Iniciar sesión</a>
        <a href="<?= yii\helpers\Url::to(['/site/request-password-reset']) ?>">Recuperar contraseña</a>
        <a href="<?= yii\helpers\Url::to(['/site/about']) ?>">Acerca de</a>
    </aside>

    <div class="ts-login-content">
        <div class="ts-login-wrapper">
            <div class="ts-login-card">
                <div class="ts-login-brand">
                    <img src="<?= Yii::getAlias('@web/logo.png') ?>" alt="Logo TOMAKO">
                    <h1>TO<span>MAKO</span></h1>
                    <p>Tu auto necesita TOMAKO 🍅</p>
                </div>

                <?= $content ?>
            </div>

            <p style="text-align:center;color:hsl(220, 10%, 40%);font-size:.75rem;margin-top:1rem;">
                &copy; <?= date('Y') ?> TOMAKO
            </p>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
