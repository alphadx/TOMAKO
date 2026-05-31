<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'MANUAL DE USUARIO';
$this->params['breadcrumbs'][] = $this->title;

$modulos = [
    [
        'id' => 'manual',
        'titulo' => 'Manual de Usuario',
        'icono' => '📘',
        'ruta' => ['/manual/index'],
        'proposito' => 'Centralizar guías, procesos operativos y buenas prácticas de uso de la plataforma.',
        'operaciones' => [
            'Revisar el índice por módulo y navegar por secciones con anclas rápidas.',
            'Compartir procedimientos estándar con personal nuevo o temporal.',
            'Usar la sección de problemas frecuentes para resolver incidentes comunes.',
            'Mantener criterio único de operación entre recepción, técnicos y administración.',
        ],
        'pasoAPaso' => [
            'Ingrese desde el menú lateral en la opción Manual de Usuario.',
            'Seleccione el módulo que desea consultar en el índice de navegación.',
            'Siga el flujo recomendado y valide precondiciones antes de ejecutar acciones críticas.',
            'Si detecta diferencias con el proceso real, registre el ajuste requerido para actualización del manual.',
        ],
        'recomendaciones' => [
            'Úselo como referencia principal antes de solicitar soporte técnico.',
            'Capacite personal nuevo con este manual durante su primera semana.',
            'Alinee revisiones de calidad usando los flujos aquí descritos.',
        ],
        'errores' => [
            'Operar por memoria sin verificar estados actuales del registro.',
            'Omitir validaciones de cliente/vehículo antes de crear citas u órdenes.',
            'No documentar excepciones operativas que se repiten en el tiempo.',
        ],
    ],
    [
        'id' => 'dashboard',
        'titulo' => 'Panel de Control',
        'icono' => '📊',
        'ruta' => ['/dashboard/index'],
        'proposito' => 'Monitorear indicadores críticos del taller en tiempo real para priorizar tareas del día.',
        'operaciones' => [
            'Visualizar KPIs de servicios activos, citas del día, stock bajo e ingresos mensuales.',
            'Consultar alertas de inventario y órdenes activas para coordinación rápida.',
            'Acceder a acciones rápidas para crear citas, órdenes, clientes o vehículos.',
            'Revisar indicadores avanzados de eficiencia, retención y productividad.',
        ],
        'pasoAPaso' => [
            'Revise las tarjetas KPI al iniciar jornada y detecte alertas rojas o amarillas.',
            'Abra el bloque de citas del día y confirme carga por técnico.',
            'Ingrese a inventario si hay stock bajo y coordine reposición.',
            'Cierre jornada validando trabajos listos para entrega y pagos pendientes.',
            'Analice tendencias semanales/mensuales usando los indicadores históricos.',
        ],
        'recomendaciones' => [
            'Use el dashboard como primera pantalla operativa del día.',
            'Priorice órdenes urgentes y bloqueos de repuestos antes de tareas administrativas.',
            'Configure alertas personalizadas según thresholds de su operación.',
        ],
        'errores' => [
            'Interpretar KPIs sin considerar fecha de corte del día.',
            'Dejar alertas de stock crítico sin acción de compra o ajuste.',
            'Ignorar indicadores de tendencia que anticipan problemas operativos.',
        ],
        'indicadores' => [
            [
                'nombre' => 'Servicios Activos',
                'descripcion' => 'Cantidad de órdenes de servicio en estado abierto, en progreso o esperando repuestos.',
                'interpretacion' => 'Valores altos indican alta demanda operativa. Valores muy bajos pueden señalar necesidad de promoción comercial.',
            ],
            [
                'nombre' => 'Citas Hoy',
                'descripcion' => 'Número total de citas agendadas para el día actual excluyendo canceladas.',
                'interpretacion' => 'Permite dimensionar la carga diaria y asignar recursos adecuadamente.',
            ],
            [
                'nombre' => 'Stock Bajo',
                'descripcion' => 'Cantidad de ítems del inventario cuya existencia está por debajo del stock mínimo configurado.',
                'interpretacion' => 'Alerta crítica que requiere acción inmediata de reposición para evitar quiebres de stock.',
            ],
            [
                'nombre' => 'Ingresos Mes',
                'descripcion' => 'Suma total de pagos completados o pagados durante el mes en curso.',
                'interpretacion' => 'Indicador financiero clave para seguimiento de metas comerciales mensuales.',
            ],
            [
                'nombre' => 'Trabajos Listos',
                'descripcion' => 'Órdenes de servicio en estado "listo para entrega" awaiting customer pickup.',
                'interpretacion' => 'Señala trabajos terminados que requieren gestión de entrega y cobro.',
            ],
            [
                'nombre' => 'Clientes Nuevos',
                'descripcion' => 'Clientes registrados por primera vez en el mes actual.',
                'interpretacion' => 'Mide efectividad de estrategias de captación y crecimiento de base de clientes.',
            ],
            [
                'nombre' => 'Valor Inventario',
                'descripcion' => 'Valor monetario total del inventario activo (cantidad × precio unitario).',
                'interpretacion' => 'Importante para gestión de capital de trabajo y políticas de reposición.',
            ],
            [
                'nombre' => 'Entregas a Tiempo',
                'descripcion' => 'Porcentaje de órdenes entregadas dentro del tiempo estimado (≤3 días desde apertura).',
                'interpretacion' => 'Mide eficiencia operativa. ≥80% es excelente, <80% requiere revisión de procesos.',
            ],
            [
                'nombre' => 'Tiempo Promedio Resolución',
                'descripcion' => 'Promedio de días transcurridos entre apertura y cierre de órdenes entregadas.',
                'interpretacion' => 'Indica velocidad de atención. Valores altos sugieren cuellos de botella o falta de recursos.',
            ],
            [
                'nombre' => 'Tasa Cancelación',
                'descripcion' => 'Porcentaje de órdenes canceladas respecto al total creado en el período.',
                'interpretacion' => '≤5% es aceptable. Valores superiores requieren análisis de causas raíz.',
            ],
            [
                'nombre' => 'Rotación Inventario',
                'descripcion' => 'Frecuencia con que se renueva el inventario en el período (costo ventas / inventario promedio).',
                'interpretacion' => '≥1 indica rotación óptima. Valores bajos señalan exceso de stock o productos obsoletos.',
            ],
            [
                'nombre' => 'Ausentismo Citas',
                'descripcion' => 'Porcentaje de citas en estado "no_show" (cliente no se presentó) respecto al total agendado.',
                'interpretacion' => '≤10% es normal. Valores altos requieren política de confirmación o depósito.',
            ],
            [
                'nombre' => 'Ingreso Promedio Orden',
                'descripcion' => 'Valor promedio facturado por orden de servicio entregada.',
                'interpretacion' => '≥$50.000 es óptimo. Ayuda a definir estrategias de upselling y paquetes de servicio.',
            ],
            [
                'nombre' => 'Retención Clientes',
                'descripcion' => 'Porcentaje de clientes que retornan al taller en un período de 6 meses.',
                'interpretacion' => '≥50% indica buena fidelización. Valores bajos requieren mejorar experiencia post-servicio.',
            ],
            [
                'nombre' => 'Productividad Técnico',
                'descripcion' => 'Promedio de órdenes completadas por técnico en el período.',
                'interpretacion' => '≥10 órdenes/técnico es alta productividad. Permite identificar necesidades de capacitación o dotación.',
            ],
            [
                'nombre' => 'Quiebre de Stock',
                'descripcion' => 'Porcentaje de ítems con cantidad cero o insuficiente para atender demanda.',
                'interpretacion' => '≤10% es controlado. Valores críticos afectan tiempos de entrega y satisfacción del cliente.',
            ],
            [
                'nombre' => 'Frecuencia Servicio Vehículo',
                'descripcion' => 'Promedio de días entre atenciones consecutivas por vehículo.',
                'interpretacion' => '≤90 días indica mantenimiento regular. Valores altos sugieren pérdida de trazabilidad del vehículo.',
            ],
            [
                'nombre' => 'Margen Bruto Servicio',
                'descripcion' => 'Porcentaje de ganancia bruta promedio por servicio (precio venta - costo) / precio venta.',
                'interpretacion' => '≥30% es óptimo. Valores bajos requieren revisión de costos o ajustes de precios.',
            ],
            [
                'nombre' => 'Tasa de Morosidad',
                'descripcion' => 'Porcentaje de pagos pendientes respecto al total de órdenes entregadas.',
                'interpretacion' => '≤10% es controlado. Valores altos indican riesgo financiero y requieren gestión de cobranza.',
            ],
            [
                'nombre' => 'Ocupación Agenda',
                'descripcion' => 'Porcentaje de slots horarios disponibles que están reservados con citas confirmadas.',
                'interpretacion' => '≥70% es óptimo. Valores bajos indican capacidad ociosa; valores muy altos pueden causar sobrecarga.',
            ],
        ],
    ],
    [
        'id' => 'clientes',
        'titulo' => 'Clientes',
        'icono' => '👥',
        'ruta' => ['/cliente/index'],
        'proposito' => 'Gestionar datos de contacto, estado y trazabilidad de los clientes del taller con segmentación por etiquetas.',
        'operaciones' => [
            'Crear y editar fichas de clientes con validación de datos básicos.',
            'Desactivar clientes para evitar uso accidental en procesos nuevos.',
            'Exportar información para análisis o respaldo operativo.',
            'Asignar etiquetas para segmentación y filtros avanzados.',
            'Gestionar etiquetas personalizadas para clasificación de clientes.',
        ],
        'pasoAPaso' => [
            'Busque por nombre/RUT antes de crear un nuevo cliente para evitar duplicados.',
            'Registre datos mínimos obligatorios y complete canales de contacto válidos.',
            'Verifique el estado del cliente antes de asociarlo a citas u órdenes.',
            'Asigne etiquetas según perfil (ej: "VIP", "Frecuente", "Flota").',
            'Use exportación CSV para cierres administrativos o auditorías internas.',
            'Exporte segmentos por etiqueta para campañas dirigidas.',
        ],
        'recomendaciones' => [
            'Estandarice formato de nombres y teléfonos para mejor búsqueda.',
            'No elimine históricos: prefiera desactivar cuando corresponda.',
            'Use etiquetas consistentes para facilitar segmentación.',
        ],
        'errores' => [
            'Crear múltiples fichas para un mismo cliente.',
            'Registrar teléfonos/correos sin formato verificable.',
            'Sobrecargar con demasiadas etiquetas sin criterio claro.',
        ],
    ],
    [
        'id' => 'vehiculos',
        'titulo' => 'Vehículos',
        'icono' => '🚗',
        'ruta' => ['/vehiculo/index'],
        'proposito' => 'Administrar parque vehicular por cliente con información técnica para diagnóstico y servicio.',
        'operaciones' => [
            'Registrar vehículos por cliente con patente y datos de identificación.',
            'Actualizar datos técnicos, kilometraje y observaciones relevantes.',
            'Controlar estado activo/inactivo del vehículo para nuevas atenciones.',
        ],
        'pasoAPaso' => [
            'Confirme cliente propietario antes de alta o edición.',
            'Valide patente única y consistencia de marca/modelo/año.',
            'Actualice kilometraje al ingreso para trazabilidad de mantenimientos.',
            'Revise historial antes de aprobar servicios repetitivos o garantías.',
        ],
        'recomendaciones' => [
            'Mantenga observaciones técnicas breves y accionables.',
            'Use el historial para prevenir reprocesos y reclamos.',
        ],
        'errores' => [
            'Patentes duplicadas por diferencias de formato.',
            'Asociar vehículo al cliente incorrecto por no validar identidad.',
        ],
    ],
    [
        'id' => 'citas',
        'titulo' => 'Citas',
        'icono' => '📅',
        'ruta' => ['/cita/index'],
        'proposito' => 'Planificar agenda operativa del taller y asegurar continuidad entre recepción y ejecución técnica.',
        'operaciones' => [
            'Crear citas con fecha/hora, cliente y vehículo asociado.',
            'Reprogramar, confirmar, cancelar o marcar no-show según seguimiento.',
            'Visualizar calendario y estadísticas mensuales para balance de carga.',
            'Agregar múltiples servicios a una cita para cálculo automático de duración estimada.',
            'Ver duración total estimada basada en la suma de servicios seleccionados.',
        ],
        'pasoAPaso' => [
            'Registre cita verificando disponibilidad en la agenda.',
            'Seleccione los servicios que se realizarán: el sistema calculará automáticamente la duración estimada sumando el tiempo de cada servicio.',
            'La hora de fin se estimará automáticamente según la duración total de los servicios seleccionados.',
            'Confirme la cita con anticipación y documente observaciones del cliente.',
            'Reprograme usando motivo claro cuando exista indisponibilidad.',
            'Al recibir el vehículo, inicie servicio desde la cita para continuidad del flujo.',
        ],
        'recomendaciones' => [
            'Evite sobrecarga horaria sin técnicos/repuestos disponibles.',
            'Use estados de cita para seguimiento real, no simbólico.',
            'Considere la duración estimada al agendar para evitar solapamientos.',
            'Revise que la suma de duraciones de servicios no exceda la jornada laboral disponible.',
        ],
        'errores' => [
            'Crear citas sin vehículo asociado correctamente.',
            'No actualizar estado tras cancelaciones o ausencias.',
            'Ignorar la duración estimada al agendar, causando sobrecarga de agenda.',
            'Seleccionar servicios sin verificar que sus duraciones sean realistas.',
        ],
    ],
    [
        'id' => 'ordenes',
        'titulo' => 'Órdenes de Servicio',
        'icono' => '🔧',
        'ruta' => ['/orden/index'],
        'proposito' => 'Controlar de extremo a extremo la ejecución técnica, costos y estados de reparación/mantenimiento.',
        'operaciones' => [
            'Crear órdenes manualmente o desde una cita previa.',
            'Asignar técnicos, agregar servicios, notas y cambiar estados operativos.',
            'Cerrar orden con validaciones de checklist y consistencia de trabajo ejecutado.',
            'Visualizar duración estimada total basada en la suma de servicios asociados.',
            'Adjuntar fotos y documentos como evidencia del trabajo realizado.',
            'Gestionar checklists personalizables según el tipo de servicio.',
            'Visualizar órdenes en vista Kanban para seguimiento visual del flujo de trabajo.',
        ],
        'pasoAPaso' => [
            'Abra orden con diagnóstico inicial y prioridad definida.',
            'Asigne técnico responsable y tiempos estimados de trabajo.',
            'Incorpore ítems de servicio: el sistema calculará automáticamente la duración total sumando las duraciones individuales.',
            'Registre notas técnicas y evidencias relevantes durante la ejecución.',
            'Adjunte fotos y documentos usando la pestaña "Archivos" con interfaz drag & drop.',
            'Complete los items del checklist según corresponda al servicio realizado.',
            'Antes de cerrar, confirme checklist completo y trazabilidad de cambios.',
            'Compare tiempo estimado vs. tiempo real para mejorar futuras estimaciones.',
        ],
        'recomendaciones' => [
            'Use notas cronológicas para auditoría y soporte posventa.',
            'Evite cerrar órdenes con pendientes de inventario o cobro.',
            'Revise la duración estimada para comunicar tiempos realistas al cliente.',
            'Documente con fotos el estado antes/después del servicio.',
            'Utilice la vista Kanban para monitorear carga de trabajo del taller.',
        ],
        'errores' => [
            'Cambiar estados sin respaldo de avance real.',
            'No validar relación cliente-vehículo al crear orden desde cero.',
            'Ignorar la duración estimada al planificar carga de trabajo del taller.',
            'Olvidar adjuntar evidencia fotográfica de trabajos críticos.',
        ],
    ],
    [
        'id' => 'seguimiento',
        'titulo' => 'Seguimiento Post-Servicio',
        'icono' => '📞',
        'ruta' => ['/seguimiento/index'],
        'proposito' => 'Gestionar llamadas de seguimiento post-servicio para medir satisfacción del cliente y detectar oportunidades de mejora.',
        'operaciones' => [
            'Programar seguimientos automáticos después de entregar una orden.',
            'Registrar resultados de llamadas con encuestas NPS y satisfacción.',
            'Visualizar agenda de pendientes del día.',
            'Generar reportes de satisfacción y NPS por período.',
        ],
        'pasoAPaso' => [
            'Revise la agenda diaria de seguimientos pendientes.',
            'Contacte al cliente según lo programado.',
            'Registre el resultado de la llamada usando las opciones predefinidas.',
            'Complete la encuesta NPS (0-10) y evaluación de satisfacción.',
            'Agregue observaciones si el cliente reporta incidencias.',
            'Marque el seguimiento como completado.',
        ],
        'recomendaciones' => [
            'Realice seguimientos dentro de las 48-72 horas post-entrega.',
            'Priorice clientes con servicios de alto valor o repetitivos.',
            'Documente incidencias para acciones correctivas.',
        ],
        'errores' => [
            'Postergar seguimientos más allá del plazo óptimo.',
            'Registrar resultados genéricos sin detalles accionables.',
            'No escalar incidencias críticas detectadas en el seguimiento.',
        ],
    ],
    [
        'id' => 'pagos',
        'titulo' => 'Pagos',
        'icono' => '💳',
        'ruta' => ['/pago/index'],
        'proposito' => 'Registrar transacciones y cierres financieros con trazabilidad por orden y método de pago.',
        'operaciones' => [
            'Crear pagos vinculados a órdenes de servicio.',
            'Confirmar/anular movimientos según reglas de control.',
            'Emitir reportes y exportaciones por tipo de análisis.',
            'Gestionar apertura y cierre de caja.',
        ],
        'pasoAPaso' => [
            'Verifique saldo pendiente de la orden antes de registrar pago.',
            'Seleccione método y monto exacto, luego confirme operación.',
            'Si hay error operativo, use anulación con motivo documentado.',
            'Ejecute cierre de caja al final del turno con conciliación básica.',
        ],
        'recomendaciones' => [
            'Restringa anulaciones a perfiles autorizados.',
            'Revise reportes diarios para detectar desvíos tempranos.',
        ],
        'errores' => [
            'Registrar pagos sin relación a orden válida.',
            'Cerrar caja sin validar diferencias entre sistema y caja física.',
        ],
    ],
    [
        'id' => 'notificaciones',
        'titulo' => 'Notificaciones',
        'icono' => '🔔',
        'ruta' => ['/notificacion/index'],
        'proposito' => 'Centralizar alertas operativas para reducir omisiones y mejorar tiempos de respuesta.',
        'operaciones' => [
            'Ver bandeja de notificaciones pendientes/no leídas.',
            'Marcar mensajes como leídos de forma individual o masiva.',
            'Configurar preferencias y plantillas (perfil administrador).',
        ],
        'pasoAPaso' => [
            'Revise contador en topbar al iniciar turno y durante cambios de estado.',
            'Abra alertas críticas primero (citas, stock, entregas).',
            'Marque leídas solo cuando la acción requerida haya sido ejecutada.',
            'Ajuste preferencias para no saturar al equipo con eventos irrelevantes.',
        ],
        'recomendaciones' => [
            'Mantenga plantillas claras y orientadas a acción.',
            'No ignore notificaciones repetidas: suelen indicar un proceso no cerrado.',
        ],
        'errores' => [
            'Marcar todo como leído sin ejecutar tareas asociadas.',
            'Desactivar notificaciones clave de operación.',
        ],
    ],
    [
        'id' => 'inventario',
        'titulo' => 'Inventario',
        'icono' => '📦',
        'ruta' => ['/inventario/index'],
        'proposito' => 'Asegurar disponibilidad de insumos y repuestos con control de stock, entradas, ajustes y gestión de proveedores.',
        'operaciones' => [
            'Crear ítems y mantener stock mínimo/referencias de compra.',
            'Registrar entradas por compra y ajustes por diferencias.',
            'Desactivar ítems obsoletos sin perder histórico.',
            'Gestionar proveedores y órdenes de compra.',
            'Recibir órdenes de compra y actualizar stock automáticamente.',
        ],
        'pasoAPaso' => [
            'Monitoree ítems críticos con frecuencia diaria.',
            'Registre entradas inmediatamente al recepcionar proveedor.',
            'Documente motivo de ajustes para trazabilidad.',
            'Coordine con órdenes activas para evitar quiebres de stock.',
            'Cree órdenes de compra cuando el stock alcance el punto de reorden.',
            'Al recibir orden de compra, verifique cantidades y actualice inventario.',
        ],
        'recomendaciones' => [
            'Defina stock mínimo realista por rotación del repuesto.',
            'Priorice exactitud en cantidades antes de cierres semanales.',
            'Mantenga información actualizada de proveedores clave.',
        ],
        'errores' => [
            'Ajustes sin motivo o referencia de respaldo.',
            'No actualizar inventario tras consumos relevantes.',
            'Retrasar recepción de órdenes de compra afectando disponibilidad.',
        ],
    ],
    [
        'id' => 'proveedores',
        'titulo' => 'Proveedores',
        'icono' => '🚚',
        'ruta' => ['/proveedor/index'],
        'proposito' => 'Gestionar información de proveedores de repuestos e insumos para facilitar compras y reposición.',
        'operaciones' => [
            'Crear y editar fichas de proveedores con datos de contacto completos.',
            'Asociar productos especializados por proveedor.',
            'Desactivar proveedores no vigentes sin perder histórico.',
        ],
        'pasoAPaso' => [
            'Registre proveedor con RUT, nombre, datos de contacto y condiciones comerciales.',
            'Especifique especialidades o líneas de productos que provee.',
            'Valide datos antes de asociar a órdenes de compra.',
            'Actualice información ante cambios de contacto o condiciones.',
        ],
        'recomendaciones' => [
            'Mantenga al menos 2 proveedores alternativos por categoría crítica.',
            'Documente plazos de entrega y políticas de devolución.',
            'Revise periódicamente desempeño de proveedores clave.',
        ],
        'errores' => [
            'Registrar proveedores duplicados por variaciones de nombre.',
            'Omitir datos de contacto secundarios (email, teléfono alternativo).',
            'No actualizar condiciones comerciales negociadas.',
        ],
    ],
    [
        'id' => 'ordenes-compra',
        'titulo' => 'Órdenes de Compra',
        'icono' => '📋',
        'ruta' => ['/orden-compra/index'],
        'proposito' => 'Gestionar solicitudes de compra a proveedores con seguimiento de estado y recepción.',
        'operaciones' => [
            'Crear órdenes de compra vinculadas a proveedores.',
            'Agregar múltiples items con cantidades y precios.',
            'Seguir estado de órdenes (borrador, enviada, recibida, cancelada).',
            'Recibir órdenes y actualizar inventario automáticamente.',
        ],
        'pasoAPaso' => [
            'Seleccione proveedor y agregue items requeridos.',
            'Verifique cantidades y precios antes de enviar.',
            'Cambie estado a "Enviada" cuando confirme transmisión al proveedor.',
            'Al llegar mercadería, use función "Recibir" para validar items.',
            'El sistema actualizará automáticamente el stock tras recepción.',
        ],
        'recomendaciones' => [
            'Use órdenes de compra para todo ingreso que no sea ajuste directo.',
            'Mantenga trazabilidad de cotizaciones asociadas.',
            'Revise pendientes de recepción semanalmente.',
        ],
        'errores' => [
            'Crear órdenes sin proveedor válido.',
            'Recibir cantidades diferentes sin registrar discrepancias.',
            'Dejar órdenes enviadas sin cerrar por largo tiempo.',
        ],
    ],
    [
        'id' => 'servicios',
        'titulo' => 'Servicios',
        'icono' => '🛠️',
        'ruta' => ['/servicio/index'],
        'proposito' => 'Definir catálogo de servicios con costos base, parámetros para cotización/ejecución y análisis de rentabilidad.',
        'operaciones' => [
            'Crear y actualizar servicios del taller.',
            'Activar/desactivar servicios según vigencia comercial.',
            'Exportar catálogo para análisis y coordinación comercial.',
            'Configurar duración estimada en minutos para cada servicio (usado en cálculo de citas y órdenes).',
            'Analizar rentabilidad por servicio con métricas de margen y contribución.',
            'Gestionar plantillas de checklist personalizables por tipo de servicio.',
        ],
        'pasoAPaso' => [
            'Revise que el servicio pertenezca a categoría correcta.',
            'Ingrese descripción clara, tiempo estimado (duración en minutos) y precio base.',
            'La duración estimada ingresada se utilizará automáticamente para calcular el tiempo total de citas y órdenes que incluyan este servicio.',
            'Valide impacto en órdenes activas antes de desactivar.',
            'Comuníquelo a recepción para presupuestos consistentes.',
            'Configure plantillas de checklist para estandarizar la ejecución del servicio.',
            'Revise reportes de rentabilidad periódicamente para ajustar precios o costos.',
        ],
        'recomendaciones' => [
            'Estandarice nombres para búsquedas rápidas y reportes.',
            'Audite precios base según costos reales de insumo y mano de obra.',
            'Mantenga actualizadas las duraciones estimadas según tiempos reales de ejecución.',
            'Revise periódicamente que las duraciones configuradas reflejen la operación real.',
            'Use análisis de rentabilidad para identificar servicios poco rentables.',
        ],
        'errores' => [
            'Servicios duplicados con nombres ligeramente distintos.',
            'Cambiar precios sin registrar criterio operativo/comercial.',
            'Configurar duraciones estimadas irreales que afectan la planificación de agenda.',
            'Ignorar indicadores de rentabilidad al definir estrategia de servicios.',
        ],
    ],
    [
        'id' => 'categorias',
        'titulo' => 'Categorías',
        'icono' => '🗂️',
        'ruta' => ['/categoria/index'],
        'proposito' => 'Agrupar servicios para orden, análisis y mantenimiento de catálogo.',
        'operaciones' => [
            'Crear categorías para organizar servicios.',
            'Editar o desactivar categorías no vigentes.',
            'Mantener estructura limpia para filtros y reportes.',
        ],
        'pasoAPaso' => [
            'Defina categoría con nombre único y representativo.',
            'Asocie servicios relacionados para facilitar búsqueda operativa.',
            'Antes de desactivar, confirme que no afecte servicios activos críticos.',
        ],
        'recomendaciones' => [
            'Use categorías pocas y claras para no fragmentar catálogo.',
            'Revise semestralmente su vigencia con jefatura técnica.',
        ],
        'errores' => [
            'Categorías redundantes que dificultan navegación.',
            'Desactivar categorías sin revisar impacto funcional.',
        ],
    ],
    [
        'id' => 'tecnicos',
        'titulo' => 'Técnicos',
        'icono' => '👨‍🔧',
        'ruta' => ['/tecnico/index'],
        'proposito' => 'Gestionar personal técnico, capacidades y disponibilidad para asignación eficiente.',
        'operaciones' => [
            'Registrar técnicos con datos laborales y estado.',
            'Actualizar perfil y certificaciones relevantes.',
            'Desactivar registros cuando no correspondan a dotación activa.',
        ],
        'pasoAPaso' => [
            'Mantenga datos de contacto y estado contractual actualizados.',
            'Asocie especialidades/certificaciones según evidencia.',
            'Revise carga de órdenes para equilibrar asignaciones.',
        ],
        'recomendaciones' => [
            'Documente certificaciones con periodicidad de vencimiento.',
            'Cruce capacidades técnicas con tipo de servicio más demandado.',
        ],
        'errores' => [
            'Asignar trabajos especializados sin validar competencia.',
            'No actualizar estado de técnicos ausentes o desvinculados.',
        ],
    ],
    [
        'id' => 'especialidades',
        'titulo' => 'Especialidades',
        'icono' => '🎯',
        'ruta' => ['/especialidad/index'],
        'proposito' => 'Definir dominios técnicos para clasificar capacidades del equipo y mejorar asignaciones.',
        'operaciones' => [
            'Crear especialidades técnicas por área (motor, frenos, electrónica, etc.).',
            'Editar/desactivar especialidades según evolución del taller.',
            'Usar especialidades para mapear técnicos idóneos a cada orden.',
        ],
        'pasoAPaso' => [
            'Cree especialidad con nombre claro y alcance funcional.',
            'Revise técnicos vinculados antes de cambios mayores.',
            'Alinee nomenclatura con procesos de capacitación interna.',
        ],
        'recomendaciones' => [
            'Mantenga catálogo simple y orientado a operación real.',
            'Evite especialidades excesivamente específicas sin uso frecuente.',
        ],
        'errores' => [
            'Multiplicar especialidades casi idénticas.',
            'No depurar especialidades obsoletas.',
        ],
    ],
    [
        'id' => 'configuracion',
        'titulo' => 'Configuración',
        'icono' => '⚙️',
        'ruta' => ['/admin/database'],
        'proposito' => 'Gestionar parámetros administrativos y utilidades de soporte del sistema.',
        'operaciones' => [
            'Acceder a herramientas administrativas de base de datos/configuración.',
            'Ejecutar operaciones de mantenimiento controladas por perfil autorizado.',
            'Monitorear estado general de configuración operativa.',
        ],
        'pasoAPaso' => [
            'Ingrese solo con rol autorizado y bajo procedimiento aprobado.',
            'Evalúe impacto antes de cambios estructurales o de datos.',
            'Registre intervención realizada para trazabilidad interna.',
        ],
        'recomendaciones' => [
            'Aplicar cambios críticos fuera de horarios de máxima demanda.',
            'Respaldar información sensible antes de intervenciones mayores.',
        ],
        'errores' => [
            'Modificar configuración sin evaluación previa.',
            'Ejecutar acciones técnicas desde perfiles no autorizados.',
        ],
    ],
    [
        'id' => 'auditoria',
        'titulo' => 'Auditoría',
        'icono' => '📜',
        'ruta' => ['/audit-log/index'],
        'proposito' => 'Rastrear eventos relevantes de seguridad y operación para cumplimiento y diagnóstico.',
        'operaciones' => [
            'Consultar logs de acciones críticas por usuario/fecha/módulo.',
            'Filtrar eventos para investigaciones internas y control de cambios.',
            'Apoyar trazabilidad ante incidentes operativos o de seguridad.',
        ],
        'pasoAPaso' => [
            'Defina ventana temporal del incidente a investigar.',
            'Filtre por usuario y módulo involucrado.',
            'Correlacione acción, fecha y resultado con el caso reportado.',
            'Eleve hallazgos con evidencia si requiere acción disciplinaria o técnica.',
        ],
        'recomendaciones' => [
            'Realice revisiones periódicas de acciones sensibles.',
            'Conserve enfoque de evidencia y no de suposición.',
        ],
        'errores' => [
            'Analizar registros fuera del rango de tiempo correcto.',
            'Concluir incidentes sin contrastar múltiples eventos relacionados.',
        ],
    ],
    [
        'id' => 'roles',
        'titulo' => 'Roles',
        'icono' => '🛡️',
        'ruta' => ['/rol/index'],
        'proposito' => 'Administrar perfiles de acceso y permisos por función dentro del taller.',
        'operaciones' => [
            'Crear/editar roles con permisos por módulo.',
            'Revisar alcance de permisos para evitar sobreprivilegios.',
            'Ajustar roles ante cambios organizacionales.',
        ],
        'pasoAPaso' => [
            'Defina responsabilidades del rol antes de asignar permisos.',
            'Otorgue mínimo acceso necesario para ejecutar tareas.',
            'Valide impacto en navegación y acciones críticas.',
            'Documente cambios por control interno.',
        ],
        'recomendaciones' => [
            'Aplique principio de mínimo privilegio.',
            'Revise permisos trimestralmente con liderazgo operativo.',
        ],
        'errores' => [
            'Asignar permisos globales por conveniencia temporal.',
            'No retirar permisos a roles obsoletos.',
        ],
    ],
    [
        'id' => 'usuarios',
        'titulo' => 'Usuarios',
        'icono' => '👤',
        'ruta' => ['/usuario/index'],
        'proposito' => 'Gestionar cuentas de acceso, estado y seguridad del personal.',
        'operaciones' => [
            'Crear usuarios y asignar rol correspondiente.',
            'Actualizar perfil y forzar cambios de contraseña cuando aplique.',
            'Desactivar usuarios inactivos o desvinculados.',
        ],
        'pasoAPaso' => [
            'Cree cuenta con identidad verificable y correo corporativo.',
            'Asigne rol según función real del colaborador.',
            'Valide primer inicio de sesión y cambio de contraseña.',
            'Desactive inmediatamente cuentas fuera de dotación.',
        ],
        'recomendaciones' => [
            'Evite cuentas compartidas entre personas.',
            'Aplique rotación de contraseñas para perfiles sensibles.',
        ],
        'errores' => [
            'Mantener cuentas activas de personal que ya no trabaja.',
            'Asignar rol incorrecto y exponer funciones críticas.',
        ],
    ],
];
?>

<div class="manual-page">
    <section class="manual-hero">
        <div>
            <h1><?= Html::encode($this->title) ?></h1>
            <p>
                Guía operativa integral de TOMAKO. Este documento concentra el uso práctico de cada módulo,
                con procesos paso a paso, recomendaciones y errores frecuentes para estandarizar la operación diaria.
            </p>
        </div>
        <div class="manual-hero-meta">
            <div><strong>Alcance:</strong> Operación completa del taller</div>
            <div><strong>Audiencia:</strong> Recepción, técnicos, jefatura y administración</div>
            <div><strong>Actualización:</strong> <?= date('d/m/Y') ?></div>
        </div>
    </section>

    <section class="manual-layout">
        <aside class="manual-index ts-panel" aria-label="Índice del manual">
            <h2>Índice de módulos</h2>
            <ul>
                <?php foreach ($modulos as $modulo): ?>
                    <li>
                        <a href="#<?= Html::encode($modulo['id']) ?>">
                            <span class="manual-index-icon" aria-hidden="true"><?= Html::encode($modulo['icono']) ?></span>
                            <span><?= Html::encode($modulo['titulo']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <div class="manual-content">
            <?php foreach ($modulos as $modulo): ?>
                <article id="<?= Html::encode($modulo['id']) ?>" class="manual-section ts-panel">
                    <header class="manual-section-header">
                        <h2>
                            <span aria-hidden="true"><?= Html::encode($modulo['icono']) ?></span>
                            <?= Html::encode($modulo['titulo']) ?>
                        </h2>
                        <a class="btn btn-sm btn-outline-primary" href="<?= Url::to($modulo['ruta']) ?>">Ir al módulo</a>
                    </header>

                    <div class="manual-grid">
                        <section>
                            <h3>Objetivo del módulo</h3>
                            <p><?= Html::encode($modulo['proposito']) ?></p>
                        </section>

                        <section>
                            <h3>Acciones principales</h3>
                            <ul>
                                <?php foreach ($modulo['operaciones'] as $item): ?>
                                    <li><?= Html::encode($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>

                        <section>
                            <h3>Flujo recomendado</h3>
                            <ol>
                                <?php foreach ($modulo['pasoAPaso'] as $paso): ?>
                                    <li><?= Html::encode($paso) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </section>

                        <section>
                            <h3>Buenas prácticas</h3>
                            <ul>
                                <?php foreach ($modulo['recomendaciones'] as $tip): ?>
                                    <li><?= Html::encode($tip) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>

                        <section>
                            <h3>Errores frecuentes a evitar</h3>
                            <ul class="manual-warning-list">
                                <?php foreach ($modulo['errores'] as $error): ?>
                                    <li><?= Html::encode($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>

                        <?php if (!empty($modulo['indicadores'])): ?>
                        <section>
                            <h3>Indicadores del Dashboard</h3>
                            <p class="text-muted mb-3">Descripción e interpretación de los KPIs disponibles en el Panel de Control:</p>
                            <div class="row g-3">
                                <?php foreach ($modulo['indicadores'] as $indicador): ?>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="card h-100 shadow-sm border-0 bg-light">
                                            <div class="card-body">
                                                <h5 class="card-title text-primary"><?= Html::encode($indicador['nombre']) ?></h5>
                                                <p class="card-text small"><strong>Descripción:</strong> <?= Html::encode($indicador['descripcion']) ?></p>
                                                <p class="card-text small"><strong>Interpretación:</strong> <?= Html::encode($indicador['interpretacion']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
