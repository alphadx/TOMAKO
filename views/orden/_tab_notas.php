<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */
/** @var array|string $addNotaRoute */
/** @var bool $showAuthor */
/** @var bool $multiline */

use yii\helpers\Html;
use yii\helpers\Url;

$addNotaRoute = $addNotaRoute ?? ['agregar-nota', 'id' => $model->id];
$showAuthor = $showAuthor ?? false;
$multiline = $multiline ?? false;

?>
<div class="orden-notas-tab">
    <?php if (!empty($model->notas)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($model->notas as $nota): ?>
            <li class="list-group-item">
                <small class="text-muted d-block"><?= $nota->created_at ? date('d/m/Y H:i', $nota->created_at) : '' ?></small>
                <?php if ($showAuthor): ?>
                    <small class="text-muted d-block"><?= Html::encode($nota->usuario->nombre_completo ?? 'Sistema') ?></small>
                <?php endif; ?>
                <?= $multiline ? nl2br(Html::encode($nota->texto)) : Html::encode($nota->texto) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="p-3 text-muted text-center">Sin notas.</div>
    <?php endif; ?>

    <div class="p-2 border-top">
        <form method="post" action="<?= Url::to($addNotaRoute) ?>">
            <?= Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken) ?>
            <div class="input-group">
                <input type="text" name="texto" class="form-control form-control-sm" placeholder="Agregar nota..." required>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i></button>
            </div>
        </form>
    </div>
</div>
