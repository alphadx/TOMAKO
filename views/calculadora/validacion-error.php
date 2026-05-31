<?php
/**
 * Vista para mostrar errores en la validación de cotización.
 * 
 * @var string $titulo
 * @var string $mensaje
 */

use yii\helpers\Html;

$this->title = 'Error de Validación';
?>

<div class="validacion-error">
    <div class="alert alert-danger" style="text-align: center; padding: 40px; margin-top: 50px;">
        <h1 style="color: #d9534f; margin-bottom: 20px;">
            <i class="glyphicon glyphicon-remove-circle" style="font-size: 64px;"></i>
        </h1>
        <h2 style="margin-bottom: 20px;"><?= Html::encode($titulo) ?></h2>
        <p style="font-size: 18px; color: #666; margin-bottom: 30px;">
            <?= Html::encode($mensaje) ?>
        </p>
        
        <div style="margin-top: 30px;">
            <?= Html::a(
                '<i class="glyphicon glyphicon-home"></i> Ir al Inicio',
                ['/site/index'],
                ['class' => 'btn btn-primary btn-lg']
            ) ?>
        </div>
    </div>

    <div class="panel panel-warning" style="margin-top: 30px;">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="glyphicon glyphicon-info-sign"></i> Información Importante
            </h3>
        </div>
        <div class="panel-body">
            <p><strong>Posibles causas de este error:</strong></p>
            <ul>
                <li>El código QR ha sido escaneado incorrectamente o está dañado.</li>
                <li>La cotización ha expirado (tiene una fecha de vencimiento).</li>
                <li>El enlace de validación ha sido modificado manualmente.</li>
                <li>La cotización ya fue utilizada previamente (si está configurado como de un solo uso).</li>
                <li>La cotización no existe en nuestro sistema.</li>
            </ul>
            
            <p style="margin-top: 20px;">
                <strong>Recomendación:</strong> Si usted es el cliente, contacte a la empresa que emitió 
                la cotización para verificar su autenticidad. Si usted es el emisor, verifique que el 
                código QR se haya generado correctamente.
            </p>
        </div>
    </div>
</div>

<style>
.validacion-error {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}

.alert-danger {
    border: 2px solid #d9534f;
    background-color: #fdf7f7;
}

@media print {
    .btn, .panel-warning {
        display: none !important;
    }
}
</style>
