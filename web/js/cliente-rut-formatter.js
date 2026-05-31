/**
 * Script para autoformateo de RUT chileno en formularios
 * Formato objetivo: 12.345.678-9
 */

document.addEventListener('DOMContentLoaded', function() {
    // Buscar todos los inputs de RUT (tanto en formulario principal como en modales)
    const rutInputs = document.querySelectorAll('.rut-input');
    
    if (!rutInputs || rutInputs.length === 0) {
        return;
    }

    /**
     * Formatea un RUT ingresado agregando puntos y guión automáticamente
     * @param {string} rut - RUT sin formato o parcialmente formateado
     * @returns {string} RUT formateado como 12.345.678-9
     */
    function formatearRUT(rut) {
        // Limpiar el RUT: quitar puntos, espacios y guiones previos
        let rutLimpio = rut.replace(/[.\s-]/g, '').toUpperCase();
        
        // Si está vacío, retornar vacío
        if (rutLimpio === '') {
            return '';
        }
        
        // Separar cuerpo y dígito verificador
        // El último carácter es el DV, todo lo anterior es el cuerpo
        let cuerpo = rutLimpio.slice(0, -1);
        let dv = rutLimpio.slice(-1);
        
        // Agregar puntos al cuerpo cada 3 dígitos desde el final
        let cuerpoFormateado = cuerpo.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        
        // Retornar formateado con guión antes del DV
        return cuerpoFormateado + '-' + dv;
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
        const valorFormateado = formatearRUT(valor);
        
        // Actualizar el valor solo si cambió
        if (valorFormateado !== valor) {
            e.target.value = valorFormateado;
            
            // Ajustar posición del cursor
            // Si se agregó un caracter (punto o guión), mover cursor adelante
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
            e.target.value = formatearRUT(valor);
        }
    }

    // Inicializar cada input de RUT encontrado
    rutInputs.forEach(function(rutInput) {
        // Escuchar eventos de input para formateo en tiempo real
        rutInput.addEventListener('input', handleInput);
        
        // Escuchar blur para validación final
        rutInput.addEventListener('blur', handleBlur);
        
        // Guardar valor inicial para referencia
        rutInput.dataset.previousValue = rutInput.value;
    });
});
