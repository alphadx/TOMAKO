<?php
/** @var yii\web\View $this */
/** @var string|null $error */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Cambiar Contraseña';
$this->params['breadcrumbs'][] = ['label' => 'Mi Perfil', 'url' => ['profile']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="usuario-change-password">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-key me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">

                    <?php if ($error !== null): ?>
                        <div class="alert alert-danger d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div><?= Html::encode($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?= Url::to(['change-password']) ?>">
                        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Contraseña Actual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control" required placeholder="Ingrese su contraseña actual">
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">Nueva Contraseña</label>
                            <input type="password" id="new_password" name="new_password"
                                   class="form-control" required minlength="10"
                                   placeholder="Mínimo 10 caracteres">
                            <div class="form-text">La contraseña debe tener al menos 10 caracteres.</div>
                        </div>

                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirmar Nueva Contraseña</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-control" required minlength="10"
                                   placeholder="Repita la nueva contraseña">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-floppy me-1"></i>Cambiar Contraseña
                            </button>
                            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Cancelar', ['profile'], ['class' => 'btn btn-secondary']) ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
