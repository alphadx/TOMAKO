<?php
/**
 * Vista para mostrar el resultado exitoso de validación de cotización.
 * 
 * @var \app\models\CotizacionJwt $cotizacionJwt
 * @var array $payload
 * @var bool $esValido
 */

use yii\helpers\Html;

$this->title = 'Cotización Válida';
?>

<div class="validacion-exito">
    <div class="alert alert-success" style="text-align: center; padding: 30px;">
        <h1 style="color: #28a745; margin-bottom: 20px;">
            <i class="glyphicon glyphicon-ok-circle" style="font-size: 48px;"></i>
        </h1>
        <h2 style="margin-bottom: 15px;">¡Cotización Válida!</h2>
        <p style="font-size: 18px; color: #666;">
            La cotización ha sido verificada correctamente y su firma es auténtica.
        </p>
    </div>

    <div class="panel panel-default" style="margin-top: 30px;">
        <div class="panel-heading" style="background-color: #28a745; color: white;">
            <h3 class="panel-title" style="font-size: 18px;">
                <i class="glyphicon glyphicon-file"></i> Información de la Cotización
            </h3>
        </div>
        <div class="panel-body">
            <?php if (isset($payload['data'])): ?>
                <?php
                // Normalizar claves del payload (soportar tanto '_' como '/')
                $data = $payload['data'];
                $normalizedData = [];
                foreach ($data as $key => $value) {
                    $normalizedKey = str_replace('/', '_', $key);
                    $normalizedData[$normalizedKey] = $value;
                }
                ?>
                <table class="table table-striped" style="margin-bottom: 0;">
                    <tbody>
                        <?php if (!empty($normalizedData['cliente_nombre'])): ?>
                            <tr>
                                <th style="width: 30%;">Cliente:</th>
                                <td><?= Html::encode($normalizedData['cliente_nombre']) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($normalizedData['cliente_rut'])): ?>
                            <tr>
                                <th>RUT:</th>
                                <td><?= Html::encode($normalizedData['cliente_rut']) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php if (!empty($normalizedData['vehiculo_patente'])): ?>
                            <tr>
                                <th>Vehículo:</th>
                                <td><?= Html::encode($normalizedData['vehiculo_patente']) ?></td>
                            </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <th>Monto Total:</th>
                            <td style="font-size: 20px; font-weight: bold; color: #28a745;">
$<?= number_format((float)($normalizedData['monto_total'] ?? 0), 0, ',', '.') ?>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Fecha de Emisión:</th>
                            <td><?= date('d/m/Y H:i', $payload['iat']) ?></td>
                        </tr>
                        
                        <tr>
                            <th>Fecha de Vencimiento:</th>
                            <td><?= date('d/m/Y', $payload['exp']) ?></td>
                        </tr>
                        
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <?php if ($payload['exp'] > time()): ?>
                                    <span class="label label-success" style="font-size: 14px;">Vigente</span>
                                <?php else: ?>
                                    <span class="label label-danger" style="font-size: 14px;">Expirado</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No se pudo decodificar la información detallada de la cotización.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel panel-info" style="margin-top: 20px;">
        <div class="panel-heading">
            <h3 class="panel-title">
                <i class="glyphicon glyphicon-lock"></i> Detalles Técnicos de Validación
            </h3>
        </div>
        <div class="panel-body">
            <table class="table table-condensed" style="margin-bottom: 0;">
                <tbody>
                    <tr>
                        <th style="width: 30%;">Hash:</th>
                        <td><code><?= Html::encode($cotizacionJwt->hash) ?></code></td>
                    </tr>
                    <tr>
                        <th>URL Raíz:</th>
                        <td><?= Html::encode($cotizacionJwt->raiz_url) ?></td>
                    </tr>
                    <tr>
                        <th>Fecha de Registro:</th>
                        <td><?= date('d/m/Y H:i', $cotizacionJwt->created_at) ?></td>
                    </tr>
                    <tr>
                        <th>JWT Válido:</th>
                        <td><span class="label label-success">Sí</span></td>
                    </tr>
                    <tr>
                        <th>JWT Completo:</th>
                        <td><code style="word-break: break-all;"><?= Html::encode($cotizacionJwt->jwt) ?></code></td>
                    </tr>
                    <tr>
                        <th>Payload Decodificado:</th>
                        <td><pre style="background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto;"><?= json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center" style="margin-top: 30px;">
        <?= Html::a(
            '<i class="glyphicon glyphicon-print"></i> Imprimir Validación',
            ['javascript:window.print()'],
            [
                'class' => 'btn btn-default',
                'onclick' => 'window.print(); return false;',
            ]
        ) ?>
        <?= Html::a(
            '<i class="glyphicon glyphicon-arrow-left"></i> Volver',
            ['/site/index'],
            ['class' => 'btn btn-primary']
        ) ?>
    </div>
</div>

<style>
.validacion-exito {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

@media print {
    .btn, .panel-heading {
        display: none !important;
    }
    
    .panel {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
    
    .alert {
        border: 1px solid #28a745 !important;
    }
}
</style>
