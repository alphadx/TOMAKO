<?php
/**
 * Vista: Calculadora de Precios de Servicios (HU-027)
 * 
 * @var yii\web\View $this
 * @var app\models\Servicio[] $servicios
 * @var array $categorias
 * @var app\models\CalculadoraForm $model
 * @var float $tasaIva
 * @var int $diasValidez
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Calculadora de Precios de Servicios';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="calculadora-index">
    <div class="row">
        <!-- Panel Izquierdo: Formulario de Cotización -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calculator"></i>
                        <?= Html::encode($this->title) ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin([
                        'id' => 'calculadora-form',
                        'method' => 'post',
                        'action' => ['calcular'],
                    ]); ?>

                    <?= $form->field($model, 'servicio_id')
                        ->dropDownList(
                            array_combine(
                                array_column($servicios, 'id'),
                                array_map(function($s) {
                                    $categoriaNombre = $s->categoria && $s->categoria->nombre 
                                        ? $s->categoria->nombre 
                                        : 'Sin categoría';
                                    return "{$s->nombre} ({$categoriaNombre})";
                                }, $servicios)
                            ),
                            ['prompt' => 'Seleccione un servicio...', 'class' => 'form-control select2']
                        )
                        ->label('Servicio'); ?>

                    <?= $form->field($model, 'cantidad')
                        ->textInput(['type' => 'number', 'min' => 1, 'max' => 999, 'value' => 1, 'class' => 'form-control'])
                        ->label('Cantidad'); ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'margen_ganancia')
                                ->textInput(['type' => 'number', 'min' => 0, 'max' => 100, 'value' => 20, 'step' => 1, 'class' => 'form-control'])
                                ->label('Margen Ganancia (%)'); ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'descuento')
                                ->textInput(['type' => 'number', 'min' => 0, 'max' => 100, 'value' => 0, 'step' => 1, 'class' => 'form-control'])
                                ->label('Descuento (%)'); ?>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <?= Html::checkbox('incluir_repuestos', false, [
                                'class' => 'custom-control-input',
                                'id' => 'incluirRepuestosSwitch',
                                'onChange' => 'toggleRepuestosFields()',
                            ]) ?>
                            <?= Html::label('Incluir Repuestos', 'incluirRepuestosSwitch', ['class' => 'custom-control-label']) ?>
                        </div>
                    </div>

                    <div id="repuestosFields" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <?= $form->field($model, 'costo_repuestos')
                                    ->textInput(['type' => 'number', 'min' => 0, 'value' => 0, 'step' => 0.01, 'class' => 'form-control', 'placeholder' => 'Opcional'])
                                    ->label('Costo Repuestos ($)'); ?>
                            </div>
                            <div class="col-md-6">
                                <?= $form->field($model, 'porcentaje_repuestos')
                                    ->textInput(['type' => 'number', 'min' => 0, 'max' => 100, 'value' => 15, 'step' => 1, 'class' => 'form-control'])
                                    ->label('Margen Repuestos (%)'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <?= Html::button('<i class="fas fa-plus"></i> Agregar a Cotización', [
                            'class' => 'btn btn-primary btn-block',
                            'onclick' => 'agregarACotizacion()',
                        ]) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>

            <!-- Datos del Cliente para Impresión -->
            <div class="card card-info mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i>
                        Datos del Cliente
                    </h3>
                </div>
                <div class="card-body">
                    <form id="cliente-form">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cliente_nombre">Nombre Completo</label>
                                <input type="text" id="cliente_nombre" class="form-control" placeholder="Nombre completo">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cliente_rut">RUT <span class="text-danger">*</span></label>
                                <input type="text" id="cliente_rut" class="form-control" placeholder="RUT" onchange="validarYFormatearRUT(this)">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="vehiculo_patente">Patente del Vehículo</label>
                        <input type="text" id="vehiculo_patente" class="form-control" placeholder="Patente" onchange="validarYFormatearPatente(this)">
                    </div>

                    <div class="form-group mt-2">
                        <?= Html::button('<i class="fas fa-print"></i> Imprimir Cotización', [
                            'class' => 'btn btn-info btn-block',
                            'id' => 'btnImprimir',
                            'disabled' => true,
                            'onclick' => 'imprimirCotizacion()',
                        ]) ?>
                    </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Panel Derecho: Resultados y Cotización Acumulada -->
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        Resultado del Cálculo
                    </h3>
                </div>
                <div class="card-body" id="resultadoContainer">
                    <div class="text-center text-muted" id="mensajeInicial">
                        <i class="fas fa-calculator fa-4x mb-3"></i>
                        <p>Seleccione un servicio y presione "Agregar a Cotización" para ver el resultado.</p>
                    </div>

                    <div id="resultadoDetalle" style="display: none;">
                        <h4 class="text-center" id="resServicioNombre"></h4>
                        <p class="text-center text-muted" id="resServicioCodigo"></p>
                        
                        <hr>

                        <table class="table table-sm">
                            <tr>
                                <td><strong>Precio Base Unitario:</strong></td>
                                <td class="text-right" id="resPrecioBase"></td>
                            </tr>
                            <tr>
                                <td><strong>Cantidad:</strong></td>
                                <td class="text-right" id="resCantidad"></td>
                            </tr>
                            <tr>
                                <td><strong>Subtotal Mano de Obra:</strong></td>
                                <td class="text-right" id="resSubtotalManoObra"></td>
                            </tr>
                            <tr>
                                <td><strong>Margen de Ganancia (<span id="resMargenPorcentaje"></span>%):</strong></td>
                                <td class="text-right" id="resPrecioConMargen"></td>
                            </tr>
                            <tr id="rowRepuestos" style="display: none;">
                                <td><strong>Repuestos (<span id="resPorcentajeRepuestos"></span>%):</strong></td>
                                <td class="text-right" id="resCostoRepuestos"></td>
                            </tr>
                            <tr class="table-secondary">
                                <td><strong>Subtotal:</strong></td>
                                <td class="text-right" id="resSubtotal"></td>
                            </tr>
                            <tr id="rowDescuento" style="display: none;">
                                <td><strong>Descuento (<span id="resDescuentoPorcentaje"></span>%):</strong></td>
                                <td class="text-right text-danger" id="resMontoDescuento"></td>
                            </tr>
                            <tr class="table-info">
                                <td><strong>NETO:</strong></td>
                                <td class="text-right" id="resNeto"></td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>IVA (<span id="resTasaIva"></span>%):</strong></td>
                                <td class="text-right" id="resMontoIva"></td>
                            </tr>
                            <tr class="table-success">
                                <td><h4><strong>TOTAL FINAL:</strong></h4></td>
                                <td class="text-right"><h4 id="resTotalFinal"></h4></td>
                            </tr>
                        </table>

                        <hr>

                        <div class="text-center">
                            <p class="mb-1">
                                <i class="fas fa-clock"></i>
                                <strong>Duración Estimada:</strong>
                                <span id="resDuracionMinutos"></span> minutos
                                (<span id="resDuracionHoras"></span> horas)
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cotización Acumulada -->
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-shopping-cart"></i>
                        Cotización Acumulada
                        <span class="badge badge-light float-right" id="cotizacionCount">0 items</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="tablaCotizacion">
                            <thead>
                                <tr>
                                    <th>Servicio</th>
                                    <th class="text-center">Cant.</th>
                                    <th class="text-right">Total</th>
                                    <th class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="cotizacionBody">
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                        <p>No hay servicios agregados</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <hr>
                    
                    <div id="totalesAcumulados" style="display: none;">
                        <table class="table table-sm">
                            <tr class="table-info">
                                <td><strong>NETO:</strong></td>
                                <td class="text-right" id="acumNeto">$ 0</td>
                            </tr>
                            <tr class="table-warning">
                                <td><strong>IVA (<span id="acumTasaIva"></span>%):</strong></td>
                                <td class="text-right" id="acumMontoIva">$ 0</td>
                            </tr>
                            <tr class="table-success">
                                <td><h5><strong>TOTAL FINAL:</strong></h5></td>
                                <td class="text-right"><h5 id="acumTotalFinal">$ 0</h5></td>
                            </tr>
                        </table>
                        
                        <div class="text-center text-muted">
                            <small>
                                <i class="fas fa-clock"></i> Válido por <strong><?= $diasValidez ?> días</strong> |
                                Duración estimada: <span id="acumDuracion">0</span> min
                            </small>
                        </div>
                    </div>
                    
                    <div class="form-group mt-3">
                        <?= Html::button('<i class="fas fa-trash"></i> Limpiar Cotización', [
                            'class' => 'btn btn-outline-danger btn-block',
                            'id' => 'btnLimpiar',
                            'disabled' => true,
                            'onclick' => 'limpiarCotizacion()',
                        ]) ?>
                    </div>
                </div>
            </div>

            <!-- Tips de Uso -->
            <div class="card card-warning mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb"></i>
                        Tips de Uso
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>El <strong>margen de ganancia</strong> se aplica sobre el precio base del servicio.</li>
                        <li>Los <strong>repuestos</strong> pueden tener un costo específico o calcularse como porcentaje.</li>
                        <li>El <strong>descuento</strong> se aplica al total después de sumar mano de obra y repuestos.</li>
                        <li>La <strong>carga impositiva (IVA)</strong> se configura en Parámetros del Sistema (default <?= $tasaIva ?>%).</li>
                        <li>La <strong>validez de la cotización</strong> es de <?= $diasValidez ?> días corridos, configurable en Parámetros.</li>
                        <li>Use el botón <strong>"Imprimir Cotización"</strong> para generar un PDF imprimible con QR.</li>
                        <li>El RUT es obligatorio para imprimir. La patente es opcional.</li>
                        <li>La cotización se guarda temporalmente en el navegador.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$js = <<<JS
// Variables globales
var cotizacionItems = [];
var resultadoActual = null;
var tasaIva = {$tasaIva};
var diasValidez = {$diasValidez};

function toggleRepuestosFields() {
    var checkbox = document.getElementById('incluirRepuestosSwitch');
    var fields = document.getElementById('repuestosFields');
    fields.style.display = checkbox.checked ? 'block' : 'none';
}

// Validar y formatear RUT chileno
function validarYFormatearRUT(input) {
    var rut = input.value.trim().replace(/\./g, '').replace(/-/g, '');
    
    if (rut.length < 8) {
        input.value = '';
        input.classList.add('is-invalid');
        return false;
    }
    
    var cuerpo = rut.slice(0, -1);
    var dv = rut.slice(-1).toUpperCase();
    
    // Calcular dígito verificador
    var suma = 0;
    var multiplo = 2;
    for (var i = cuerpo.length - 1; i >= 0; i--) {
        suma += parseInt(cuerpo.charAt(i)) * multiplo;
        multiplo = multiplo === 7 ? 2 : multiplo + 1;
    }
    var dvEsperado = 11 - (suma % 11);
    dvEsperado = dvEsperado === 11 ? '0' : (dvEsperado === 10 ? 'K' : dvEsperado.toString());
    
    if (dv !== dvEsperado) {
        alert('RUT inválido. Dígito verificador incorrecto.');
        input.value = '';
        input.classList.add('is-invalid');
        return false;
    }
    
    // Formatear: XX.XXX.XXX-X
    var rutFormateado = cuerpo.replace(/^(\d{1,2})(\d{3})(\d{3})$/, '$1.$2.$3') + '-' + dv;
    input.value = rutFormateado;
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    verificarPermisosImpresion();
    return true;
}

// Validar y formatear patente
function validarYFormatearPatente(input) {
    var patente = input.value.trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    
    if (patente.length === 0) {
        input.value = '';
        return true;
    }
    
    var valida = false;
    var patenteFormateada = '';
    
    if (/^[A-Z]{2}\d{4}$/.test(patente)) {
        patenteFormateada = patente.substring(0, 2) + '-' + patente.substring(2);
        valida = true;
    } else if (/^[A-Z]{4}\d{2}$/.test(patente)) {
        patenteFormateada = patente.substring(0, 4) + '-' + patente.substring(4);
        valida = true;
    }
    
    if (!valida) {
        alert('Patente inválida. Use formatos LL-NNNN o LLLL-NN.');
        input.value = '';
        input.classList.add('is-invalid');
        return false;
    }
    
    input.value = patenteFormateada;
    input.classList.remove('is-invalid');
    input.classList.add('is-valid');
    return true;
}

function agregarACotizacion() {
    var formData = {
        servicio_id: $('#calculadoraform-servicio_id').val(),
        cantidad: $('#calculadoraform-cantidad').val(),
        margen_ganancia: $('#calculadoraform-margen_ganancia').val(),
        descuento: $('#calculadoraform-descuento').val(),
        incluir_repuestos: $('#incluirRepuestosSwitch').is(':checked') ? 1 : 0,
        costo_repuestos: $('#calculadoraform-costo_repuestos').val() || 0,
        porcentaje_repuestos: $('#calculadoraform-porcentaje_repuestos').val()
    };

    if (!formData.servicio_id) {
        alert('Por favor seleccione un servicio.');
        return;
    }

    $.ajax({
        url: '/calculadora/calcular',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                resultadoActual = response.data;
                mostrarResultado(response.data);
                agregarItemACotizacion(response.data, formData);
            } else {
                alert(response.message || 'Error al calcular.');
            }
        },
        error: function() {
            alert('Error en la solicitud. Intente nuevamente.');
        }
    });
}

function agregarItemACotizacion(data, params) {
    var item = {
        id: Date.now(),
        servicio_id: params.servicio_id,
        servicio_nombre: data.servicio_nombre,
        servicio_codigo: data.servicio_codigo,
        cantidad: parseInt(params.cantidad),
        precio_unitario: parseFloat(data.precio_base_unitario.replace(/\./g, '').replace(',', '.')),
        subtotal_mano_obra: parseFloat(data.subtotal_mano_obra.replace(/\./g, '').replace(',', '.')),
        margen_ganancia: parseFloat(params.margen_ganancia),
        precio_con_margen: parseFloat(data.precio_con_margen.replace(/\./g, '').replace(',', '.')),
        incluye_repuestos: data.incluye_repuestos,
        costo_repuestos: parseFloat(data.costo_repuestos.replace(/\./g, '').replace(',', '.')),
        porcentaje_repuestos: parseFloat(data.porcentaje_repuestos),
        subtotal: parseFloat(data.subtotal.replace(/\./g, '').replace(',', '.')),
        descuento_porcentaje: parseFloat(data.descuento_porcentaje),
        monto_descuento: parseFloat(data.monto_descuento.replace(/\./g, '').replace(',', '.')),
        neto: parseFloat(data.neto.replace(/\./g, '').replace(',', '.')),
        monto_iva: parseFloat(data.monto_iva.replace(/\./g, '').replace(',', '.')),
        total_final: parseFloat(data.total_final.replace(/\./g, '').replace(',', '.')),
        duracion_minutos: parseInt(data.duracion_estimada_minutos)
    };
    
    cotizacionItems.push(item);
    guardarCotizacionEnStorage();
    actualizarTablaCotizacion();
    
    $('#calculadoraform-servicio_id').val('').trigger('change');
    $('#calculadoraform-cantidad').val(1);
    $('#calculadoraform-descuento').val(0);
    $('#incluirRepuestosSwitch').prop('checked', false);
    toggleRepuestosFields();
    $('#calculadoraform-costo_repuestos').val(0);
    $('#calculadoraform-porcentaje_repuestos').val(15);
}

function actualizarTablaCotizacion() {
    var tbody = $('#cotizacionBody');
    
    if (cotizacionItems.length === 0) {
        tbody.html('<tr><td colspan="4" class="text-center text-muted py-4">' +
            '<i class="fas fa-shopping-cart fa-2x mb-2"></i>' +
            '<p>No hay servicios agregados</p></td></tr>');
        $('#totalesAcumulados').hide();
        $('#btnLimpiar').prop('disabled', true);
        $('#cotizacionCount').text('0 items');
        return;
    }
    
    var html = '';
    var totalNeto = 0;
    var totalMontoIva = 0;
    var totalFinal = 0;
    var totalDuracion = 0;
    
    cotizacionItems.forEach(function(item, index) {
        totalNeto += item.neto;
        totalMontoIva += item.monto_iva;
        totalFinal += item.total_final;
        totalDuracion += item.duracion_minutos;
        
        html += '<tr>' +
            '<td><small><strong>' + item.servicio_nombre + '</strong></small><br>' +
            '<small class="text-muted">Cód: ' + item.servicio_codigo + '</small></td>' +
            '<td class="text-center">' + item.cantidad + '</td>' +
            '<td class="text-right"><strong>$ ' + item.total_final.toLocaleString('es-CL', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></td>' +
            '<td class="text-center">' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarItem(' + item.id + ')">' +
            '<i class="fas fa-trash"></i></button></td>' +
            '</tr>';
    });
    
    tbody.html(html);
    $('#acumNeto').text('$ ' + totalNeto.toLocaleString('es-CL', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#acumTasaIva').text(tasaIva);
    $('#acumMontoIva').text('$ ' + totalMontoIva.toLocaleString('es-CL', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#acumTotalFinal').text('$ ' + totalFinal.toLocaleString('es-CL', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#acumDuracion').text(totalDuracion);
    $('#totalesAcumulados').show();
    $('#btnLimpiar').prop('disabled', false);
    $('#cotizacionCount').text(cotizacionItems.length + ' item(s)');
    verificarPermisosImpresion();
}

function eliminarItem(id) {
    if (confirm('¿Eliminar este servicio de la cotización?')) {
        cotizacionItems = cotizacionItems.filter(function(item) {
            return item.id !== id;
        });
        guardarCotizacionEnStorage();
        actualizarTablaCotizacion();
    }
}

function limpiarCotizacion() {
    if (confirm('¿Está seguro de eliminar toda la cotización?')) {
        cotizacionItems = [];
        localStorage.removeItem('cotizacion_items');
        actualizarTablaCotizacion();
        verificarPermisosImpresion();
    }
}

function guardarCotizacionEnStorage() {
    localStorage.setItem('cotizacion_items', JSON.stringify(cotizacionItems));
}

function cargarCotizacionDesdeStorage() {
    var stored = localStorage.getItem('cotizacion_items');
    if (stored) {
        try {
            cotizacionItems = JSON.parse(stored);
            actualizarTablaCotizacion();
        } catch(e) {
            console.error('Error al cargar cotización:', e);
        }
    }
}

function verificarPermisosImpresion() {
    var rutValido = $('#cliente_rut').val().trim().length > 0;
    var hayItems = cotizacionItems.length > 0;
    $('#btnImprimir').prop('disabled', !(rutValido && hayItems));
}

function mostrarResultado(data) {
    $('#mensajeInicial').hide();
    $('#resultadoDetalle').show();
    $('#resServicioNombre').text(data.servicio_nombre);
    $('#resServicioCodigo').text('Código: ' + data.servicio_codigo);
    $('#resPrecioBase').text('$ ' + data.precio_base_unitario);
    $('#resCantidad').text(data.cantidad);
    $('#resSubtotalManoObra').text('$ ' + data.subtotal_mano_obra);
    $('#resMargenPorcentaje').text(data.margen_ganancia_porcentaje);
    $('#resPrecioConMargen').text('$ ' + data.precio_con_margen);
    
    if (data.incluye_repuestos) {
        $('#rowRepuestos').show();
        $('#resPorcentajeRepuestos').text(data.porcentaje_repuestos);
        $('#resCostoRepuestos').text('$ ' + data.costo_repuestos);
    } else {
        $('#rowRepuestos').hide();
    }
    
    $('#resSubtotal').text('$ ' + data.subtotal);
    
    if (data.descuento_porcentaje > 0) {
        $('#rowDescuento').show();
        $('#resDescuentoPorcentaje').text(data.descuento_porcentaje);
        $('#resMontoDescuento').text('- $ ' + data.monto_descuento);
    } else {
        $('#rowDescuento').hide();
    }
    
    $('#resNeto').text('$ ' + data.neto);
    $('#resTasaIva').text(data.tasa_iva);
    $('#resMontoIva').text('$ ' + data.monto_iva);
    $('#resTotalFinal').text('$ ' + data.total_final);
    $('#resDuracionMinutos').text(data.duracion_estimada_minutos);
    $('#resDuracionHoras').text(data.duracion_estimada_horas);
}

function imprimirCotizacion() {
    var rut = $('#cliente_rut').val().trim();
    var nombre = $('#cliente_nombre').val().trim();
    var patente = $('#vehiculo_patente').val().trim();
    
    if (!rut) {
        alert('El RUT es obligatorio para imprimir la cotización.');
        return;
    }
    
    if (cotizacionItems.length === 0) {
        alert('Debe agregar al menos un servicio a la cotización.');
        return;
    }
    
    // Obtener token CSRF
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var csrfParam = $('meta[name="csrf-param"]').attr('content') || '_csrf';
    
    var datosEnvio = {};
    datosEnvio[csrfParam] = csrfToken;
    datosEnvio['cliente_nombre'] = nombre;
    datosEnvio['cliente_rut'] = rut;
    datosEnvio['vehiculo_patente'] = patente;
    datosEnvio['items'] = cotizacionItems;
    
    var form = $('<form>', {
        method: 'POST',
        action: '/calculadora/imprimir',
        target: '_blank'
    });
    
    $.each(datosEnvio, function(key, value) {
        if (typeof value === 'object') {
            $('<input>', {
                type: 'hidden',
                name: key,
                value: JSON.stringify(value)
            }).appendTo(form);
        } else {
            $('<input>', {
                type: 'hidden',
                name: key,
                value: value
            }).appendTo(form);
        }
    });
    
    form.appendTo('body').submit().remove();
}

$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: 'Seleccione un servicio...',
            allowClear: true
        });
    }
    cargarCotizacionDesdeStorage();
    $('#cliente_rut, #cliente_nombre').on('input change', verificarPermisosImpresion);
});
JS;

$this->registerJs($js, \yii\web\View::POS_END);
?>
