/**
 * Script para autoformateo de patente chilena en formularios
 * Formatos soportados:
 *   - Formato antiguo: AB-1234 (2 letras + 4 dígitos)
 *   - Formato nuevo: ABCD-12 (4 letras + 2 dígitos)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Buscar todos los inputs de patente
    const patenteInputs = document.querySelectorAll('.patente-input');
    
    if (!patenteInputs || patenteInputs.length === 0) {
        return;
    }

    /**
     * Determina el tipo de patente basado en los caracteres ingresados
     * @param {string} patente - Patente sin formato
     * @returns {string|null} 'antigua' o 'nueva' o null si no coincide
     */
    function determinarTipoPatente(patente) {
        // Solo letras y dígitos, sin guión
        const limpia = patente.replace(/[^A-Z0-9]/g, '');
        
        // Formato antiguo: 2 letras + 4 dígitos
        if (/^[A-Z]{2}\d{4}$/.test(limpia)) {
            return 'antigua';
        }
        
        // Formato nuevo: 4 letras + 2 dígitos
        if (/^[A-Z]{4}\d{2}$/.test(limpia)) {
            return 'nueva';
        }
        
        return null;
    }

    /**
     * Formatea una patente según su tipo
     * @param {string} patente - Patente ingresada
     * @returns {string} Patente formateada con guión
     */
    function formatearPatente(patente) {
        // Convertir a mayúsculas y quitar caracteres no válidos
        let limpia = patente.toUpperCase().replace(/[^A-Z0-9]/g, '');
        
        // Si está vacío, retornar vacío
        if (limpia === '') {
            return '';
        }
        
        const tipo = determinarTipoPatente(limpia);
        
        if (tipo === 'antigua') {
            // AB-1234: insertar guión después de las primeras 2 letras
            return limpia.slice(0, 2) + '-' + limpia.slice(2);
        } else if (tipo === 'nueva') {
            // ABCD-12: insertar guión después de las primeras 4 letras
            return limpia.slice(0, 4) + '-' + limpia.slice(4);
        }
        
        // Si aún no completa un formato válido, retornar sin formato pero en mayúsculas
        return limpia;
    }

    /**
     * Maneja el evento de input para formatear mientras se escribe
     */
    function handleInput(e) {
        const valor = e.target.value;
        const cursorPosition = e.target.selectionStart;
        const valorAnterior = e.target.dataset.previousValue || '';
        
        // Detectar si se borró contenido
        const seBorro = valor.length < valorAnterior.length;
        
        // Formatear el valor
        const valorFormateado = formatearPatente(valor);
        
        // Actualizar el valor solo si cambió
        if (valorFormateado !== valor) {
            e.target.value = valorFormateado;
            
            // Ajustar posición del cursor
            const diferencia = valorFormateado.length - valor.length;
            e.target.setSelectionRange(
                cursorPosition + diferencia,
                cursorPosition + diferencia
            );
        }
        
        // Guardar valor actual para próxima comparación
        e.target.dataset.previousValue = valorFormateado;
    }

    /**
     * Maneja el evento blur para validar formato final
     */
    function handleBlur(e) {
        const valor = e.target.value;
        if (valor) {
            // Asegurar formato correcto al salir del campo
            e.target.value = formatearPatente(valor);
        }
    }

    // Inicializar cada input de patente encontrado
    patenteInputs.forEach(function(patenteInput) {
        // Escuchar eventos de input para formateo en tiempo real
        patenteInput.addEventListener('input', handleInput);
        
        // Escuchar blur para validación final
        patenteInput.addEventListener('blur', handleBlur);
        
        // Guardar valor inicial para referencia
        patenteInput.dataset.previousValue = patenteInput.value;
    });
});
