<?php
/** @var yii\web\View $this */
/** @var string $token */
/** @var string|null $error */
/** @var string|null $success */

use yii\helpers\Html;

$this->title = 'Restablecer contraseña';
?>

<div class="card shadow-sm" style="max-width: 420px; margin: 0 auto;">
    <div class="card-body p-4">
        <h1 class="h4 mb-3 text-center">Restablecer contraseña</h1>

        <?php if ($error !== null): ?>
            <div class="alert alert-danger"><?= Html::encode($error) ?></div>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <div class="alert alert-success">
                <?= Html::encode($success) ?>
                <div class="mt-2">
                    <a href="<?= yii\helpers\Url::to(['site/login']) ?>" class="btn btn-sm btn-outline-primary">Ir al login</a>
                </div>
            </div>
        <?php else: ?>
            <form method="post" action="<?= yii\helpers\Url::to(['site/reset-password', 'token' => $token]) ?>">
                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                <div class="mb-3">
                    <label for="new_password" class="form-label">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" minlength="10" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" minlength="10" required>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Guardar contraseña</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
