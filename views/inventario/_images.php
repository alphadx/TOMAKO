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
                    <div class="position-relative <?= ($imagenDefault && $img->id === $imagenDefault->id) ? 'border border-2 border-primary rounded' : '' ?>" 
                         style="width: 80px; height: 80px;"
                         id="thumb-<?= $img->id ?>">
                        <img src="<?= $img->getUrl() ?>" 
                             alt="Imagen <?= $img->id ?>"
                             class="img-thumbnail w-100 h-100"
                             style="object-fit: cover; cursor: pointer;"
                             onclick="cambiarImagenPrincipal('<?= $img->getUrl() ?>', <?= $img->id ?>)"
                             loading="lazy">
                        
                        <?php if ($imagenDefault && $img->id === $imagenDefault->id): ?>
                            <span class="position-absolute top-0 start-0 badge bg-primary" style="font-size: 0.6rem;">
                                <i class="bi bi-star-fill"></i>
                            </span>
                        <?php endif; ?>

                        <!-- Botones de acción -->
                        <div class="position-absolute top-0 end-0 d-flex gap-0">
                            <?php if (!$imagenDefault || $img->id !== $imagenDefault->id): ?>
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

        <!-- Formulario unificado de subida -->
        <hr>
        <?php $form = ActiveForm::begin([
            'action' => ['inventario/upload-image', 'id' => $model->id],
            'method' => 'post',
            'options' => ['enctype' => 'multipart/form-data', 'id' => 'upload-form'],
        ]); ?>
            <div class="text-center">
                <label for="file-input" class="btn btn-outline-primary btn-sm mb-2" style="cursor:pointer;">
                    <i class="bi bi-camera me-1"></i>Seleccionar o tomar foto(s)
                </label>
                <input type="file"
                       name="image_files[]"
                       class="d-none"
                       accept="image/*"
                       multiple
                       id="file-input"
                       onchange="previewFiles(this)">
                <div id="file-preview" class="d-flex flex-wrap gap-2 mb-2 justify-content-center"></div>
                <button type="submit" class="btn btn-primary btn-sm" id="btn-upload" style="display:none;">
                    <i class="bi bi-cloud-upload me-1"></i>Subir Imágenes
                </button>
            </div>
        <?php ActiveForm::end(); ?>
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

JS;
$this->registerJs($js);
?>