<?php
/** @var yii\web\View $this */
/** @var string|null $error */
/** @var string|null $success */

use yii\helpers\Html;

$this->title = 'Recuperar contraseña';
?>

<div class="card shadow-sm" style="max-width: 420px; margin: 0 auto;">
    <div class="card-body p-4">
        <h1 class="h4 mb-3 text-center">Recuperar contraseña</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger"><?= Html::encode($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <div class="alert alert-success"><?= Html::encode($success) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= yii\helpers\Url::to(['site/request-password-reset']) ?>">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

            <div class="mb-3">
                <label for="email" class="form-label">Correo electrónico</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary" type="submit">Enviar enlace</button>
                <a class="btn btn-outline-secondary" href="<?= yii\helpers\Url::to(['site/login']) ?>">Volver al login</a>
            </div>
        </form>
    </div>
</div>
