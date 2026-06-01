<?php
/**
 * Vista: Escanear codigo QR con la camara del telefono.
 * Utiliza jsQR desde CDN para decodificar QR desde video.
 *
 * @var yii\web\View $this
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Escanear QR';
$this->params['breadcrumbs'][] = ['label' => 'Inventario', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$searchUrl = Url::to(['inventario/qr-search']);
?>

<div class="inventario-qr-scan">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-qr-code-scan me-2"></i><?= Html::encode($this->title) ?></h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white text-center">
                    <strong><i class="bi bi-camera-video me-2"></i>Escáner QR</strong>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted mb-3">Apunta la camara del telefono hacia el codigo QR del producto.</p>

                    <div id="scanner-container" class="mb-3" style="position:relative;display:inline-block;">
                        <video id="qr-video" autoplay playsinline
                               style="width:100%;max-width:400px;border-radius:8px;border:2px solid #dee2e6;"></video>
                        <canvas id="qr-canvas" style="display:none;"></canvas>
                        <div id="scan-overlay" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:200px;height:200px;border:2px solid rgba(255,255,255,0.7);border-radius:12px;box-shadow:0 0 0 9999px rgba(0,0,0,0.3);display:none;">
                            <div id="scan-line" style="position:absolute;top:0;left:0;right:0;height:2px;background:#0d6efd;animation:scanAnim 2s infinite;"></div>
                        </div>
                    </div>

                    <div id="scan-status" class="mb-3">
                        <div id="status-ready" class="text-muted"><i class="bi bi-info-circle me-1"></i>Presiona "Iniciar Escaner" para comenzar.</div>
                        <div id="status-scanning" style="display:none;"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div><span class="text-primary">Buscando codigo QR...</span></div>
                        <div id="status-found" style="display:none;"><i class="bi bi-check-circle-fill text-success me-1"></i><span class="text-success">QR encontrado! Redirigiendo...</span></div>
                        <div id="status-error" style="display:none;" class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i><span id="error-message"></span></div>
                    </div>

                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-primary" id="btn-start" onclick="startQrScanner()"><i class="bi bi-camera-video me-1"></i>Iniciar Escaner</button>
                        <button type="button" class="btn btn-danger" id="btn-stop" onclick="stopQrScanner()" style="display:none;"><i class="bi bi-stop-circle me-1"></i>Detener</button>
                    </div>

                    <hr>
                    <div class="mt-3">
                        <p class="text-muted small mb-2">No funciona la camara? Ingresa el codigo manualmente:</p>
                        <form action="<?= $searchUrl ?>" method="GET" class="d-flex gap-2">
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Ingresa codigo QR o SKU...">
                            <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i> Buscar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes scanAnim { 0%,100%{top:0} 50%{top:calc(100% - 2px)} }
</style>

<?php
$this->registerJsFile('https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js', [
    'position' => \yii\web\View::POS_HEAD,
]);

$js = <<<JS
var qrStream = null;
var qrAnimFrame = null;

async function startQrScanner() {
    try {
        qrStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment',width:{ideal:1280},height:{ideal:720}}});
        var video = document.getElementById('qr-video');
        video.srcObject = qrStream;
        document.getElementById('btn-start').style.display = 'none';
        document.getElementById('btn-stop').style.display = 'inline-block';
        document.getElementById('scan-overlay').style.display = 'block';
        document.getElementById('status-ready').style.display = 'none';
        document.getElementById('status-scanning').style.display = 'block';
        document.getElementById('status-error').style.display = 'none';
        video.onloadedmetadata = function() { scanFrame(); };
    } catch (err) {
        showError('No se pudo acceder a la camara: ' + err.message);
    }
}

function scanFrame() {
    var video = document.getElementById('qr-video');
    var canvas = document.getElementById('qr-canvas');
    var ctx = canvas.getContext('2d', {willReadFrequently:true});
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        if (typeof jsQR !== 'undefined') {
            var code = jsQR(imgData.data, imgData.width, imgData.height, {inversionAttempts:'dontInvert'});
            if (code) {
                document.getElementById('status-scanning').style.display = 'none';
                document.getElementById('status-found').style.display = 'block';
                stopQrScanner();
                window.location.href = '{$searchUrl}?q=' + encodeURIComponent(code.data);
                return;
            }
        }
    }
    qrAnimFrame = requestAnimationFrame(scanFrame);
}

function stopQrScanner() {
    if (qrAnimFrame) { cancelAnimationFrame(qrAnimFrame); qrAnimFrame = null; }
    if (qrStream) { qrStream.getTracks().forEach(function(t){t.stop()}); qrStream = null; }
    document.getElementById('btn-start').style.display = 'inline-block';
    document.getElementById('btn-stop').style.display = 'none';
    document.getElementById('scan-overlay').style.display = 'none';
    document.getElementById('qr-video').srcObject = null;
}

function showError(msg) {
    document.getElementById('status-scanning').style.display = 'none';
    document.getElementById('status-error').style.display = 'block';
    document.getElementById('error-message').textContent = msg;
    document.getElementById('btn-start').style.display = 'inline-block';
    document.getElementById('btn-stop').style.display = 'none';
}

window.addEventListener('beforeunload', stopQrScanner);
JS;
$this->registerJs($js);
?>