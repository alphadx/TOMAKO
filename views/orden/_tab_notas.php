<?php
/** @var yii\web\View $this */
/** @var app\models\OrdenServicio $model */

use yii\helpers\Html;

?>
<div class="orden-notas-tab">
    <?php if (!empty($model->notas)): ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($model->notas as $nota): ?>
            <li class="list-group-item">
                <small class="text-muted d-block"><?= $nota->created_at ? date('d/m/Y H:i', $nota->created_at) : '' ?></small>
                <?= Html::encode($nota->texto) ?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="p-3 text-muted text-center">Sin notas.</div>
    <?php endif; ?>

    <div class="p-2 border-top">
        <form method="post" action="<?= \yii\helpers\Url::to(['agregar-nota', 'id' => $model->id]) ?>">
            <?= \yii\helpers\Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken) ?>
            <div class="input-group">
                <input type="text" name="texto" class="form-control form-control-sm" placeholder="Agregar nota..." required>
                <button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-send"></i></button>
            </div>
        </form>
    </div>
</div>
