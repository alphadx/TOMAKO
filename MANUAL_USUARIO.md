# 📘 TOMAKO - Manual de Usuario

> **Sistema de Gestión de Taller Mecánico**  
> *Tu auto necesita TOMAKO* 🍅  
> **Versión**: 1.0 | **Última actualización**: Mayo 2025

---

## 📋 Índice General

1. [Introducción](#introducción)
2. [Primeros Pasos](#primeros-pasos)
3. [Módulos del Sistema](#módulos-del-sistema)
   - [Dashboard](#dashboard)
   - [Clientes](#clientes)
   - [Vehículos](#vehículos)
   - [Citas](#citas)
   - [Órdenes de Servicio](#órdenes-de-servicio)
   - [Servicios](#servicios)
   - [Inventario](#inventario)
   - [Seguimiento](#seguimiento)
4. [Guías Rápidas](#guías-rápidas)
5. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 🎯 Introducción

**TOMAKO** es un sistema integral de gestión para talleres mecánicos automotrices que centraliza todas las operaciones del taller en una plataforma moderna.

### ¿Qué puedes hacer con TOMAKO?

- ✅ Gestionar clientes y sus vehículos
- ✅ Agendar citas y seguimientos post-servicio
- ✅ Crear órdenes de servicio con checklists personalizables
- ✅ Visualizar el flujo de trabajo en tiempo real (Kanban)
- ✅ Controlar inventario de repuestos y proveedores
- ✅ Analizar rentabilidad por servicio
- ✅ Generar reportes técnicos y de gestión

### Roles de Usuario

| Rol | Permisos |
|-----|----------|
| **Administrador** | Acceso completo a todos los módulos y configuración |
| **Recepcionista** | Clientes, vehículos, citas, órdenes de servicio (creación) |
| **Técnico** | Órdenes asignadas, checklists, registro de trabajos |
| **Jefe de Taller** | Todas las órdenes, inventario, reportes, seguimiento |

---

## 🚀 Primeros Pasos

### 1. Iniciar Sesión

1. Abre tu navegador y ve a la URL del sistema
2. Ingresa tu **usuario** y **contraseña**
3. Haz clic en **"Ingresar"**

> 🔐 **Nota**: Si olvidaste tu contraseña, contacta al administrador del sistema.

### 2. Conociendo la Interfaz

#### Barra de Navegación Superior
- **Logo TOMAKO**: Vuelve al dashboard
- **Menú principal**: Accede a los módulos del sistema
- **Usuario**: Perfil y cerrar sesión

#### Menú Lateral (Sidebar)
Organizado por módulos con submenús desplegables:

```
📊 Dashboard
👥 Clientes
   └─ Listado
   └─ Etiquetas
🚗 Vehículos
📅 Citas
🔧 Órdenes de Servicio
   ├─ Listado
   ├─ Kanban
   ├─ Reporte Técnico
   └─ Reporte Checklist
📦 Inventario
   ├─ Listado
   ├─ Proveedores
   └─ Órdenes de Compra
📞 Seguimiento
   ├─ Agenda
   ├─ Pendientes
   └─ Reportes
⚙️ Servicios
   ├─ Listado
   ├─ Rentabilidad
   └─ Calculadora
```

### 3. Generar Datos Demo (Opcional)

Para propósitos de prueba y capacitación, puedes generar datos históricos de demostración usando el comando de consola:

```bash
# Generar 31 días de datos demo desde una fecha específica
php yii demo-data/sembrar-mes 2026-05-26 31 1
```

**Parámetros:**
- **Fecha inicial**: formato YYYY-MM-DD (ej: `2026-05-26`)
- **Días**: mínimo 30 días de historia operativa
- **Limpiar**: `1` para borrar datos demo anteriores, `0` para conservar

**¿Qué genera este comando?**
- Clientes de ejemplo con etiquetas (Frecuente, VIP, Nuevo)
- Vehículos asociados a cada cliente
- Citas programadas en el período indicado
- Órdenes de servicio con estados variables
- Pagos registrados
- Movimientos de inventario
- Notificaciones del sistema

Al finalizar, el comando muestra un resumen con las cantidades generadas y crea un archivo Markdown con la historia operativa del período simulado.

> ⚠️ **Advertencia**: Este comando elimina datos demo anteriores si el parámetro de limpieza es `1`. No lo uses en entornos de producción con datos reales.

---

## 📊 Módulos del Sistema

### Dashboard

**Propósito**: Panel ejecutivo con KPIs en tiempo real

#### ¿Qué ves en el Dashboard?

| KPI | Descripción |
|-----|-------------|
| 📈 Servicios Activos | Órdenes de servicio en curso |
| 📅 Citas del Día | Citas programadas para hoy |
| ⚠️ Stock Bajo | Productos con inventario mínimo |
| 💰 Ingresos del Mes | Total facturado en el mes actual |
| ✅ Trabajos Listos | Órdenes listas para entrega |
| 📦 Valor Inventario | Valor total del stock almacenado |

#### Cómo usar el Dashboard

1. **Visualización rápida**: Los KPIs se actualizan automáticamente
2. **Filtros de fecha**: Usa el selector en la esquina superior derecha
3. **Acceso directo**: Haz clic en cualquier KPI para ver el detalle

---

### 👥 Clientes

**Propósito**: Gestionar la base de datos de clientes del taller

#### Funcionalidades Principales

##### 1. Listado de Clientes

- **Ver todos los clientes** con información completa
- **Buscar** por nombre, RUT, email o teléfono
- **Filtrar** por etiqueta de segmentación
- **Exportar** listados a CSV

**Campos del cliente**:
- Nombre completo
- RUT (con validación automática)
- Email y teléfono
- Fecha de nacimiento (para cumpleaños)
- Fuente de captación (cómo llegó al taller)
- Preferencias de contacto
- Etiquetas de segmentación

##### 2. Etiquetas de Segmentación

Las etiquetas permiten clasificar clientes para campañas y análisis:

**Ejemplos de etiquetas**:
- 🏆 Cliente Frecuente
- 🚗 Flota Empresarial
- 👤 Nuevo Cliente
- ⭐ VIP
- 🔧 Solo Mantenimiento

**Cómo crear una etiqueta**:
1. Ve a **Clientes → Etiquetas**
2. Haz clic en **"Nueva Etiqueta"**
3. Ingresa nombre, color y descripción
4. Guarda la etiqueta

**Cómo asignar etiquetas**:
1. Abre la ficha del cliente
2. En la sección "Etiquetas", haz clic en **"Asignar"**
3. Selecciona una o más etiquetas
4. Guarda los cambios

##### 3. Ficha Completa del Cliente

Al hacer clic en un cliente, ves:

- **Información personal**: Datos de contacto, RUT, cumpleaños
- **Vehículos asociados**: Lista de autos registrados
- **Historial de servicios**: Todas las órdenes de servicio
- **Etiquetas**: Segmentación asignada
- **Notas internas**: Comentarios del equipo

**Acciones disponibles**:
- ✏️ Editar información
- ➕ Agregar vehículo
- 🏷️ Gestionar etiquetas
- 📤 Exportar historial

---

### 🚗 Vehículos

**Propósito**: Administrar vehículos y su historial de mantenimiento

#### Funcionalidades

- **Registrar nuevo vehículo**: Patente, VIN, marca, modelo, año, color
- **Validación automática**: Formato de patente chilena (antiguo y nuevo)
- **Historial completo**: Todas las intervenciones realizadas
- **Búsqueda rápida**: Por patente, VIN o propietario

**Datos del vehículo**:
- Patente (validada automáticamente)
- VIN (Vehicle Identification Number)
- Marca, modelo, año
- Color
- Kilometraje actual
- Propietario (vinculado a cliente)
- Historial de servicios

---

### 📅 Citas

**Propósito**: Agendar citas de manera eficiente evitando solapamientos

#### Cómo agendar una cita

1. Ve a **Citas → Nueva Cita**
2. Completa el formulario:
   - **Cliente**: Busca y selecciona
   - **Vehículo**: Selecciona del cliente
   - **Fecha y hora**: El sistema valida disponibilidad
   - **Servicios**: Agrega uno o múltiples servicios
   - **Duración estimada**: Se calcula automáticamente
   - **Notas**: Instrucciones especiales
3. Haz clic en **"Guardar Cita"**

#### Estados de las Citas

| Estado | Descripción |
|--------|-------------|
| 🟡 Programada | Cita confirmada, pendiente de ejecutar |
| 🟢 En Progreso | Cliente llegó, servicio en ejecución |
| ✅ Completada | Servicio finalizado |
| ❌ Cancelada | Cita cancelada por cliente o taller |
| 🔄 Reprogramada | Cambiada a nueva fecha/hora |

#### Funcionalidades Avanzadas

- **Validación de solapamientos**: El sistema previene duplicaciones
- **Reprogramación flexible**: Cambia fecha/hora manteniendo historial
- **Recordatorios automáticos**: Notificaciones antes de la cita
- **Vista calendario**: Visualización mensual/semanal/diaria

---

### 🔧 Órdenes de Servicio

**Propósito**: Gestionar el ciclo completo de cada intervención técnica

#### Funcionalidades Principales

##### 1. Listado de Órdenes

- **Ver todas las órdenes** con estado actual
- **Filtrar** por técnico, fecha, prioridad, estado
- **Buscar** por número de orden, cliente o patente
- **Acciones rápidas**: Ver, editar, imprimir

##### 2. Vista Kanban

**¿Qué es el Kanban?**  
Tablero visual que muestra el flujo de órdenes por estado.

**Columnas del Kanban**:
```
📝 Pendiente → 🔍 En Diagnóstico → 🔧 En Reparación → 
✅ En Revisión → 🧼 Lavado → 📦 Listo para Entrega
```

**Cómo usar el Kanban**:
1. Ve a **Órdenes de Servicio → Kanban**
2. **Arrastra y suelta** las tarjetas entre columnas
3. El estado se actualiza automáticamente
4. **Filtra** por técnico, fecha o prioridad
5. Haz clic en una tarjeta para ver **detalle rápido**

**Alertas visuales**:
- 🔴 Rojo: Tiempo excesivo en estado actual
- 🟡 Amarillo: Próximo a vencer plazo estimado
- 🟢 Verde: Dentro del tiempo estimado

##### 3. Crear Orden de Servicio

**Pasos para crear una orden**:

1. **Información básica**:
   - Cliente y vehículo (búsqueda rápida)
   - Tipo de servicio (mantenimiento, reparación, diagnóstico)
   - Prioridad (normal, urgente, VIP)

2. **Diagnóstico inicial**:
   - Descripción del problema reportado por el cliente
   - Kilometraje actual
   - Nivel de combustible
   - Observaciones de recepción

3. **Servicios a realizar**:
   - Agrega servicios del catálogo
   - Cada servicio carga su **checklist automático**
   - Estima tiempo y costo

4. **Repuestos necesarios**:
   - Busca productos del inventario
   - Reserva stock automáticamente
   - Calcula costo de materiales

5. **Asignación**:
   - Asigna técnico responsable
   - Establece fecha límite de entrega

6. **Documentación adjunta**:
   - 📸 **Sube fotos** del vehículo (estado inicial, daños)
   - 📄 **Adjunta documentos** (presupuestos aprobados, autorizaciones)
   - Sistema drag & drop múltiple
   - Compresión automática de imágenes
   - Galería con preview

7. **Guardar y continuar**:
   - La orden queda en estado "Pendiente"
   - Notifica al técnico asignado
   - Programa seguimiento post-servicio automático

##### 4. Checklists Personalizables

**¿Qué son los checklists?**  
Listas de verificación que aseguran calidad y consistencia en cada servicio.

**Checklists por tipo de servicio**:

| Servicio | Items típicos del checklist |
|----------|---------------------------|
| **Cambio de Aceite** | ✓ Drenar aceite usado<br>✓ Reemplazar filtro<br>✓ Llenar aceite nuevo<br>✓ Verificar nivel<br>✓ Inspeccionar fugas |
| **Frenos** | ✓ Medir espesor pastillas<br>✓ Revisar discos<br>✓ Verificar líquido de frenos<br>✓ Purgar sistema si corresponde<br>✓ Test de frenado |
| **Neumáticos** | ✓ Revisar presión<br>✓ Verificar desgaste<br>✓ Rotar posición<br>✓ Balancear ruedas<br>✓ Alinear dirección |

**Cómo funcionan**:

1. **Plantillas predefinidas**: Cada servicio tiene su plantilla
2. **Carga automática**: Al crear la orden, se cargan los items
3. **Marcado en terreno**: El técnico marca cada item completado
4. **Fotos opcionales**: Adjunta evidencia fotográfica
5. **Porcentaje de avance**: Se calcula automáticamente
6. **Reporte de cumplimiento**: Supervisor revisa % completado

**Reporte de Checklist**:
- Ve a **Órdenes de Servicio → Reporte Checklist**
- Filtra por período, servicio o técnico
- Visualiza % de cumplimiento por orden
- Identifica oportunidades de mejora

##### 5. Fotos y Documentos

**Sistema de archivos adjuntos**:

- **Drag & Drop múltiple**: Arrastra varios archivos a la vez
- **Tipos soportados**: JPG, PNG, PDF, DOC, XLS
- **Compresión automática**: Imágenes optimizadas sin perder calidad
- **Thumbnails**: Vista previa de todas las imágenes
- **Galería fullscreen**: Haz clic para ver en tamaño completo
- **Eliminación segura**: Borra archivos no deseados

**Casos de uso**:
- 📸 Fotos del estado inicial del vehículo
- 📸 Evidencia de daños encontrados
- 📸 Fotos del trabajo realizado
- 📄 Presupuestos aprobados por el cliente
- 📄 Autorizaciones firmadas
- 📄 Facturas de repuestos

##### 6. Reporte Técnico

**¿Qué es el Reporte Técnico?**  
Documento profesional que resume toda la intervención.

**Contenido del reporte**:
- Información del cliente y vehículo
- Diagnóstico inicial
- Servicios realizados (con checklists completados)
- Repuestos utilizados
- Mano de obra aplicada
- Fotos del antes y después
- Costos detallados
- Recomendaciones futuras

**Cómo generar**:
1. Ve a **Órdenes de Servicio → Reporte Técnico**
2. Selecciona la orden
3. Elige formato (PDF o impresión directa)
4. Descarga o imprime

---

### ⚙️ Servicios

**Propósito**: Catálogo de servicios y análisis de rentabilidad

#### Funcionalidades

##### 1. Listado de Servicios

- **Ver todos los servicios** del catálogo
- **Crear nuevo servicio**: Nombre, código, precio, duración
- **Editar servicios existentes**: Actualiza precios y tiempos
- **Activar/desactivar**: Sin eliminar del historial

**Datos del servicio**:
- Código único (ej: `SERV-001`)
- Nombre descriptivo
- Precio base
- Duración estimada (horas)
- Categoría (mantenimiento, reparación, diagnóstico)
- Checklist asociado
- Histórico de precios

##### 2. Análisis de Rentabilidad

**¿Qué ves en Rentabilidad?**

| Métrica | Descripción |
|---------|-------------|
| **Margen Bruto** | (Precio - Costos) / Precio × 100 |
| **Costo Variable** | Mano de obra + repuestos + overhead variable (20%) |
| **Costo Fijo** | Overhead fijo asignado por hora |
| **Utilidad Neta** | Margen después de todos los costos |

**Gráficos incluidos**:
- 📊 **Top 10 servicios más rentables**
- 📉 **Bottom 5 servicios menos rentables**
- 📈 **Comparativa vs período anterior**
- 🎯 **Margen promedio del taller**

**Cómo usar**:
1. Ve a **Servicios → Rentabilidad**
2. Selecciona período de análisis (mes, trimestre, año)
3. Revisa KPIs y gráficos
4. Identifica servicios a ajustar
5. Exporta a Excel o PDF para presentar

**Acciones recomendadas**:
- 🔼 **Aumentar precio** en servicios con margen bajo
- 🔽 **Reducir costos** negociando con proveedores
- ❌ **Descontinuar** servicios consistentemente no rentables
- ⭐ **Promocionar** servicios altamente rentables

##### 3. Calculadora de Precios

**Herramienta para cotizar servicios**:

1. **Selecciona servicio base** del catálogo
2. **Agrega repuestos** necesarios (busca por SKU o nombre)
3. **Ajusta mano de obra** (horas × costo por hora del técnico)
4. **Calcula automáticamente**:
   - Subtotal repuestos
   - Subtotal mano de obra
   - Overhead (20% variable + fijo)
   - **Total final**
5. **Genera cotización** imprimible o enviable por email

---

### 📦 Inventario

**Propósito**: Control de stock de repuestos y gestión de proveedores

#### Funcionalidades

##### 1. Listado de Productos

- **Ver todo el inventario** con stock actual
- **Buscar** por SKU, nombre, categoría o proveedor
- **Filtrar** por stock bajo, categoría, ubicación
- **Alertas automáticas**: Productos bajo stock mínimo

**Datos del producto**:
- SKU (generado automáticamente)
- Nombre y descripción
- Categoría (aceites, filtros, frenos, neumáticos, etc.)
- Stock actual y stock mínimo
- Unidad de medida (unidad, litro, kit, etc.)
- Precio de costo y precio de venta
- Ubicación física (pasillo, estante, caja)
- Proveedor(es) habitual(es)

##### 2. Movimientos de Inventario

**Tipos de movimientos**:

| Tipo | Descripción | Ejemplo |
|------|-------------|---------|
| 📥 Entrada | Compra o devolución | Recepción de orden de compra |
| 📤 Salida | Venta o consumo | Uso en orden de servicio |
| 🔄 Transferencia | Cambio de ubicación | Reorganización de bodega |
| ⚠️ Ajuste | Corrección de stock | Inventario físico vs sistema |

**Cada movimiento registra**:
- Fecha y hora exacta
- Usuario que realizó el movimiento
- Cantidad anterior y nueva
- Motivo del movimiento
- Documento relacionado (OC, OS, etc.)

##### 3. Proveedores

**Gestión de proveedores de repuestos**:

**Registro de proveedor**:
1. Ve a **Inventario → Proveedores**
2. Haz clic en **"Nuevo Proveedor"**
3. Completa:
   - Razón social y RUT
   - Datos de contacto (email, teléfono, dirección)
   - Persona de contacto
   - Condiciones de pago (contado, 30 días, etc.)
   - Tiempo de entrega promedio
   - Categorías de productos que provee
4. Guarda el proveedor

**Lista de proveedores**:
- Visualiza todos los proveedores activos
- Filtra por categoría o estado
- Ve historial de compras por proveedor
- Accede a evaluaciones de desempeño

##### 4. Órdenes de Compra

**Proceso de compra**:

**1. Crear orden de compra**:
1. Ve a **Inventario → Órdenes de Compra**
2. Haz clic en **"Nueva Orden"**
3. Selecciona proveedor
4. Agrega productos:
   - Busca por SKU o nombre
   - Ingresa cantidad solicitada
   - Verifica precio unitario
5. Revisa totales calculados automáticamente
6. Guarda como **borrador** o **envía** al proveedor

**Estados de la orden**:
```
📝 Borrador → 📤 Enviada → 📦 Recibida Parcial → 
✅ Recibida Completo / ❌ Cancelada
```

**2. Seguimiento de orden**:
- Visualiza estado actual en tiempo real
- Ve fecha de envío y fecha estimada de entrega
- Monitorea progreso de recepción por item

**3. Recepción de productos**:
1. Abre la orden recibida
2. Haz clic en **"Recibir Productos"**
3. Para cada item:
   - Ingresa cantidad recibida (puede ser parcial)
   - El sistema actualiza stock automáticamente
   - Registra movimiento de entrada
4. El estado cambia según corresponda:
   - **Recibida Parcial**: Si faltan items
   - **Recibida Completo**: Si llegaron todos los items
5. **Actualización automática**:
   - Stock incrementado en inventario
   - Movimiento registrado en historial
   - Precio de costo actualizado (promedio ponderado)

**Características avanzadas**:
- 📊 **KPIs de compras**: Total órdenes, pendientes, monto del mes
- 📈 **Progreso visual**: Barras de progreso por item
- ⚠️ **Validaciones de estado**: Solo acciones permitidas según estado
- 🔍 **Historial completo**: Todas las compras por proveedor

##### 5. Evaluación de Proveedores

**Sistema de evaluación de desempeño**:

**Métricas evaluadas** (escala 1-5):
- ⏱️ **Puntualidad**: Cumplimiento de fechas de entrega
- 🏆 **Calidad**: Productos sin defectos
- 📞 **Atención**: Respuesta y soporte
- 💰 **Precio**: Competitividad vs mercado
- 🔄 **Flexibilidad**: Facilidad para cambios/devoluciones

**Cómo evaluar**:
1. Ve a **Inventario → Proveedores → Evaluar**
2. Selecciona proveedor y período
3. Califica cada métrica (1-5 estrellas)
4. Agrega comentarios opcionales
5. Guarda la evaluación

**Ranking de proveedores**:
- Visualiza ranking mensual/anual
- Identifica mejores proveedores por categoría
- Toma decisiones basadas en datos

**Evaluación automática**:
- El sistema evalúa **puntualidad** automáticamente al recibir
- Compara fecha prometida vs fecha real de recepción
- Sugiere puntaje basado en desviación

---

### 📞 Seguimiento

**Propósito**: Dar seguimiento post-servicio para asegurar satisfacción del cliente

#### Funcionalidades

##### 1. Agenda de Seguimiento

**Programación automática**:
- Al completar una orden, el sistema programa seguimiento
- Fecha sugerida: 3-5 días después de la entrega
- Personalizable según tipo de servicio

**Vista de agenda**:
- 📅 **Calendario**: Seguimientos por fecha
- 📋 **Lista**: Seguimientos del día/semana
- 🔔 **Recordatorios**: Notificaciones de pendientes

**Cómo gestionar**:
1. Ve a **Seguimiento → Agenda**
2. Visualiza seguimientos programados
3. Filtra por fecha, técnico o estado
4. Haz clic para ver detalle o completar

##### 2. Pendientes del Día

**Lista diaria de seguimiento**:

- Muestra seguimientos programados para hoy
- Indica estado: Pendiente, Completado, Postergado
- Permite completar rápidamente desde la lista

**Acciones rápidas**:
- ✅ **Completar**: Registra resultado del seguimiento
- ⏭️ **Postergar**: Reagenda para otra fecha
- 📝 **Notas**: Agrega observaciones

##### 3. Encuestas de Satisfacción

**Tipos de encuesta**:

| Tipo | Cuándo se usa | Preguntas típicas |
|------|---------------|-------------------|
| **NPS** | Post-servicio general | "¿Qué tan probable es que nos recomiendes?" (0-10) |
| **Satisfacción** | Post-reparación específica | "¿Quedaste conforme con el trabajo?" (1-5) |
| **Calidad** | Post-servicio complejo | "¿El problema quedó resuelto?" (Sí/No) |

**Cómo completar**:
1. Abre el seguimiento pendiente
2. Selecciona tipo de encuesta
3. Registra respuestas del cliente
4. Agrega comentarios adicionales
5. Guarda el resultado

**Resultados predefinidos**:
- 😊 **Promotor** (NPS 9-10): Cliente satisfecho
- 😐 **Neutro** (NPS 7-8): Cliente indiferente
- 😞 **Detractor** (NPS 0-6): Cliente insatisfecho

##### 4. Reportes de Seguimiento

**Métricas disponibles**:

| KPI | Descripción |
|-----|-------------|
| **Tasa de respuesta** | % de clientes contactados que respondieron |
| **NPS Promedio** | Score neto de promotores menos detractores |
| **Satisfacción general** | Promedio de calificaciones (1-5) |
| **Seguimientos completados** | Total de seguimientos realizados |
| **Seguimientos pendientes** | Total de seguimientos por realizar |

**Gráficos incluidos**:
- 📊 Distribución de promotores/neutros/detractores
- 📈 Evolución de NPS por período
- ⭐ Satisfacción por tipo de servicio
- 🎯 Comparativa vs meta mensual

**Cómo generar reportes**:
1. Ve a **Seguimiento → Reportes**
2. Selecciona período (semana, mes, trimestre)
3. Filtra por servicio, técnico o segmento
4. Visualiza gráficos y métricas
5. Exporta a PDF o Excel

---

## 📖 Guías Rápidas

### 🚀 Flujo Completo de Atención

```
1. 📞 Cliente llama → Agendar Cita
2. 🚗 Cliente llega → Verificar cita, registrar vehículo
3. 🔍 Diagnóstico → Crear Orden de Servicio
4. 💬 Aprobar presupuesto → Cliente autoriza
5. 🔧 Ejecutar servicio → Técnico realiza trabajo + checklist
6. 📸 Documentar → Subir fotos del trabajo
7. ✅ Revisión calidad → Supervisor verifica checklist
8. 💰 Cobrar → Generar cuenta, procesar pago
9. 🧼 Lavado → Entregar vehículo limpio
10. 📞 Seguimiento → Contactar cliente a los 3-5 días
```

### 📋 Checklist de Recepción

Al recibir un vehículo:

- [ ] Verificar identidad del cliente
- [ ] Confirmar datos del vehículo (patente, kilometraje)
- [ ] Inspeccionar estado exterior (fotos)
- [ ] Verificar nivel de combustible
- [ ] Registrar objetos personales en el vehículo
- [ ] Escuchar descripción del problema
- [ ] Confirmar servicios a realizar
- [ ] Estimar tiempo y costo
- [ ] Obtener autorización firmada

### 🎯 Tips de Uso Eficiente

#### Atajos de Teclado
| Tecla | Acción |
|-------|--------|
| `Ctrl + N` | Nueva orden de servicio |
| `Ctrl + B` | Buscar cliente/vehículo |
| `Ctrl + G` | Ir al dashboard |
| `Esc` | Cerrar modal/ventana |

#### Mejores Prácticas

1. **Siempre toma fotos** del estado inicial del vehículo
2. **Completa todos los items** del checklist antes de marcar como terminado
3. **Actualiza el estado** en Kanban inmediatamente después de cada acción
4. **Programa seguimientos** para todos los servicios (no solo reparaciones mayores)
5. **Revisa alertas de stock bajo** diariamente
6. **Analiza rentabilidad** mensualmente para ajustar precios

---

## ❓ Preguntas Frecuentes

### General

**¿Puedo acceder desde mi celular?**  
Sí, TOMAKO es responsive y funciona en cualquier dispositivo con navegador.

**¿Necesito instalar algo?**  
No, es un sistema web. Solo necesitas conexión a internet y un navegador actualizado.

**¿Mis datos están seguros?**  
Sí, el sistema cuenta con autenticación segura, roles de usuario y auditoría de todas las operaciones.

### Clientes

**¿Cómo importo clientes desde Excel?**  
Actualmente la importación masiva no está disponible. Debes registrarlos manualmente o solicitar esta funcionalidad al administrador.

**¿Puede un cliente tener múltiples vehículos?**  
Sí, ilimitados. Todos quedan vinculados a su ficha y historial.

### Órdenes de Servicio

**¿Puedo modificar una orden ya creada?**  
Sí, mientras no esté en estado "Entregada". Los cambios quedan registrados en auditoría.

**¿Qué pasa si me equivoco al arrastrar en Kanban?**  
Puedes mover la tarjeta a otra columna cuando quieras. El cambio se guarda automáticamente.

**¿Las fotos se pierden si elimino la orden?**  
Sí, al eliminar una orden se eliminan todos sus archivos adjuntos. Confirma antes de eliminar.

### Inventario

**¿El stock se actualiza automáticamente?**  
Sí, al recibir una orden de compra o consumir repuestos en una orden de servicio.

**¿Qué pasa si llego a stock cero?**  
El sistema muestra alerta visual y notifica al responsable de compras.

**¿Puedo trabajar con múltiples unidades de medida?**  
Sí, puedes configurar unidades (unidad, litro, kg, kit) y el sistema hace conversiones.

### Seguimiento

**¿Los seguimientos son obligatorios?**  
No, pero altamente recomendados para medir satisfacción y fidelizar clientes.

**¿Puedo personalizar las preguntas de la encuesta?**  
Actualmente hay plantillas predefinidas. La personalización requiere configuración del administrador.

### Reportes

**¿Puedo exportar reportes?**  
Sí, la mayoría de los reportes se pueden exportar a Excel (XLSX) o PDF.

**¿Los gráficos se actualizan en tiempo real?**  
Sí, al cambiar filtros o período, los gráficos se recalculan automáticamente.

---

## 📞 Soporte

### Contacto

- **Email de soporte**: soporte@tomako.cl
- **Teléfono**: +56 2 2XXX XXXX
- **Horario de atención**: Lunes a Viernes, 9:00 - 18:00

### Recursos Adicionales

- 📹 **Videos tutoriales**: [youtube.com/tomako](https://youtube.com/tomako)
- 📚 **Base de conocimientos**: [ayuda.tomako.cl](https://ayuda.tomako.cl)
- 💬 **Comunidad de usuarios**: [comunidad.tomako.cl](https://comunidad.tomako.cl)

### Reportar un Problema

Si encuentras un error o tienes una sugerencia:

1. Describe el problema claramente
2. Indica los pasos para reproducirlo
3. Adjunta capturas de pantalla si es relevante
4. Especifica navegador y versión
5. Envía a soporte@tomako.cl

---

## 📝 Notas de Versión

### Versión 1.0 (Mayo 2025)

**Nuevas funcionalidades**:
- ✅ Agenda de seguimiento post-servicio con encuestas NPS
- ✅ Tablero Kanban con drag & drop para órdenes de servicio
- ✅ Sistema de archivos adjuntos (fotos y documentos)
- ✅ Checklists personalizables por tipo de servicio
- ✅ Análisis de rentabilidad por servicio con gráficos
- ✅ Módulo completo de proveedores y órdenes de compra
- ✅ Segmentación de clientes con etiquetas
- ✅ Evaluación de proveedores con ranking

**Mejoras UX**:
- Menú lateral reorganizado con submenús
- Dashboard con KPIs en tiempo real
- Alertas visuales de stock bajo y tiempos excedidos
- Compresión automática de imágenes
- Validación de patente y RUT chilenos

---

> **© 2025 TOMAKO - Tu auto necesita TOMAKO** 🍅  
> *Taller Operativo de Mecánica Avanzada y Kinemática Optimizada*
