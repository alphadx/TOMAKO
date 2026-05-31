<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\OrdenServicio $model */
?>

<?php if (empty($model->notas)): ?>
    <p class="text-muted">Sin notas</p>
<?php else: ?>
    <div class="timeline">
        <?php foreach ($model->notas as $nota): ?>
            <div class="timeline-item mb-3 p-2 border-bottom">
                <strong><?= Html::encode($nota->usuario->nombre_completo ?? 'Sistema') ?></strong>
                <small class="text-muted"> - <?= date('d/m/Y H:i', $nota->created_at) ?></small>
                <p><?= nl2br(Html::encode($nota->texto)) ?></p>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>

<form method="post" action="/orden-servicio/agregar-nota?id=<?= $model->id ?>" id="formNota" class="mt-3">
    <textarea name="texto" class="form-control mb-2" placeholder="Agregar nota..." rows="3"></textarea>
    <button type="submit" class="btn btn-primary">Agregar Nota</button>
</form>
