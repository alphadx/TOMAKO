# 🍅 TOMAKO — Sistema de Gestión de Taller Mecánico

> **Tu auto necesita TOMAKO** 🍅  
> *Taller Operativo de Mecánica Avanzada y Kinemática Optimizada*

[![Yii2 Framework](https://img.shields.io/badge/Yii-2.0-blue.svg)](https://www.yiiframework.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📖 ¿Qué es TOMAKO?

**TOMAKO** es un sistema integral de gestión para talleres mecánicos automotrices desarrollado en PHP con el framework Yii2. Centraliza todas las operaciones del taller en una plataforma moderna: desde la recepción del cliente hasta la entrega del vehículo, pasando por diagnóstico, órdenes de servicio, gestión de inventario y seguimiento técnico.

### 🎯 Propósito

Digitalizar y optimizar el flujo de trabajo de talleres mecánicos, proporcionando:
- **Trazabilidad completa** de cada intervención
- **Control de inventario** en tiempo real
- **Agendamiento inteligente** de citas
- **Dashboard con KPIs** para toma de decisiones
- **Auditoría detallada** de todas las operaciones

---

## 🧬 CONCEPTO DE MARCA

### Esencia
**TOMAKO** es la fusión entre **energía orgánica** y **precisión industrial**. Representa un taller mecánico con carácter: cercano pero experto, vibrante pero confiable.

### Personalidad
| Rasgo | Manifestación visual |
|:---|:---|
| 🔥 **Energético** | Rojo vibrante, formas dinámicas, composición con movimiento |
| ⚙️ **Preciso** | Líneas limpias, geometría controlada, tipografía técnica |
| 🍅 **Cercano** | Metáfora orgánica, tono humano, guiño pop sutil |
| 🛠️ **Experto** | Paleta industrial, aplicaciones funcionales, jerarquía clara |

### Público objetivo
- Conductores que buscan **confianza + carácter** en su taller.
- Fans de la cultura pop que aprecian referencias inteligentes (sin necesidad de explicarlas).
- Clientes que valoran **servicio cercano con resultados profesionales**.

---

## 🎨 SISTEMA DE LOGOTIPO

### Logo Principal (Horizontal)
```
[ISOTIPO]  TOMAKO  |  Tu auto necesita TOMAKO 🍅
```
- **Isotipo:** Silueta geométrica que fusiona:
  - La redondez del **tomate** (base circular)
  - La forma alargada de la **hoja de tabaco** (elemento superior curvado)
  - Integrado sutilmente en un **engranaje de 6 dientes** o perfil de llave inglesa
- **Logotipo:** `TOMAKO` en tipografía Sans-Serif geométrica, peso SemiBold
- **Slogan:** En línea inferior o derecha, tipografía Regular, tamaño 60% del nombre

### Área de protección
```
     ←── 1x ──→
   ┌─────────────┐
1x │   [LOGO]    │ 1x
   └─────────────┘
     ←── 1x ──→
```
*1x = altura de la letra "T" en TOMAKO. Espacio libre obligatorio.*

---

## 🎨 PALETA CROMÁTICA

### Colores Primarios
| Nombre | HEX | RGB | Uso |
|:---|:---|:---|:---|
| **Rojo TOMAKO** | `#E63946` | 230,57,70 | Isotipo, acentos, CTA, slogan emoji 🍅 |
| **Marrón TABACO** | `#7A5230` | 122,82,48 | Tipografía principal, fondos técnicos |
| **Gris MECÁNICO** | `#4A4E54` | 74,78,84 | Líneas, iconos, estructuras |

### Colores Secundarios
| Nombre | HEX | Uso |
|:---|:---|:---|
| **Blanco NEUTRO** | `#F8F9FA` | Fondos, respiración, texto sobre oscuro |
| **Cobre RESPLANDOR** | `#D4A574` | *Solo Easter Egg*: bordes sutiles, animaciones digitales |
| **Verde HOJA** | `#52796F` | Detalles orgánicos secundarios |

---

## 🔤 SISTEMA TIPOGRÁFICO

### Familias oficiales (Google Fonts - libres)
| Rol | Familia | Peso | Estilo |
|:---|:---|:---|:---|
| **Logotipo TOMAKO** | `Montserrat` | SemiBold (600) | Mayúsculas, tracking +25 |
| **Slogan** | `Montserrat` | Regular (400) | Oración, tamaño 60% del logo |
| **Títulos** | `Montserrat` | Medium (500) | Mayúsculas o Título |
| **Cuerpo técnico** | `Inter` | Regular (400) / Medium (500) | Legibilidad máxima |
| **Acentos pop** | `Space Grotesk` | Medium (500) | *Opcional*: redes, merch, campañas |

---

## ✅ DO's & 🚫 DON'Ts

| ✅ HACER | 🚫 EVITAR |
|:---|:---|
| Usar el slogan completo: *"Tu auto necesita TOMAKO 🍅"* | Abreviar, modificar o traducir el slogan |
| Mantener el isotipo con proporciones fijas | Estirar, deformar o redibujar el isotipo |
| Usar el "resplandor cobre" solo en digital/merch especial | Usar Cobre en documentos formales |
| Jugar con metáforas: "energía", "fusión", "carácter" | Usar palabras: "adicción", "nuclear", "mutante" |

---

## 🛠️ FUNCIONALIDADES DEL SOFTWARE

### 👥 Gestión de Clientes
- Registro completo con validación de **RUT chileno** (algoritmo módulo 11)
- Normalización automática de nombres y emails
- Validación de formato telefónico internacional (+XX X XXXX XXXX)
- Historial de vehículos y servicios asociados
- Estados activo/inactivo
- Relaciones: Vehículos, Órdenes, Citas, Pagos

### 🚗 Gestión de Vehículos
- Validación de **patente chilena**:
  - Formato antiguo: AB-1234 (2 letras + 4 dígitos)
  - Formato nuevo: ABCD-12 (4 letras + 2 dígitos)
- Soporte para **VIN** (17 caracteres, validación ISO)
- Relación uno-a-muchos con clientes
- Registro de kilometraje y fotos
- Detección automática de tabla (vehiculo/vehiculos)
- Métodos: `getUltimaCita()`, `getProximaCita()`

### 📅 Sistema de Citas
- Agendamiento por fecha y hora con validación de solapamientos
- **Estados**: pendiente → confirmada → en_progreso → completada / cancelada / no_show
- Asociación múltiple de servicios por cita
- Validación: hora_fin > hora_inicio
- Validación: vehículo pertenece al cliente
- Integración con calendario visual
- Reprogramación flexible
- Confirmación automática por email
- Transiciones controladas por `puedeTransicionarA()`

### 🔧 Órdenes de Servicio
- Generación automática de códigos (JOB-NNN)
- **Flujo de estados**: abierto → en_progreso → esperando_repuestos → listo_para_entrega → entregada
- **Prioridades**: baja, normal, alta, urgente
- Detalles múltiples con servicios y repuestos
- Asignación de técnicos por orden
- Notas internas y registro de cambios de estado (OrdenEstadoLog)
- Cálculo automático de totales desde detalles
- Tiempo estimado de intervención (`getDuracionTotalMinutos()`, `getDuracionTotalLabel()`)
- Badges dinámicos por estado y prioridad
- Transiciones validadas por `puedeTransicionar()`

### 🛠️ Catálogo de Servicios
- Codificación personalizada (S-NNNN)
- Precios base con historial de cambios (HistorialPrecio)
- Duración estimada en minutos
- Categorización por tipo de intervención
- Control de estado (activo/inactivo)
- Relación muchos-a-muchos con citas

### 📦 Gestión de Inventario
- Control de stock con SKU automático (INS-NNNN)
- **Alertas de stock bajo** configurables por ítem
- Movimientos de entrada/salida registrados (InventoryMovement)
- Múltiples unidades: unidad, litro, kg, metro
- Ubicación física en bodega
- Valorización total del inventario
- Estados de stock: `sin_stock` | `bajo` | `en_stock`
- Validación: categoría debe ser tipo 'insumo' o 'ambos'
- Stock mínimo/máximo configurable

### 👨‍🔧 Gestión de Técnicos
- Registro con validación de RUT
- Especialidades asignadas (Especialidad)
- Certificaciones registradas con fechas (Certificacion)
- Costo por hora para cálculo de mano de obra
- Asignación dinámica a órdenes de servicio (AsignacionOrden)
- Historial de intervenciones

### 💳 Módulo de Pagos
- Múltiples métodos: efectivo, tarjeta débito/crédito, transferencia (MetodoPago)
- **Estados**: pendiente, completado, pagado, anulado
- Vinculación con órdenes de servicio
- Registro de comprobantes y referencias
- Anulación con causal documentada
- Auditoría de transacciones

### 📊 Dashboard Ejecutivo
KPIs en tiempo real con actualización automática (AJAX):

| KPI | Descripción |
|:---|:---|
| 🔧 Servicios Activos | Órdenes en curso (abierto + en_progreso + esperando_repuestos) |
| 📅 Citas Hoy | Agenda del día filtrada por estado |
| ⚠️ Stock Bajo | Ítems bajo mínimo crítico |
| 💳 Ingresos Mes | Suma de pagos completados del mes |
| ✅ Trabajos Listos | Órdenes listas para entrega |
| 👥 Clientes Nuevos | Altas del mes actual |
| 📦 Valor Inventario | Stock × precio unitario |

Endpoint: `GET /dashboard/refresh-kpi?kpi={nombre}`

### 🔐 Sistema de Seguridad
- Autenticación de usuarios con bloqueo por intentos fallidos (LoginAttempt)
- Roles configurables (Rol) con permisos granulares (Permiso, RolPermiso)
- Auditoría completa de logs (AuditLog)
- Parámetros del sistema centralizados (ParametroSistema)
- Preferencias de notificación por usuario (PreferenciaNotificacion)
- Preferencias de dashboard personalizables por usuario (DashboardPreference)

### 🔔 Notificaciones
- Plantillas personalizables por evento (PlantillaNotificacion)
- Múltiples canales: email, push, interna
- Registro de envíos (EmailLog, Notificacion)
- Preferencias por usuario
- Eventos: cita_confirmada, orden_listo, stock_bajo, recordatorio_cita, etc.
- Recordatorios automáticos de citas (RecordatorioCitaController)

### 📝 Características Adicionales
- **Checklist de inspección**: Items configurables por orden (ChecklistItem)
- **Plantillas de checklist**: Plantillas reutilizables (PlantillaChecklist, PlantillaChecklistItem)
- **Notas de orden**: Bitácora interna (OrdenNota)
- **Cierre de caja**: Control diario (CierreCaja)
- **Logs de archivo**: Tracking de operaciones masivas (ArchiveLog)
- **Búsquedas avanzadas**: Models Search para todos los módulos principales
- **Seguimiento**: Historial de actividades y seguimiento de órdenes (Seguimiento)
- **Archivos adjuntos**: Gestión de archivos en órdenes de servicio (OrdenServicioArchivo)
- **Calculadora de precios**: Herramienta de validación y cotización (CalculadoraController)
- **Etiquetas de cliente**: Sistema de etiquetado personalizado (Etiqueta, ClienteEtiqueta)

### 🛒 Módulo de Compras y Proveedores
- **Proveedores**: Gestión completa de proveedores (Proveedor)
- **Productos de proveedor**: Catálogo de productos por proveedor (ProveedorProducto)
- **Órdenes de compra**: Generación y seguimiento de compras (OrdenCompra, OrdenCompraItem)
- **Evaluación de proveedores**: Sistema de evaluación de desempeño (EvaluacionProveedor)
- **Permisos específicos**: Control de acceso para módulo de proveedores

### 🚗 Gestión de Marcas y Modelos
- **Marcas**: Catálogo de marcas de vehículos (Marca)
- **Modelos**: Modelos por marca con API REST (ApiMarcaController, ApiModeloController)
- **Integración con vehículos**: Selección dinámica en formularios

### 📈 Productividad y Reportes
- **Productividad de técnicos**: Reportes de rendimiento (ProductividadTecnicoController)
- **Reporte de inventario**: Valorización y movimientos (InventarioReportController)
- **Reporte de checklist**: Inspecciones técnicas (OrdenServicioController/reporte-checklist)
- **Asignación de turnos**: Dashboard de asignación de técnicos (AsignacionTurnoController)

---

## 🏗️ Arquitectura Técnica

### Stack Tecnológico
- **Backend**: PHP 8.1+ con Yii2 Framework
- **Base de datos**: MySQL/MariaDB
- **Frontend**: Bootstrap 5, JavaScript vanilla
- **Cache**: Redis/File cache para KPIs
- **Email**: SwiftMailer / Symfony Mailer

### Estructura de Directorios
```
/workspace
├── config/              # Configuración de la aplicación
├── controllers/         # 32 controladores especializados
├── models/              # 55+ modelos ActiveRecord + 17 Search models
├── views/               # Vistas organizadas por controller
├── components/
│   ├── behaviors/       # AuditBehavior para tracking automático
│   ├── services/        # Lógica de negocio encapsulada (21 servicios)
│   ├── helpers/         # Utilidades: DateHelper, FormatHelper, ImageProcessor, etc.
│   └── widgets/         # Componentes UI reutilizables (7 widgets)
├── migrations/          # 41 migraciones de base de datos
├── documentacion/       # Documentación técnica por módulos
├── tests/               # Tests unitarios, funcionales, acceptance
└── web/                 # Assets públicos (CSS, JS, imágenes)
```

### Modelos Principales (55+)
| Modelo | Responsabilidad |
|:---|:---|
| `Cliente` | Gestión de clientes con validación RUT |
| `ClienteEtiqueta` | Etiquetas personalizables para clientes |
| `Vehiculo` | Flota de clientes con validación patente/VIN |
| `Marca` | Marcas de vehículos |
| `Modelo` | Modelos de vehículos por marca |
| `Cita` | Agenda con validación de solapamientos |
| `CitaServicio` | Relación N:M citas-servicios |
| `OrdenServicio` | Órdenes de servicio con flujo de estados |
| `OrdenServicioDetalle` | Ítems de orden (servicios/repuestos) |
| `OrdenServicioArchivo` | Archivos adjuntos a órdenes |
| `OrdenServicioRepuesto` | Repuestos utilizados en órdenes |
| `OrdenEstadoLog` | Historial de cambios de estado |
| `OrdenNota` | Bitácora interna de órdenes |
| `AsignacionOrden` | Técnicos asignados a órdenes |
| `Servicio` | Catálogo de servicios |
| `ServicioRentabilidad` | Análisis de rentabilidad por servicio |
| `Categoria` | Categorización (servicios/insumos) |
| `InventoryItem` | Items de inventario con SKU |
| `InventoryMovement` | Movimientos de stock |
| `Tecnico` | Registro de técnicos |
| `Especialidad` | Especialidades técnicas |
| `Certificacion` | Certificaciones de técnicos |
| `Pago` | Transacciones de pago |
| `MetodoPago` | Métodos de pago configurables |
| `User` | Usuarios del sistema |
| `Rol` | Roles de seguridad |
| `Permiso` | Permisos granulares |
| `RolPermiso` | Asignación rol-permiso |
| `Notificacion` | Notificaciones del sistema |
| `PlantillaNotificacion` | Templates de notificación |
| `PreferenciaNotificacion` | Preferencias por usuario |
| `EmailLog` | Logs de envío de emails |
| `AuditLog` | Auditoría de operaciones |
| `LoginAttempt` | Intentos de login fallidos |
| `ChecklistItem` | Checklists de inspección |
| `PlantillaChecklist` | Plantillas reutilizables de checklist |
| `PlantillaChecklistItem` | Items de plantilla de checklist |
| `HistorialPrecio` | Historial de cambios de precio |
| `ParametroSistema` | Configuración global |
| `CierreCaja` | Control de cierre diario |
| `ArchiveLog` | Logs de archivado |
| `CotizacionJwt` | Tokens JWT para cotizaciones |
| `DashboardPreference` | Preferencias de dashboard por usuario |
| `Seguimiento` | Seguimiento de órdenes y actividades |
| `Proveedor` | Gestión de proveedores |
| `ProveedorProducto` | Productos de proveedores |
| `OrdenCompra` | Órdenes de compra a proveedores |
| `OrdenCompraItem` | Ítems de orden de compra |
| `EvaluacionProveedor` | Evaluaciones de desempeño de proveedores |
| `Etiqueta` | Etiquetas personalizables del sistema |
| `CalculadoraForm` | Formulario de calculadora de precios |
| `ChecklistReportForm` | Formulario de reporte de checklist |

### Servicios de Negocio
| Servicio | Responsabilidad |
|:---|:---|
| `AuthService` | Autenticación y autorización |
| `CitaService` | CRUD de citas + transiciones de estado |
| `OrdenService` | Creación de órdenes desde citas o directo |
| `OrdenServicioService` | Gestión completa de órdenes |
| `ClienteService` | Alta/modificación de clientes |
| `VehiculoService` | Gestión de flota de clientes |
| `InventarioService` | Movimientos de stock y alertas |
| `PagoService` | Procesamiento de pagos |
| `NotificacionService` | Envío de notificaciones multi-canal |
| `DashboardService` | Agregación de KPIs con cache |
| `CategoriaService` | Gestión de categorías |
| `CierreCajaService` | Control de cierre de caja |
| `RentabilidadService` | Análisis de rentabilidad |
| `RolService` | Gestión de roles y permisos |
| `ServicioService` | Catálogo de servicios |
| `TecnicoService` | Gestión de técnicos |
| `UserService` | Administración de usuarios |
| `AuditLogService` | Servicio de logs de auditoría |
| `DatabaseInitService` | Inicialización de base de datos |
| `BaseService` | Clase base para servicios |

### Comportamientos (Behaviors)
- **AuditBehavior**: Registra automáticamente creador, actualizador, timestamps y logs de auditoría en todos los modelos principales
- **AccessControlBehavior**: Control de acceso personalizado
- **SoftDeleteBehavior**: Eliminación lógica de registros

### Helpers/Utilidades
| Helper | Responsabilidad |
|:---|:---|
| `DateHelper` | Manipulación y formato de fechas |
| `FormatHelper` | Formateo de valores (moneda, RUT, etc.) |
| `StringHelper` | Utilidades de texto |
| `ImageProcessor` | Procesamiento de imágenes |
| `UploadHelper` | Gestión de archivos subidos |
| `SearchHelper` | Utilidades de búsqueda |

### Widgets
| Widget | Responsabilidad |
|:---|:---|
| `KpiCard` | Tarjetas de KPI para dashboard |
| `StatusBadge` | Badges de estado dinámicos |
| `FlashMessages` | Mensajes flash personalizados |
| `AlertasStockWidget` | Widget de alertas de stock |
| `CitasHoyWidget` | Widget de citas del día |
| `OrdenesActivasWidget` | Widget de órdenes activas |
| `AccesosRapidosWidget` | Accesos rápidos del sistema |

### Controladores (32)
`AdminController`, `ApiMarcaController`, `ApiModeloController`, `AsignacionTurnoController`, `AuditLogController`, `BaseController`, `CalculadoraController`, `CategoriaController`, `CitaController`, `ClienteController`, `DashboardController`, `EspecialidadController`, `EtiquetaController`, `EvaluacionProveedorController`, `InventarioController`, `InventarioReportController`, `ManualController`, `NotificacionController`, `OrdenCompraController`, `OrdenController`, `OrdenServicioController`, `PagoController`, `ProductividadTecnicoController`, `ProveedorController`, `RecordatorioCitaController`, `RolController`, `SeguimientoController`, `ServicioController`, `SiteController`, `TecnicoController`, `UsuarioController`, `VehiculoController`

---

## 🔄 Flujo de Trabajo Típico

```
Cliente solicita servicio
         ↓
    Agendar Cita (valida solapamiento)
         ↓
   ¿Se presenta?
    ↓           ↓
   Sí          No
   ↓           ↓
Confirmar   Marcar No-Show
   ↓
Crear Orden de Servicio (JOB-NNN)
   ↓
Diagnóstico + Cotización
   ↓
Aprobación Cliente
   ↓
Asignar Técnico (AsignacionOrden)
   ↓
Ejecutar Servicio + Checklist
   ↓
¿Repuestos OK?
    ↓           ↓
   Sí         No → Esperando Repuestos
   ↓           ↓
Completar ←─────┘
   ↓
Control de Calidad
   ↓
Orden Lista para Entrega
   ↓
Generar Pago (método configurable)
   ↓
Entregar Vehículo + Cerrar Orden
   ↓
Registro en AuditLog + EmailLog
```

---

## 📋 Requisitos del Sistema

- PHP >= 8.1
- MySQL >= 5.7 o MariaDB >= 10.2
- Extensiones PHP: pdo, pdo_mysql, intl, gd, json, mbstring
- Composer instalado globalmente
- Mínimo 512MB RAM (recomendado 1GB+)
- Navegador moderno con JavaScript habilitado

---

## ⚙️ Instalación

### 1. Clonar repositorio
```bash
git clone <repository-url> tomako
cd tomako
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar base de datos
Editar `config/db.php`:
```php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=tomako_db',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

### 4. Ejecutar migraciones
```bash
./yii migrate --interactive=0
```

### 5. Configurar permisos de escritura
```bash
chmod 777 runtime/
chmod 777 web/assets/
```

### 6. Generar datos demo (opcional)
Para propósitos de prueba y demostración, puedes generar datos históricos:
```bash
# Generar 31 días de datos demo starting desde una fecha específica
php yii demo-data/sembrar-mes 2026-05-26 31 1
```
**Parámetros:**
- Fecha inicial: formato YYYY-MM-DD (ej: `2026-05-26`)
- Días: mínimo 30 días de historia
- Limpiar: `1` para borrar datos demo anteriores, `0` para conservar

Este comando genera clientes, vehículos, citas, órdenes de servicio, pagos e inventario de ejemplo.

### 7. Acceder al sistema
Abrir navegador en `http://localhost/tomako/web`

**Usuario admin por defecto**: consultar documentación de seeders o crear manualmente.

---

## 🧪 Testing

El proyecto incluye suite de tests:

```bash
# Tests unitarios
./vendor/bin/phpunit tests/unit/

# Tests funcionales
./vendor/bin/phpunit tests/functional/

# Tests de aceptación (requiere Codeception + navegador)
./vendor/bin/codecept run acceptance
```

---

## 🔒 Consideraciones de Seguridad

- Todos los formularios incluyen validación CSRF
- Los passwords se almacenan con hash bcrypt
- Las sesiones expiran después de inactividad
- Los logs de auditoría registran IP y usuario
- Los roles limitan acceso a funcionalidades críticas
- Validación de RUT y patente previene datos inválidos
- Bloqueo automático por intentos fallidos de login

---

## 📄 Licencia

MIT License. Ver archivo [LICENSE](LICENSE) para detalles.

---

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el repositorio
2. Crear rama feature (`git checkout -b feature/NuevaFuncionalidad`)
3. Commit cambios (`git commit -m 'Añadir nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/NuevaFuncionalidad`)
5. Abrir Pull Request

---

## 📞 Soporte

Para incidencias técnicas o consultas comerciales:

- **Issues**: Reportar bugs en GitHub Issues
- **Documentación**: Ver `/docs` para guías detalladas

---

> **Hecho con ❤️ para talleres que exigen precisión**  
> *Tu auto necesita TOMAKO* 🍅🔧

---

## 🕵️ EL GUIÑO SUTIL (Easter Egg)

Para quienes tengan la intuición, TOMAKO incluye referencias sutiles:

1. **Resplandor Cobre**: Degradé casi imperceptible en elementos digitales especiales
2. **Metáfora visual**: Tomate (energía fresca) + Tabaco (carácter técnico) = Fusión perfecta
3. **Verbos clave**: "conectar", "fascinación", "energía que perdura"

> ⚠️ **Regla de oro**: El guiño funciona solo si el espectador *ya tiene la referencia*. Nunca lo explicamos oficialmente.
