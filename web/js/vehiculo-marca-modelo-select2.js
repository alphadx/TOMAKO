/**
 * Vehiculo Marca/Modelo Select2
 * 
 * Implementa selectores con autocompletado para marca y modelo de vehículos.
 * Permite buscar existentes o crear nuevos si no existen.
 * 
 * @author TOMAKO
 * @since 1.0.0
 */

(function() {
    'use strict';

    // Configuración de URLs API
    const MARCA_SEARCH_URL = '/api-marca/search';
    const MARCA_CREATE_URL = '/api-marca/create';
    const MODELO_SEARCH_URL = '/api-modelo/search';
    const MODELO_CREATE_URL = '/api-modelo/create';

    /**
     * Inicializa Select2 para marca en un formulario específico.
     * 
     * @param {string} formId - ID del formulario contenedor
     * @param {string} selectId - ID del elemento select para marca
     * @param {string|null} modeloSelectId - ID del elemento select para modelo (opcional)
     */
    function initMarcaSelect2(formId, selectId, modeloSelectId = null) {
        const $select = $(`#${selectId}`);
        
        if ($select.length === 0) {
            console.warn(`Elemento #${selectId} no encontrado`);
            return;
        }

        // Si es un input text, convertirlo a select
        if ($select.prop('tagName') === 'INPUT' && $select.attr('type') === 'text') {
            const $newSelect = $('<select></select>')
                .attr('id', selectId)
                .attr('name', $select.attr('name'))
                .addClass($select.attr('class'))
                .prop('required', $select.prop('required'));
            
            $select.replaceWith($newSelect);
            initMarcaSelect2(formId, selectId, modeloSelectId);
            return;
        }

        $select.select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar o escribir nueva marca',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function() {
                    return 'Ingrese al menos 2 caracteres';
                },
                searching: function() {
                    return 'Buscando...';
                },
                noResults: function() {
                    return 'No se encontraron marcas. Escriba para crear una nueva.';
                }
            },
            ajax: {
                url: MARCA_SEARCH_URL,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        _csrf: getCsrfToken()
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            templateResult: function(item) {
                if (item.create) {
                    return $('<span class="text-primary"><i class="fas fa-plus-circle"></i> ' + item.text + '</span>');
                }
                return $('<span>' + item.text + '</span>');
            },
            templateSelection: function(item) {
                return item.text || item.nombre || 'Seleccione una marca';
            }
        });

        // Manejar creación de nueva marca
        $select.on('select2:select', function(e) {
            const data = e.params.data;
            
            if (data && data.create && data.id.startsWith('new:')) {
                const nuevoNombre = data.nombre || data.id.replace('new:', '');
                
                $.post(MARCA_CREATE_URL, {
                    nombre: nuevoNombre,
                    _csrf: getCsrfToken()
                }, function(response) {
                    if (response.success) {
                        // Actualizar el select con el nuevo valor
                        const newOption = new Option(response.text, response.id, true, true);
                        $select.append(newOption).trigger('change');
                        
                        // Limpiar selector de modelos si existe
                        if (modeloSelectId) {
                            $(`#${modeloSelectId}`).val(null).trigger('change');
                            initModeloSelect2(formId, modeloSelectId, selectId);
                        }
                        
                        mostrarMensaje('success', 'Marca creada exitosamente');
                    } else {
                        mostrarMensaje('error', response.message || 'Error al crear la marca');
                        $select.val(null).trigger('change');
                    }
                }, 'json').fail(function() {
                    mostrarMensaje('error', 'Error de conexión al crear la marca');
                    $select.val(null).trigger('change');
                });
            }
            
            // Si cambia la marca, resetear y recargar modelos
            if (modeloSelectId && data && !data.create && data.id) {
                const $modeloSelect = $(`#${modeloSelectId}`);
                if ($modeloSelect.length > 0) {
                    $modeloSelect.val(null).trigger('change');
                    initModeloSelect2(formId, modeloSelectId, selectId);
                }
            }
        });
    }

    /**
     * Inicializa Select2 para modelo en un formulario específico.
     * 
     * @param {string} formId - ID del formulario contenedor
     * @param {string} selectId - ID del elemento select para modelo
     * @param {string} marcaSelectId - ID del elemento select para marca
     */
    function initModeloSelect2(formId, selectId, marcaSelectId) {
        const $select = $(`#${selectId}`);
        const $marcaSelect = $(`#${marcaSelectId}`);
        
        if ($select.length === 0) {
            console.warn(`Elemento #${selectId} no encontrado`);
            return;
        }

        // Si es un input text, convertirlo a select
        if ($select.prop('tagName') === 'INPUT' && $select.attr('type') === 'text') {
            const $newSelect = $('<select></select>')
                .attr('id', selectId)
                .attr('name', $select.attr('name'))
                .addClass($select.attr('class'))
                .prop('required', $select.prop('required'));
            
            $select.replaceWith($newSelect);
            initModeloSelect2(formId, selectId, marcaSelectId);
            return;
        }

        // Función para cargar modelos según marca seleccionada
        function cargarModelos() {
            const marcaId = $marcaSelect ? $marcaSelect.val() : null;
            
            if (!marcaId || parseInt(marcaId) <= 0) {
                $select.prop('disabled', true);
                $select.empty().append('<option value="">Primero seleccione una marca</option>');
                return;
            }
            
            $select.prop('disabled', false);
        }

        $select.select2({
            theme: 'bootstrap4',
            placeholder: 'Buscar o escribir nuevo modelo',
            allowClear: true,
            minimumInputLength: 2,
            language: {
                inputTooShort: function() {
                    return 'Ingrese al menos 2 caracteres';
                },
                searching: function() {
                    return 'Buscando...';
                },
                noResults: function() {
                    return 'No se encontraron modelos. Escriba para crear uno nuevo.';
                }
            },
            ajax: {
                url: MODELO_SEARCH_URL,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    const marcaId = $marcaSelect ? $marcaSelect.val() : null;
                    return {
                        q: params.term || '',
                        marca_id: marcaId || '',
                        _csrf: getCsrfToken()
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.results || []
                    };
                },
                cache: true
            },
            templateResult: function(item) {
                if (item.create) {
                    return $('<span class="text-primary"><i class="fas fa-plus-circle"></i> ' + item.text + '</span>');
                }
                return $('<span>' + item.text + '</span>');
            },
            templateSelection: function(item) {
                return item.text || item.nombre || 'Seleccione un modelo';
            }
        });

        // Manejar creación de nuevo modelo
        $select.on('select2:select', function(e) {
            const data = e.params.data;
            
            if (data && data.create && data.id.startsWith('new:')) {
                const nuevoNombre = data.nombre || data.id.replace('new:', '');
                const marcaId = data.marca_id || ($marcaSelect ? $marcaSelect.val() : null);
                
                if (!marcaId) {
                    mostrarMensaje('error', 'Debe seleccionar una marca primero');
                    $select.val(null).trigger('change');
                    return;
                }
                
                $.post(MODELO_CREATE_URL, {
                    nombre: nuevoNombre,
                    marca_id: marcaId,
                    _csrf: getCsrfToken()
                }, function(response) {
                    if (response.success) {
                        const newOption = new Option(response.text, response.id, true, true);
                        $select.append(newOption).trigger('change');
                        mostrarMensaje('success', 'Modelo creado exitosamente');
                    } else {
                        mostrarMensaje('error', response.message || 'Error al crear el modelo');
                        $select.val(null).trigger('change');
                    }
                }, 'json').fail(function() {
                    mostrarMensaje('error', 'Error de conexión al crear el modelo');
                    $select.val(null).trigger('change');
                });
            }
        });

        // Cargar modelos iniciales si hay marca seleccionada
        cargarModelos();
        
        // Escuchar cambios en la marca
        if ($marcaSelect) {
            $marcaSelect.on('change', function() {
                cargarModelos();
                // Reinicializar para que tome la nueva marca
                if ($select.data('select2')) {
                    $select.select2('destroy');
                }
                initModeloSelect2(formId, selectId, marcaSelectId);
            });
        }
    }

    /**
     * Obtiene el token CSRF de Yii2.
     * 
     * @returns {string} Token CSRF
     */
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    /**
     * Muestra un mensaje toast/notificación.
     * 
     * @param {string} tipo - 'success', 'error', 'warning', 'info'
     * @param {string} mensaje - Mensaje a mostrar
     */
    function mostrarMensaje(tipo, mensaje) {
        // Intentar usar notificaciones de Bootstrap
        const container = document.getElementById('toast-container') || document.body;
        
        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo === 'error' ? 'danger' : tipo} alert-dismissible fade show`;
        toast.role = 'alert';
        toast.innerHTML = `
            ${mensaje}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    // Exponer funciones globalmente
    window.VehiculoMarcaModeloSelect2 = {
        initMarcaSelect2: initMarcaSelect2,
        initModeloSelect2: initModeloSelect2,
        initForm: function(formId, marcaSelectId, modeloSelectId) {
            initMarcaSelect2(formId, marcaSelectId, modeloSelectId);
            if (modeloSelectId) {
                initModeloSelect2(formId, modeloSelectId, marcaSelectId);
            }
        }
    };

    // Auto-inicializar en formularios de vehículo si existen los elementos
    document.addEventListener('DOMContentLoaded', function() {
        // Formulario principal de vehículo
        if (document.getElementById('vehiculo-marca') && document.getElementById('vehiculo-modelo')) {
            window.VehiculoMarcaModeloSelect2.initForm(
                'vehiculo-form',
                'vehiculo-marca',
                'vehiculo-modelo'
            );
        }
        
        // Modal de alta rápida
        if (document.getElementById('vehiculo-quick-modal-marca') && document.getElementById('vehiculo-quick-modal-modelo')) {
            window.VehiculoMarcaModeloSelect2.initForm(
                'vehiculo-quick-form',
                'vehiculo-quick-modal-marca',
                'vehiculo-quick-modal-modelo'
            );
        }
    });

})();
