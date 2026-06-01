<?php
/**
 * Vista parcial: Galería de imágenes y captura de cámara para un ítem de inventario.
 * @var yii\web\View $this
 * @var app\models\InventoryItem $model
 * @var app\models\InventoryItemImage[] $imagenes
 * @var app\models\InventoryItemImage|null $imagenDefault
 */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$uploadUrl = Url::to(['inventario/upload-image', 'id' => $model->id]);
$deactivateUrl = Url::to(['inventario/deactivate-image']);
$setDefaultUrl = Url::to(['inventario/set-default-image']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
?>

<!-- Galería de Imágenes -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-images me-2"></i>Imágenes del Producto</strong>
        <span class="badge bg-secondary"><?= count($imagenes) ?> imagen(es)</span>
    </div>
    <div class="card-body">
        <?php if (!empty($imagenes)): ?>
            <!-- Imagen principal (predefinida) -->
            <div class="text-center mb-3" id="imagen-principal">
                <?php if ($imagenDefault): ?>
                    <img src="<?= $imagenDefault->getUrl() ?>" 
                         alt="<?= Html::encode($model->nombre) ?>"
                         class="img-fluid rounded shadow-sm"
                         style="max-height: 300px; object-fit: contain;"
                         id="img-principal"
                         data-image-id="<?= $imagenDefault->id ?>">
                <?php endif; ?>
            </div>

            <!-- Miniaturas -->
            <div class="d-flex flex-wrap gap-2 justify-content-center mb-3" id="galeria-miniaturas">
                <?php foreach ($imagenes as $img): ?>
                    <div class="position-relative <?= $img->is_default ? 'border border-2 border-primary rounded' : '' ?>" 
                         style="width: 80px; height: 80px;"
                         id="thumb-<?= $img->id ?>">
                        <img src="<?= $img->getUrl() ?>" 
                             alt="Imagen <?= $img->id ?>"
                             class="img-thumbnail w-100 h-100"
                             style="object-fit: cover; cursor: pointer;"
                             onclick="cambiarImagenPrincipal('<?= $img->getUrl() ?>', <?= $img->id ?>)"
                             loading="lazy">
                        
                        <?php if ($img->is_default): ?>
                            <span class="position-absolute top-0 start-0 badge bg-primary" style="font-size: 0.6rem;">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php endif; ?>

                        <!-- Botones de acción -->
                        <div class="position-absolute top-0 end-0 d-flex gap-0">
                            <?php if (!$img->is_default): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-warning p-0 px-1" 
                                        style="font-size: 0.6rem;"
                                        title="Marcar como predefinida"
                                        onclick="setDefaultImage(<?= $img->id ?>)">
                                    <i class="bi bi-star"></i>
                                </button>
                            <?php endif; ?>
                            <button type="button" 
                                    class="btn btn-sm btn-danger p-0 px-1" 
                                    style="font-size: 0.6rem;"
                                    title="Dar de baja"
                                    onclick="deactivateImage(<?= $img->id ?>)">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-image" style="font-size: 3rem;"></i>
                <p class="mt-2">No hay imágenes registradas para este producto.</p>
            </div>
        <?php endif; ?>

        <!-- Formulario de subida de archivos -->
        <hr>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-dashed">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-upload me-1"></i>Subir desde archivo</h6>
                        <?php $form = ActiveForm::begin([
                            'action' => ['inventario/upload-image', 'id' => $model->id],
                            'method' => 'post',
                            'options' => ['enctype' => 'multipart/form-data', 'id' => 'upload-form'],
                        ]); ?>
                            <div class="mb-2">
                                <input type="file" 
                                       name="image_files[]" 
                                       class="form-control form-control-sm" 
                                       accept="image/*" 
                                       multiple
                                       id="file-input"
                                       onchange="previewFiles(this)">
                            </div>
                            <div id="file-preview" class="d-flex flex-wrap gap-1 mb-2 justify-content-center"></div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100" id="btn-upload" style="display:none;">
                                <i class="bi bi-cloud-upload me-1"></i>Subir Imágenes
                            </button>
                        <?php ActiveForm::end(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-dashed">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-camera me-1"></i>Capturar con cámara</h6>
                        <p class="text-muted small mb-2">Usa la cámara de tu teléfono o computador</p>
                        
                        <div id="camera-container" style="display:none;">
                            <video id="camera-preview" autoplay playsinline 
                                   class="img-fluid rounded mb-2"
                                   style="max-height: 200px;"></video>
                            <canvas id="camera-canvas" style="display:none;"></canvas>
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-success btn-sm" onclick="capturePhoto()">
                                    <i class="bi bi-camera-fill me-1"></i>Capturar
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="stopCamera()">
                                    <i class="bi bi-x-circle me-1"></i>Cancelar
                                </button>
                            </div>
                        </div>
                        <div id="captured-preview" style="display:none;">
                            <img id="captured-image" class="img-fluid rounded mb-2" style="max-height: 200px;">
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-primary btn-sm" onclick="uploadCapturedPhoto()">
                                    <i class="bi bi-check-circle me-1"></i>Guardar
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="retakePhoto()">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Tomar otra
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm w-100" id="btn-camera" onclick="startCamera()">
                            <i class="bi bi-camera-video me-1"></i>Abrir Cámara
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formularios ocultos para acciones -->
<form id="form-deactivate-image" method="POST" style="display:none;">
    <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
</form>
<form id="form-set-default-image" method="POST" style="display:none;">
    <input type="hidden" name="<?= $csrfParam ?>" value="<?= $csrfToken ?>">
</form>

<?php
$js = <<<JS
function cambiarImagenPrincipal(url, id) {
    var img = document.getElementById('img-principal');
    if (img) { img.src = url; img.dataset.imageId = id; }
    document.querySelectorAll('#galeria-miniaturas > div').forEach(function(el) {
        el.classList.remove('border', 'border-2', 'border-primary');
    });
    var thumb = document.getElementById('thumb-' + id);
    if (thumb) { thumb.classList.add('border', 'border-2', 'border-primary'); }
}

function deactivateImage(imageId) {
    if (!confirm('¿Dar de baja esta imagen?')) return;
    var form = document.getElementById('form-deactivate-image');
    form.action = '{$deactivateUrl}?imageId=' + imageId;
    form.submit();
}

function setDefaultImage(imageId) {
    var form = document.getElementById('form-set-default-image');
    form.action = '{$setDefaultUrl}?imageId=' + imageId;
    form.submit();
}

function previewFiles(input) {
    var preview = document.getElementById('file-preview');
    var btn = document.getElementById('btn-upload');
    preview.innerHTML = '';
    if (input.files.length > 0) {
        btn.style.display = 'block';
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width:50px;height:50px;object-fit:cover;border-radius:4px;';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        }
    } else {
        btn.style.display = 'none';
    }
}

var cameraStream = null;

async function startCamera() {
    try {
        var constraints = { video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } } };
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        var video = document.getElementById('camera-preview');
        video.srcObject = cameraStream;
        document.getElementById('camera-container').style.display = 'block';
        document.getElementById('btn-camera').style.display = 'none';
    } catch (err) {
        alert('No se pudo acceder a la camara: ' + err.message);
    }
}

function stopCamera() {
    if (cameraStream) { cameraStream.getTracks().forEach(function(t) { t.stop(); }); cameraStream = null; }
    document.getElementById('camera-container').style.display = 'none';
    document.getElementById('captured-preview').style.display = 'none';
    document.getElementById('btn-camera').style.display = 'block';
}

function capturePhoto() {
    var video = document.getElementById('camera-preview');
    var canvas = document.getElementById('camera-canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    document.getElementById('captured-image').src = canvas.toDataURL('image/jpeg', 0.85);
    document.getElementById('camera-container').style.display = 'none';
    document.getElementById('captured-preview').style.display = 'block';
}

function retakePhoto() {
    document.getElementById('captured-preview').style.display = 'none';
    document.getElementById('camera-container').style.display = 'block';
}

async function uploadCapturedPhoto() {
    var canvas = document.getElementById('camera-canvas');
    var dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    var formData = new FormData();
    formData.append('base64_image', dataUrl);
    formData.append('{$csrfParam}', '{$csrfToken}');
    try {
        var response = await fetch('{$uploadUrl}', { method: 'POST', body: formData });
        if (response.ok) { window.location.reload(); }
        else { alert('Error al subir la imagen.'); }
    } catch (err) { alert('Error de red: ' + err.message); }
}

window.addEventListener('beforeunload', stopCamera);
JS;
$this->registerJs($js);
?>