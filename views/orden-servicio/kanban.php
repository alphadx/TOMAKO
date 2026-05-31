<?php
/**
 * Vista Kanban de Órdenes de Servicio
 * HU-002: Seguimiento de Estados de Orden (Kanban)
 */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Tablero Kanban - Órdenes de Servicio';
$this->params['breadcrumbs'][] = ['label' => 'Órdenes de Servicio', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

// Etiquetas y colores por estado
$estadosConfig = [
    'abierto' => ['label' => 'Abierto', 'color' => '#6c757d', 'icon' => '📋'],
    'en_progreso' => ['label' => 'En Progreso', 'color' => '#17a2b8', 'icon' => '🔧'],
    'esperando_repuestos' => ['label' => 'Esperando Repuestos', 'color' => '#ffc107', 'icon' => '📦'],
    'listo_para_entrega' => ['label' => 'Listo para Entrega', 'color' => '#28a745', 'icon' => '✅'],
    'entregada' => ['label' => 'Entregada', 'color' => '#007bff', 'icon' => '🎉'],
];

// Colores por prioridad
$prioridadColores = [
    'baja' => '#28a745',
    'normal' => '#17a2b8',
    'alta' => '#ffc107',
    'urgente' => '#dc3545',
];
?>

<div class="kanban-container">
    <!-- Filtros -->
    <div class="kanban-filters card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-filter"></i> Filtros</h5>
        </div>
        <div class="card-body">
            <?php \yii\widgets\ActiveForm::begin([
                'method' => 'get',
                'action' => ['kanban'],
                'options' => ['class' => 'form-inline'],
            ]); ?>
            
            <div class="form-group mr-3">
                <label for="tecnico_id" class="control-label">Técnico:</label>
                <select name="tecnico_id" id="tecnico_id" class="form-control">
                    <option value="">Todos los técnicos</option>
                    <?php foreach ($tecnicos as $tecnico): ?>
                        <option value="<?= $tecnico->id ?>" <?= $tecnicoId == $tecnico->id ? 'selected' : '' ?>>
                            <?= Html::encode($tecnico->nombre) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group mr-3">
                <label for="prioridad" class="control-label">Prioridad:</label>
                <select name="prioridad" id="prioridad" class="form-control">
                    <option value="">Todas las prioridades</option>
                    <option value="baja" <?= $prioridad === 'baja' ? 'selected' : '' ?>>Baja</option>
                    <option value="normal" <?= $prioridad === 'normal' ? 'selected' : '' ?>>Normal</option>
                    <option value="alta" <?= $prioridad === 'alta' ? 'selected' : '' ?>>Alta</option>
                    <option value="urgente" <?= $prioridad === 'urgente' ? 'selected' : '' ?>>Urgente</option>
                </select>
            </div>

            <div class="form-group mr-3">
                <label for="fecha_desde" class="control-label">Desde:</label>
                <input type="date" name="fecha_desde" id="fecha_desde" class="form-control" 
                       value="<?= Html::encode($fechaDesde) ?>">
            </div>

            <div class="form-group mr-3">
                <label for="fecha_hasta" class="control-label">Hasta:</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control" 
                       value="<?= Html::encode($fechaHasta) ?>">
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="<?= Url::to(['kanban']) ?>" class="btn btn-secondary">
                    <i class="fas fa-undo"></i> Limpiar
                </a>
            </div>

            <?php \yii\widgets\ActiveForm::end(); ?>
        </div>
    </div>

    <!-- Tablero Kanban -->
    <div class="kanban-board">
        <?php foreach ($estadosConfig as $estadoKey => $config): ?>
            <div class="kanban-column" data-estado="<?= $estadoKey ?>">
                <div class="kanban-column-header" style="border-top: 4px solid <?= $config['color'] ?>">
                    <h6>
                        <span><?= $config['icon'] ?></span>
                        <?= $config['label'] ?>
                        <span class="badge badge-light"><?= count($ordenesPorEstado[$estadoKey] ?? []) ?></span>
                    </h6>
                </div>
                
                <div class="kanban-cards" id="kanban-<?= $estadoKey ?>">
                    <?php if (empty($ordenesPorEstado[$estadoKey])): ?>
                        <div class="kanban-empty">Sin órdenes</div>
                    <?php else: ?>
                        <?php foreach ($ordenesPorEstado[$estadoKey] as $orden): ?>
                            <?php
                            // Calcular tiempo en el estado actual
                            $tiempoEnEstado = 0;
                            $alertaTiempo = false;
                            
                            if ($orden->created_at) {
                                $tiempoHoras = (time() - $orden->created_at) / 3600;
                                $tiempoEnEstado = round($tiempoHoras, 1);
                                
                                // Verificar si excede el tiempo máximo
                                if (isset($tiempoMaximoEstado[$estadoKey])) {
                                    $maxHoras = $tiempoMaximoEstado[$estadoKey];
                                    $alertaTiempo = $tiempoHoras > $maxHoras;
                                }
                            }
                            
                            // Obtener técnico asignado
                            $tecnicoAsignado = null;
                            if (!empty($orden->asignaciones)) {
                                $tecnicoAsignado = $orden->asignaciones[0]->tecnico ?? null;
                            }
                            ?>
                            
                            <div class="kanban-card <?= $alertaTiempo ? 'kanban-card-alert' : '' ?>" 
                                 data-id="<?= $orden->id ?>" 
                                 draggable="true"
                                 id="card-<?= $orden->id ?>">
                                
                                <div class="kanban-card-header">
                                    <small class="kanban-codigo">#<?= Html::encode($orden->codigo) ?></small>
                                    <span class="kanban-prioridad" 
                                          style="background-color: <?= $prioridadColores[$orden->prioridad] ?? '#ccc' ?>">
                                        <?= ucfirst($orden->prioridad) ?>
                                    </span>
                                </div>
                                
                                <div class="kanban-card-body" onclick="abrirModalDetalle(<?= $orden->id ?>)">
                                    <h6 class="kanban-titulo">
                                        <?= Html::encode($orden->vehiculo?->modelo ?? 'Vehículo') ?>
                                        <?= Html::encode($orden->vehiculo?->marca ?? '') ?>
                                    </h6>
                                    <p class="kanban-cliente">
                                    <i class="fas fa-user"></i> <?= Html::encode($orden->cliente?->nombre ?? 'Cliente') ?>
                                    </p>
                                    
                                    <?php if ($tecnicoAsignado): ?>
                                        <p class="kanban-tecnico">
                                            <i class="fas fa-wrench"></i> <?= Html::encode($tecnicoAsignado->nombre) ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if ($alertaTiempo): ?>
                                        <div class="kanban-alerta-tiempo">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span><?= $tiempoEnEstado ?>h en este estado</span>
                                        </div>
                                    <?php else: ?>
                                        <small class="kanban-tiempo">
                                            <i class="far fa-clock"></i> <?= $tiempoEnEstado ?>h
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="kanban-card-footer">
                                    <a href="<?= Url::to(['view', 'id' => $orden->id]) ?>" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Ver detalle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal de Detalle Rápido -->
<div class="modal fade" id="modalDetalleOrden" tabindex="-1" role="dialog" aria-labelledby="modalDetalleLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleLabel">Detalle de Orden</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalDetalleContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="#" id="btnVerOrdenCompleta" class="btn btn-primary">Ver Orden Completa</a>
            </div>
        </div>
    </div>
</div>

<style>
.kanban-container {
    padding: 20px;
}

.kanban-filters .card {
    background: #f8f9fa;
}

.kanban-board {
    display: flex;
    gap: 20px;
    overflow-x: auto;
    padding-bottom: 20px;
    min-height: 600px;
}

.kanban-column {
    flex: 1;
    min-width: 280px;
    max-width: 320px;
    background: #f4f5f7;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
}

.kanban-column-header {
    padding: 15px;
    background: #fff;
    border-radius: 8px 8px 0 0;
    border-bottom: 1px solid #ddd;
}

.kanban-column-header h6 {
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 600;
}

.kanban-cards {
    padding: 10px;
    flex-grow: 1;
    overflow-y: auto;
    min-height: 400px;
}

.kanban-empty {
    text-align: center;
    color: #999;
    padding: 20px;
    font-style: italic;
}

.kanban-card {
    background: #fff;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
    cursor: grab;
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.kanban-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.kanban-card.dragging {
    opacity: 0.5;
    cursor: grabbing;
}

.kanban-card-alert {
    border-left-color: #dc3545;
    background: #fff5f5;
}

.kanban-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.kanban-codigo {
    font-weight: 600;
    color: #666;
}

.kanban-prioridad {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    color: #fff;
    font-weight: 600;
    text-transform: uppercase;
}

.kanban-card-body {
    cursor: pointer;
}

.kanban-titulo {
    font-size: 14px;
    margin: 0 0 8px 0;
    color: #333;
}

.kanban-cliente,
.kanban-tecnico {
    font-size: 12px;
    color: #666;
    margin: 4px 0;
}

.kanban-alerta-tiempo {
    color: #dc3545;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    padding: 4px 8px;
    background: #ffe6e6;
    border-radius: 4px;
}

.kanban-tiempo {
    color: #999;
    font-size: 11px;
}

.kanban-card-footer {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    text-align: right;
}

/* Responsive */
@media (max-width: 768px) {
    .kanban-board {
        flex-direction: column;
    }
    
    .kanban-column {
        max-width: none;
        margin-bottom: 20px;
    }
    
    .kanban-cards {
        min-height: auto;
    }
}
</style>

<script>
// Variables globales para drag & drop
let draggedCard = null;
let sourceColumn = null;

// Inicializar eventos cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    inicializarDragAndDrop();
});

function inicializarDragAndDrop() {
    const cards = document.querySelectorAll('.kanban-card');
    const columns = document.querySelectorAll('.kanban-cards');

    // Configurar cards
    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    // Configurar columnas
    columns.forEach(column => {
        column.addEventListener('dragover', handleDragOver);
        column.addEventListener('dragleave', handleDragLeave);
        column.addEventListener('drop', handleDrop);
    });
}

function handleDragStart(e) {
    draggedCard = this;
    sourceColumn = this.parentElement;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', this.dataset.id);
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    draggedCard = null;
    sourceColumn = null;
    
    // Remover clase de todas las columnas
    document.querySelectorAll('.kanban-cards').forEach(col => {
        col.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    
    const nuevoEstado = this.dataset.estado;
    const ordenId = e.dataTransfer.getData('text/plain');
    
    if (draggedCard && nuevoEstado) {
        // Actualizar estado vía AJAX
        actualizarEstadoOrden(ordenId, nuevoEstado, draggedCard, this);
    }
}

function actualizarEstadoOrden(ordenId, nuevoEstado, card, columnaDestino) {
    fetch('<?= Url::to(['actualizar-estado-kanban']) ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `id=${ordenId}&estado=${nuevoEstado}&_csrf=<?= Yii::$app->request->csrfToken ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mover la tarjeta visualmente
            columnaDestino.appendChild(card);
            
            // Mostrar notificación
            mostrarNotificacion('Estado actualizado correctamente', 'success');
            
            // Actualizar alerta de tiempo si es necesario
            actualizarAlertaTiempo(card, nuevoEstado);
        } else {
            // Revertir el movimiento
            sourceColumn.appendChild(card);
            mostrarNotificacion(data.message || 'Error al actualizar estado', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (sourceColumn) {
            sourceColumn.appendChild(card);
        }
        mostrarNotificacion('Error de conexión', 'danger');
    });
}

function actualizarAlertaTiempo(card, estado) {
    // Recargar la página para actualizar los tiempos (opcional)
    // O hacer una llamada AJAX para obtener el nuevo tiempo
    setTimeout(() => {
        location.reload();
    }, 1500);
}

function abrirModalDetalle(ordenId) {
    $('#modalDetalleOrden').modal('show');
    $('#btnVerOrdenCompleta').attr('href', '/orden-servicio/view?id=' + ordenId);
    
    // Cargar contenido del modal vía AJAX
    $.ajax({
        url: '/orden-servicio/view',
        data: { id: ordenId, modal: 1 },
        success: function(html) {
            // Extraer solo el contenido relevante
            $('#modalDetalleContent').html(html);
        },
        error: function() {
            $('#modalDetalleContent').html('<div class="alert alert-danger">Error al cargar el detalle</div>');
        }
    });
}

function mostrarNotificacion(mensaje, tipo) {
    const alertHtml = `
        <div class="alert alert-${tipo} alert-dismissible fade show kanban-notification" role="alert">
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    
    $('.kanban-container').prepend(alertHtml);
    
    // Auto-dismiss después de 3 segundos
    setTimeout(() => {
        $('.kanban-notification').alert('close');
    }, 3000);
}
</script>
