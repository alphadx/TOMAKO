<?php
declare(strict_types=1);

use app\models\OrdenEstadoLog;

/** @var app\models\OrdenServicio $model */

$logs = OrdenEstadoLog::find()
    ->where(['orden_id' => $model->id])
    ->orderBy(['created_at' => SORT_DESC])
    ->all();
?>

<?php if (empty($logs)): ?>
    <p class="text-muted">Sin cambios de estado</p>
<?php else: ?>
    <div class="timeline">
        <?php foreach ($logs as $log): ?>
            <div class="timeline-item mb-3 p-2" style="border-left: 3px solid #007bff; padding-left: 15px;">
                <strong><?= htmlspecialchars($log->usuario->nombre_completo ?? 'Sistema') ?></strong>
                <small class="text-muted"> - <?= date('d/m/Y H:i', $log->created_at) ?></small>
                <p class="mb-1">
                    <?= $log->estado_anterior ? '<strong>' . htmlspecialchars($log->estado_anterior) . '</strong>' : 'Creación' ?>
                    → 
                    <strong><?= htmlspecialchars($log->estado_nuevo) ?></strong>
                </p>
                <?php if ($log->comentario): ?>
                    <small class="text-muted"><em><?= htmlspecialchars($log->comentario) ?></em></small>
                <?php endif ?>
            </div>
        <?php endforeach ?>
    </div>
<?php endif ?>
