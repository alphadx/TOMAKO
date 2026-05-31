<?php
/**
 * Vista: Imprimir Cotización con JWT y QR (HU-027)
 * 
 * @var yii\web\View $this
 * @var array $itemsCotizacion
 * @var float $totalNeto
 * @var float $tasaIva
 * @var float $montoIva
 * @var float $totalFinal
 * @var int $duracionTotal
 * @var string $clienteNombre
 * @var string $clienteRut
 * @var string $vehiculoPatente
 * @var string $fechaCotizacion
 * @var string $fechaVencimiento
 * @var int $diasValidez
 * @var string $jwt
 * @var string $qrData
 */

use yii\helpers\Html;

$this->title = 'Cotización de Servicio';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Html::encode($this->title) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #333; padding: 15px; }
        .container { max-width: 750px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; background: #fff; }
        .header { text-align: center; border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 15px; }
        .header h1 { color: #2c3e50; font-size: 20pt; margin-bottom: 5px; }
        .header p { color: #7f8c8d; font-size: 9pt; }
        .qr-section { float: right; width: 100px; height: 100px; margin-left: 15px; }
        .info-cliente { background: #f8f9fa; padding: 12px; border-radius: 5px; margin-bottom: 15px; clear: both; }
        .info-cliente table { width: 100%; border-collapse: collapse; }
        .info-cliente td { padding: 4px 0; font-size: 10pt; }
        .info-cliente strong { color: #2c3e50; width: 110px; display: inline-block; }
        .tabla-precios { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 10pt; }
        .tabla-precios th, .tabla-precios td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
        .tabla-precios th { background: #f8f9fa; color: #2c3e50; font-weight: bold; }
        .tabla-precios td:last-child { text-align: right; }
        .tabla-precios .subtotal { background: #e9ecef; font-weight: bold; }
        .tabla-precios .descuento { color: #e74c3c; }
        .tabla-precios .neto { background: #d4edda; font-weight: bold; }
        .tabla-precios .iva { background: #fff3cd; font-weight: bold; }
        .tabla-precios .total { background: #2ecc71; color: #fff; font-size: 12pt; font-weight: bold; }
        .notas { background: #fff3cd; padding: 12px; border-radius: 5px; margin-top: 15px; font-size: 9pt; }
        .notas h3 { color: #856404; margin-bottom: 8px; font-size: 10pt; }
        .footer { margin-top: 20px; text-align: center; font-size: 8pt; color: #7f8c8d; border-top: 1px solid #eee; padding-top: 12px; }
        .jwt-info { background: #f0f0f0; padding: 8px; border-radius: 3px; font-size: 8pt; word-break: break-all; margin-top: 10px; }
        .btn-print { display: block; width: 180px; margin: 15px auto; padding: 10px 20px; background: #3498db; color: #fff; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold; cursor: pointer; border: none; }
        .btn-print:hover { background: #2980b9; }
        @media print {
            .btn-print, .jwt-raw { display: none; }
            body { padding: 0; }
            .container { border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">🖨️ Imprimir</button>
    
    <div class="container">
        <div class="header">
            <div style="overflow: hidden;">
                <div class="qr-section" id="qrcode"></div>
                <h1>COTIZACIÓN</h1>
                <p>Taller Automotriz - <?= $fechaCotizacion ?></p>
                <p style="color: #e74c3c; font-weight: bold;">Vence: <?= $fechaVencimiento ?> (<?= $diasValidez ?> días)</p>
            </div>
        </div>
        
        <?php if ($clienteNombre || $clienteRut || $vehiculoPatente): ?>
        <div class="info-cliente">
            <table>
                <?php if ($clienteNombre): ?>
                <tr><td><strong>Cliente:</strong></td><td><?= Html::encode($clienteNombre) ?></td></tr>
                <?php endif; ?>
                <?php if ($clienteRut): ?>
                <tr><td><strong>RUT:</strong></td><td><?= Html::encode($clienteRut) ?></td></tr>
                <?php endif; ?>
                <?php if ($vehiculoPatente): ?>
                <tr><td><strong>Vehículo:</strong></td><td><?= Html::encode($vehiculoPatente) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
        <?php endif; ?>
        
        <table class="tabla-precios">
            <tr><th>Servicio</th><th class="text-center">Cant.</th><th class="text-right">Total</th></tr>
            <?php foreach ($itemsCotizacion as $item): ?>
            <tr>
                <td>
                    <strong><?= Html::encode($item['servicio_nombre']) ?></strong><br>
                    <small style="color: #7f8c8d;">Cód: <?= Html::encode($item['servicio_codigo']) ?></small>
                </td>
                <td class="text-center"><?= $item['cantidad'] ?></td>
                <td class="text-right">$ <?= number_format($item['total_final'], 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="neto"><td colspan="2"><strong>NETO</strong></td><td class="text-right">$ <?= number_format($totalNeto, 2, ',', '.') ?></td></tr>
            <tr class="iva"><td colspan="2"><strong>IVA (<?= number_format($tasaIva, 2) ?>%)</strong></td><td class="text-right">$ <?= number_format($montoIva, 2, ',', '.') ?></td></tr>
            <tr class="total"><td colspan="2">TOTAL FINAL</td><td class="text-right">$ <?= number_format($totalFinal, 2, ',', '.') ?></td></tr>
        </table>
        
        <div style="text-align: center; margin: 15px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
            <strong>⏱️ Duración Estimada:</strong> <?= $duracionTotal ?> min (<?= round($duracionTotal / 60, 2) ?> hrs)
        </div>
        
        <div class="notas">
            <h3>📋 Notas:</h3>
            <ul style="margin-left: 20px; font-size: 9pt;">
                <li>Validez: <?= $diasValidez ?> días desde emisión.</li>
                <li>Precio final sujeto a diagnóstico técnico.</li>
                <li>QR contiene firma digital para validación del JWT.</li>
                <li>Para validar: reconstruya JWT con datos del documento y compare firma escaneada.</li>
            </ul>
        </div>
        
        <div class="jwt-info jwt-raw">
            <strong>Firma Digital (JWT):</strong><br>
            <?= Html::encode($jwt) ?>
        </div>
        
        <div class="footer">
            <p>Documento generado el <?= $fechaCotizacion ?> | Válido hasta <?= $fechaVencimiento ?></p>
            <p>Cotización respaldada por firma digital verificable vía QR</p>
        </div>
    </div>
    
    <script>
        var qrData = <?= json_encode($qrData) ?>;
        new QRCode(document.getElementById("qrcode"), {
            text: qrData,
            width: 90,
            height: 90,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    </script>
</body>
</html>
