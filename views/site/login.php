<?php
/** @var yii\web\View $this */
/** @var string $email */
/** @var bool $rememberMe */
/** @var string|null $error */
/** @var int $blockedRemaining */
/** @var bool $systemOnline */

use yii\helpers\Html;

$this->title = 'Iniciar Sesión';
$this->context->layout = 'login';
?>

<div class="site-login d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-sm" style="width: 100%; max-width: 420px;">
        <div class="card-body p-4 p-md-5">
            <?php if (Yii::$app->session->hasFlash('warning')): ?>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-clock-history me-2"></i>
                    <div><?= Html::encode((string) Yii::$app->session->getFlash('warning')) ?></div>
                </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <h1 class="h3 fw-bold text-primary">TOMAKO</h1>
                <p class="text-muted small">Ingrese sus credenciales para continuar</p>
                <p class="small mb-0">
                    Estado del Sistema:
                    <span class="badge <?= $systemOnline ? 'bg-success' : 'bg-danger' ?>">
                        <?= $systemOnline ? 'Online' : 'Offline' ?>
                    </span>
                </p>
            </div>

            <?php if ($error !== null): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= Html::encode($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($blockedRemaining > 0): ?>
                <div class="alert alert-warning py-2 small" role="alert">
                    Bloqueo temporal activo. Tiempo restante: <strong><?= (int) $blockedRemaining ?>s</strong>
                </div>
            <?php endif; ?>

            <form id="login-form" method="post" action="<?= yii\helpers\Url::to(['site/login']) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Correo Electrónico</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?= Html::encode($email) ?>"
                        placeholder="correo@ejemplo.cl"
                        required
                        autofocus
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="mb-4 form-check">
                    <input
                        type="checkbox"
                        id="rememberMe"
                        name="rememberMe"
                        class="form-check-input"
                        value="1"
                        <?= $rememberMe ? 'checked' : '' ?>
                    >
                    <label for="rememberMe" class="form-check-label">Recordarme por 7 días</label>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                    </button>
                </div>

                <div class="text-center mt-3">
                    <a href="<?= yii\helpers\Url::to(['site/request-password-reset']) ?>" class="small">¿Olvidó su contraseña?</a>
                </div>
            </form>
        </div>
    </div>
</div>
