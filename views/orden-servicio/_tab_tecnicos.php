<?php
declare(strict_types=1);

use yii\helpers\Html;

/** @var app\models\OrdenServicio $model */
?>

<?php if (empty($model->asignaciones)): ?>
    <p class="text-muted">Sin técnicos asignados</p>
<?php else: ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Técnico</th>
                <th>Especialidad</th>
                <th>Asignado</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($model->asignaciones as $asignacion): ?>
                <tr>
                    <td><?= Html::encode($asignacion->tecnico->nombre ?? 'N/A') ?></td>
                    <td><?= Html::encode($asignacion->tecnico->especialidad->nombre ?? 'N/A') ?></td>
                    <td><?= $asignacion->asignado_at ? date('d/m/Y H:i', $asignacion->asignado_at) : 'N/A' ?></td>
                    <td class="text-end">
                        <?= Html::a('<i class="bi bi-x"></i>', '#', ['class' => 'btn btn-sm btn-danger']) ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endif ?>
