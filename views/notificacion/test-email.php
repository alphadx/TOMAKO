<?php
/** @var yii\web\View $this */
/** @var string $destinatario */
/** @var string $plantilla */
/** @var app\models\PlantillaNotificacion[] $plantillas */

use yii\helpers\Html;

$this->title = 'Test de Email';
$this->params['breadcrumbs'][] = ['label' => 'Notificaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => 'Plantillas', 'url' => ['plantillas']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-test-email" style="max-width:640px;">
    <h1 class="h4 mb-3"><?= Html::encode($this->title) ?></h1>

    <form method="post" class="card shadow-sm">
        <div class="card-body">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

            <div class="mb-3">
                <label class="form-label">Destinatario</label>
                <input type="email" name="destinatario" value="<?= Html::encode($destinatario) ?>" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Plantilla</label>
                <select name="plantilla" class="form-select" required>
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($plantillas as $p): ?>
                        <option value="<?= Html::encode($p->codigo) ?>" <?= $plantilla === $p->codigo ? 'selected' : '' ?>>
                            <?= Html::encode($p->codigo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Enviar prueba</button>
        </div>
    </form>
</div>
