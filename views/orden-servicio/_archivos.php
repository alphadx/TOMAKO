<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;
use app\models\OrdenServicio;
use app\models\OrdenServicioArchivo;

/**
 * Vista de gestión de archivos adjuntos para órdenes de servicio
 * HU-004: Adjuntar Fotos y Documentos a Órdenes
 * 
 * @var yii\web\View $this
 * @var OrdenServicio $model
 */

$this->title = 'Archivos Adjuntos - ' . $model->codigo;
?>

<div class="archivos-adjuntos-manager" data-orden-id="<?= $model->id ?>">
    <!-- Header con contadores -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3 class="mb-0" id="contador-fotos"><?= count(array_filter($model->archivos, fn($a) => $a->tipo === 'foto')) ?></h3>
                    <small class="text-muted">Fotos</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3 class="mb-0" id="contador-documentos"><?= count(array_filter($model->archivos, fn($a) => $a->tipo === 'documento')) ?></h3>
                    <small class="text-muted">Documentos</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3 class="mb-0" id="contador-total"><?= count($model->archivos) ?></h3>
                    <small class="text-muted">Total Archivos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas para fotos y documentos -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#tab-fotos" role="tab">
                📷 Fotos
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#tab-documentos" role="tab">
                📄 Documentos
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Tab de Fotos -->
        <div class="tab-pane fade show active" id="tab-fotos" role="tabpanel">
            <!-- Área de Drag & Drop -->
            <div class="dropzone mb-3" id="dropzone-fotos">
                <div class="dropzone-content text-center p-4 border border-dashed rounded">
                    <i class="bi bi-cloud-upload display-4 text-primary"></i>
                    <h5 class="mt-2">Arrastra fotos aquí o haz click para seleccionar</h5>
                    <p class="text-muted">Formatos: JPG, PNG, HEIC, WebP | Máx: 2MB por archivo</p>
                    <input type="file" name="archivos[]" id="input-fotos" multiple accept="image/*" style="display:none;">
                    <button type="button" class="btn btn-primary mt-2" onclick="document.getElementById('input-fotos').click()">
                        Seleccionar Fotos
                    </button>
                </div>
                <div class="upload-progress mt-2" style="display:none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Subiendo archivos...</small>
                </div>
            </div>

            <!-- Galería de fotos -->
            <div class="galeria-fotos row" id="galeria-fotos">
                <?php foreach ($model->archivos as $archivo): ?>
                    <?php if ($archivo->tipo === 'foto'): ?>
                        <div class="col-md-4 col-sm-6 foto-item" data-id="<?= $archivo->id ?>">
                            <img src="<?= $archivo->thumbnailUrl ?: $archivo->url ?>" class="foto-thumbnail" alt="<?= Html::encode($archivo->nombre_original) ?>" onclick="verImagen('<?= $archivo->url ?>')">
                            <div class="foto-overlay">
                                <div class="text-white text-center">
                                    <button class="btn btn-light btn-sm me-2" onclick="verImagen('<?= $archivo->url ?>')">
                                        <i class="bi bi-zoom-in"></i> Ver
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="eliminarArchivo(<?= $archivo->id ?>, 'foto')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                            <div class="foto-info">
                                <small class="text-muted d-block text-truncate"><?= Html::encode($archivo->nombre_original) ?></small>
                                <small class="text-muted"><?= $archivo->getTamañoFormateado() ?> | <?= Yii::$app->formatter->asDatetime($archivo->created_at) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tab de Documentos -->
        <div class="tab-pane fade" id="tab-documentos" role="tabpanel">
            <!-- Área de Drag & Drop -->
            <div class="dropzone mb-3" id="dropzone-documentos">
                <div class="dropzone-content text-center p-4 border border-dashed rounded">
                    <i class="bi bi-file-earmark-arrow-up display-4 text-success"></i>
                    <h5 class="mt-2">Arrastra documentos aquí o haz click para seleccionar</h5>
                    <p class="text-muted">Formatos: PDF, DOC, DOCX, XLS, XLSX, TXT | Máx: 2MB por archivo</p>
                    <input type="file" name="archivos[]" id="input-documentos" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" style="display:none;">
                    <button type="button" class="btn btn-success mt-2" onclick="document.getElementById('input-documentos').click()">
                        Seleccionar Documentos
                    </button>
                </div>
                <div class="upload-progress mt-2" style="display:none;">
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="text-muted">Subiendo archivos...</small>
                </div>
            </div>

            <!-- Lista de documentos -->
            <div class="lista-documentos" id="lista-documentos">
                <?php foreach ($model->archivos as $archivo): ?>
                    <?php if ($archivo->tipo === 'documento'): ?>
                        <div class="documento-item" data-id="<?= $archivo->id ?>">
                            <div class="documento-icono"><?= obtenerIconoDocumento($archivo->nombre_original) ?></div>
                            <div class="documento-info">
                                <strong><?= Html::encode($archivo->nombre_original) ?></strong><br>
                                <small class="text-muted"><?= $archivo->getTamañoFormateado() ?> | <?= Yii::$app->formatter->asDatetime($archivo->created_at) ?></small>
                            </div>
                            <div>
                                <a href="<?= $archivo->url ?>" class="btn btn-sm btn-outline-primary me-2" download>
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarArchivo(<?= $archivo->id ?>, 'documento')">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php
function obtenerIconoDocumento(string $nombre): string
{
    $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
    $iconos = [
        'pdf' => '📕',
        'doc' => '📘',
        'docx' => '📘',
        'xls' => '📗',
        'xlsx' => '📗',
        'txt' => '📃'
    ];
    return $iconos[$extension] ?? '📄';
}
?>

<style>
.galeria-fotos .foto-item {
    position: relative;
    margin-bottom: 1rem;
}

.galeria-fotos .foto-thumbnail {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 8px;
    cursor: pointer;
    transition: transform 0.2s;
}

.galeria-fotos .foto-thumbnail:hover {
    transform: scale(1.05);
}

.galeria-fotos .foto-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s;
    border-radius: 8px;
}

.galeria-fotos .foto-item:hover .foto-overlay {
    opacity: 1;
}

.foto-info {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.documento-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    margin-bottom: 0.5rem;
    transition: background 0.2s;
}

.documento-item:hover {
    background: #f8f9fa;
}

.documento-icono {
    font-size: 2rem;
    margin-right: 1rem;
}

.documento-info {
    flex: 1;
}

.dropzone {
    transition: all 0.3s;
}

.dropzone.dragover {
    background: #e3f2fd;
    border-color: #2196f3 !important;
}

.modal-fullscreen {
    max-width: 90%;
}

.modal-imagen-preview {
    max-height: 80vh;
    object-fit: contain;
}
</style>

<?php
$js = <<<JS
(function() {
    const ordenId = '{$model->id}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    // Cargar archivos existentes
    function cargarArchivos() {
        fetchArchivos();
    }
    
    async function fetchArchivos() {
        try {
            // Por ahora usamos datos embebidos, luego se puede hacer AJAX
            console.log('Archivos cargados desde PHP');
        } catch (error) {
            console.error('Error cargando archivos:', error);
        }
    }
    
    // Configurar drag & drop para fotos
    setupDropzone('dropzone-fotos', 'input-fotos', 'foto');
    
    // Configurar drag & drop para documentos
    setupDropzone('dropzone-documentos', 'input-documentos', 'documento');
    
    function setupDropzone(dropzoneId, inputId, tipo) {
        const dropzone = document.getElementById(dropzoneId);
        const input = document.getElementById(inputId);
        
        // Prevenir comportamiento por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Highlight cuando se arrastra sobre el dropzone
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.add('dragover');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('dragover');
            }, false);
        });
        
        // Manejar drop
        dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            handleFiles(files, tipo);
        }, false);
        
        // Manejar selección por input
        input.addEventListener('change', (e) => {
            handleFiles(e.target.files, tipo);
        });
    }
    
    function handleFiles(files, tipo) {
        if (files.length === 0) return;
        
        uploadFiles(files, tipo);
    }
    
    async function uploadFiles(files, tipo) {
        const formData = new FormData();
        formData.append('_csrf', csrfToken);
        formData.append('tipo', tipo);
        
        for (let file of files) {
            formData.append('archivos[]', file);
        }
        
        const dropzone = tipo === 'foto' ? document.getElementById('dropzone-fotos') : document.getElementById('dropzone-documentos');
        const progressBar = dropzone.querySelector('.upload-progress');
        const progressDiv = progressBar.querySelector('.progress-bar');
        
        progressBar.style.display = 'block';
        
        try {
            const response = await fetch('/orden-servicio/subir-archivo/' + ordenId, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Actualizar UI
                result.archivos.forEach(archivo => {
                    if (tipo === 'foto') {
                        agregarFotoAGaleria(archivo);
                    } else {
                        agregarDocumentoALista(archivo);
                    }
                });
                
                actualizarContadores();
                
                alert(result.message);
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error al subir archivos: ' + error.message);
        } finally {
            progressBar.style.display = 'none';
            progressDiv.style.width = '0%';
        }
    }
    
    function agregarFotoAGaleria(archivo) {
        const galeria = document.getElementById('galeria-fotos');
        const fotoHtml = \`
            <div class="col-md-4 col-sm-6 foto-item" data-id="\${archivo.id}">
                <img src="\${archivo.thumbnailUrl || archivo.url}" class="foto-thumbnail" alt="\${archivo.nombre}" onclick="verImagen('\${archivo.url}')">
                <div class="foto-overlay">
                    <div class="text-white text-center">
                        <button class="btn btn-light btn-sm me-2" onclick="verImagen('\${archivo.url}')">
                            <i class="bi bi-zoom-in"></i> Ver
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="eliminarArchivo(\${archivo.id}, 'foto')">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
                <div class="foto-info">
                    <small class="text-muted d-block text-truncate">\${archivo.nombre}</small>
                    <small class="text-muted">\${archivo.tamaño} | \${archivo.fecha}</small>
                </div>
            </div>
        \`;
        galeria.insertAdjacentHTML('afterbegin', fotoHtml);
    }
    
    function agregarDocumentoALista(archivo) {
        const lista = document.getElementById('lista-documentos');
        const icono = obtenerIconoDocumento(archivo.nombre);
        const documentoHtml = \`
            <div class="documento-item" data-id="\${archivo.id}">
                <div class="documento-icono">\${icono}</div>
                <div class="documento-info">
                    <strong>\${archivo.nombre}</strong><br>
                    <small class="text-muted">\${archivo.tamaño} | \${archivo.fecha}</small>
                </div>
                <div>
                    <a href="\${archivo.url}" class="btn btn-sm btn-outline-primary me-2" download>
                        <i class="bi bi-download"></i> Descargar
                    </a>
                    <button class="btn btn-sm btn-outline-danger" onclick="eliminarArchivo(\${archivo.id}, 'documento')">
                        <i class="bi bi-trash"></i> Eliminar
                    </button>
                </div>
            </div>
        \`;
        lista.insertAdjacentHTML('afterbegin', documentoHtml);
    }
    
    function obtenerIconoDocumento(nombre) {
        const extension = nombre.split('.').pop().toLowerCase();
        const iconos = {
            'pdf': '📕',
            'doc': '📘',
            'docx': '📘',
            'xls': '📗',
            'xlsx': '📗',
            'txt': '📃'
        };
        return iconos[extension] || '📄';
    }
    
    async function eliminarArchivo(archivoId, tipo) {
        if (!confirm('¿Estás seguro de que deseas eliminar este archivo?')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('_csrf', csrfToken);
        
        try {
            const response = await fetch('/orden-servicio/eliminar-archivo/' + ordenId + '/' + archivoId, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Remover elemento del DOM
                const elemento = document.querySelector(\`[data-id="\${archivoId}"]\`);
                if (elemento) {
                    elemento.remove();
                }
                
                actualizarContadores();
                
                alert(result.message);
            } else {
                alert('Error: ' + result.error);
            }
        } catch (error) {
            alert('Error al eliminar archivo: ' + error.message);
        }
    }
    
    function verImagen(url) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = \`
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="\${url}" class="modal-imagen-preview">
                    </div>
                </div>
            </div>
        \`;
        
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
        });
    }
    
    function actualizarContadores() {
        const fotos = document.querySelectorAll('#galeria-fotos .foto-item').length;
        const documentos = document.querySelectorAll('#lista-documentos .documento-item').length;
        
        document.getElementById('contador-fotos').textContent = fotos;
        document.getElementById('contador-documentos').textContent = documentos;
        document.getElementById('contador-total').textContent = fotos + documentos;
    }
    
    // Inicializar
    cargarArchivos();
})();
JS;

$this->registerJs($js);
?>

<!-- Modal para preview de imágenes -->
<div class="modal fade" id="modalImagenPreview" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" class="modal-imagen-preview" id="imagenPreview">
            </div>
        </div>
    </div>
</div>
