<?php
/**
 * Historial de Recordatorios - HU-019
 */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Historial de Recordatorios';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="recordatorio-cita-historial">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="card">
        <div class="card-body">
            <p class="text-muted text-center">
                El historial detallado de recordatorios enviados estará disponible próximamente.
                Por ahora, puede consultar el log de emails en:
            </p>
            <div class="text-center mt-3">
                <?= Html::a(
                    '<i class="fas fa-envelope-open-text"></i> Ver Log de Emails',
                    ['/notificacion/email-log'],
                    ['class' => 'btn btn-info']
                ) ?>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <?= Html::a('<i class="fas fa-arrow-left"></i> Volver', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>
</div>
