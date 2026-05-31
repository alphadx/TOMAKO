<?php

declare(strict_types=1);

namespace app\components\services;

use app\models\Cita;
use app\models\Cliente;
use app\models\InventoryItem;
use app\models\InventoryMovement;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use app\models\Pago;
use app\models\Permiso;
use app\models\Servicio;
use app\models\Tecnico;
use app\models\AsignacionOrden;
use app\models\Vehiculo;
use Yii;
use yii\db\Expression;

/**
 * Servicio de agregación de datos para el Dashboard principal.
 */
class DashboardService extends BaseService
{
    protected string $logCategoria = 'app.dashboard';

    public function getKpis(): array
    {
        return [
            'servicios_activos' => (int) $this->getKpiConCache('servicios_activos', fn(): int => $this->getServiciosActivos()),
            'citas_hoy' => (int) $this->getKpiConCache('citas_hoy', fn(): int => $this->getCitasHoyCount()),
            'stock_bajo' => (int) $this->getKpiConCache('stock_bajo', fn(): int => $this->getStockBajoCount()),
            'ingresos_mes' => (float) $this->getKpiConCache('ingresos_mes', fn(): float => $this->getIngresosMes()),
            'trabajos_listos' => (int) $this->getKpiConCache('trabajos_listos', fn(): int => $this->getTrabajosListos()),
            'clientes_nuevos' => (int) $this->getKpiConCache('clientes_nuevos', fn(): int => $this->getClientesNuevosMes()),
            'valor_inventario' => (float) $this->getKpiConCache('valor_inventario', fn(): float => $this->getValorInventario()),
            // Primeros 5 indicadores implementados
            'tasa_entrega_tiempo' => (float) $this->getKpiConCache('tasa_entrega_tiempo', fn(): float => $this->getTasaEntregaTiempo()),
            'tiempo_promedio_resolucion' => (float) $this->getKpiConCache('tiempo_promedio_resolucion', fn(): float => $this->getTiempoPromedioResolucion()),
            'tasa_cancelacion' => (float) $this->getKpiConCache('tasa_cancelacion', fn(): float => $this->getTasaCancelacion()),
            'rotacion_inventario' => (float) $this->getKpiConCache('rotacion_inventario', fn(): float => $this->getRotacionInventario()),
            'tasa_no_show' => (float) $this->getKpiConCache('tasa_no_show', fn(): float => $this->getTasaNoShow()),
            // Nuevos 5 indicadores (6-10)
            'ingreso_promedio_orden' => (float) $this->getKpiConCache('ingreso_promedio_orden', fn(): float => $this->getIngresoPromedioOrden()),
            'distribucion_prioridad' => (array) $this->getKpiConCache('distribucion_prioridad', fn(): array => $this->getDistribucionPrioridad()),
            'tasa_retencion_clientes' => (float) $this->getKpiConCache('tasa_retencion_clientes', fn(): float => $this->getTasaRetencionClientes()),
            'productividad_tecnico' => (float) $this->getKpiConCache('productividad_tecnico', fn(): float => $this->getProductividadTecnico()),
            'tasa_quiebre_stock' => (float) $this->getKpiConCache('tasa_quiebre_stock', fn(): float => $this->getTasaQuiebreStock()),
            // Terceros 5 indicadores (11-15)
            'frecuencia_servicio_vehiculo' => (float) $this->getKpiConCache('frecuencia_servicio_vehiculo', fn(): float => $this->getFrecuenciaServicioVehiculo()),
            'margen_bruto_servicio' => (float) $this->getKpiConCache('margen_bruto_servicio', fn(): float => $this->getMargenBrutoServicio()),
            'ingresos_por_metodo_pago' => (array) $this->getKpiConCache('ingresos_por_metodo_pago', fn(): array => $this->getIngresosPorMetodoPago()),
            'tasa_morosidad' => (float) $this->getKpiConCache('tasa_morosidad', fn(): float => $this->getTasaMorosidad()),
            'ocupacion_agenda' => (float) $this->getKpiConCache('ocupacion_agenda', fn(): float => $this->getOcupacionAgenda()),
        ];
    }

    /**
     * @return array<int, Cita>
     */
    public function getCitasHoy(): array
    {
        return (array) $this->getKpiConCache('lista_citas_hoy', function (): array {
            return Cita::find()
                ->with(['cliente', 'vehiculo'])
                ->where(['fecha' => date('Y-m-d')])
                ->andWhere(['!=', 'estado', 'cancelada'])
                ->orderBy(['hora_inicio' => SORT_ASC])
                ->limit(8)
                ->all();
        });
    }

    /**
     * @return array<int, InventoryItem>
     */
    public function getAlertasStock(): array
    {
        return (array) $this->getKpiConCache('alertas_stock', function (): array {
            return InventoryItem::find()
                ->where(['status' => 1])
                ->andWhere(new Expression('cantidad <= stock_minimo'))
                ->orderBy(['cantidad' => SORT_ASC, 'nombre' => SORT_ASC])
                ->limit(8)
                ->all();
        });
    }

    /**
     * @return array<string,int>
     */
    public function getOrdenesActivas(): array
    {
        return (array) $this->getKpiConCache('ordenes_activas_estado', function (): array {
            $rows = OrdenServicio::find()
                ->select(['estado', 'total' => 'COUNT(*)'])
                ->where(['in', 'estado', [
                    'abierto',
                    'en_progreso',
                    'esperando_repuestos',
                    'listo_para_entrega',
                ]])
                ->groupBy('estado')
                ->asArray()
                ->all();

            $base = [
                'abierto' => 0,
                'en_progreso' => 0,
                'esperando_repuestos' => 0,
                'listo_para_entrega' => 0,
            ];

            foreach ($rows as $row) {
                $estado = (string) ($row['estado'] ?? '');
                if (array_key_exists($estado, $base)) {
                    $base[$estado] = (int) ($row['total'] ?? 0);
                }
            }

            return $base;
        });
    }

    /**
     * @return array<int, array{icon:string,title:string,description:string,url:array}>
     */
    public function getAccesosRapidos(): array
    {
        $items = [
            ['modulo' => 'cita', 'icon' => '📅', 'title' => 'Nueva Cita', 'description' => 'Agenda una nueva atención.', 'url' => ['/cita/create']],
            ['modulo' => 'orden', 'icon' => '🔧', 'title' => 'Nueva Orden', 'description' => 'Abre una orden de servicio.', 'url' => ['/orden/create']],
            ['modulo' => 'cliente', 'icon' => '👥', 'title' => 'Nuevo Cliente', 'description' => 'Registra un nuevo cliente.', 'url' => ['/cliente/create']],
            ['modulo' => 'inventario', 'icon' => '📦', 'title' => 'Inventario', 'description' => 'Consulta existencias y alertas.', 'url' => ['/inventario/index']],
        ];

        if (Yii::$app->user->isGuest) {
            return [];
        }

        $identity = Yii::$app->user->identity;
        if ($identity !== null && (int) $identity->rol_id === 1) {
            return array_map(static fn(array $item): array => [
                'icon' => $item['icon'],
                'title' => $item['title'],
                'description' => $item['description'],
                'url' => $item['url'],
            ], $items);
        }

        $permisos = $this->getPermisosUsuarioActual();
        if ($permisos === []) {
            return array_map(static fn(array $item): array => [
                'icon' => $item['icon'],
                'title' => $item['title'],
                'description' => $item['description'],
                'url' => $item['url'],
            ], $items);
        }

        $filtrados = [];
        foreach ($items as $item) {
            $modulo = (string) $item['modulo'];
            if (!in_array($modulo . '.crear', $permisos, true) && !in_array($modulo . '.create', $permisos, true) && !in_array($modulo . '.ver', $permisos, true) && !in_array($modulo . '.view', $permisos, true)) {
                continue;
            }

            $filtrados[] = [
                'icon' => $item['icon'],
                'title' => $item['title'],
                'description' => $item['description'],
                'url' => $item['url'],
            ];
        }

        return $filtrados;
    }

    public function getKpiConCache(string $nombre, callable $calculator, int $ttl = 0): mixed
    {
        $cache = Yii::$app->cache;
        $ttlFinal = $ttl > 0 ? $ttl : (int) (Yii::$app->params['dashboard.kpi_ttl'] ?? 60);
        $key = $this->buildKpiCacheKey($nombre);

        $value = $cache->get($key);
        if ($value !== false) {
            return $value;
        }

        try {
            $value = $calculator();
            $cache->set($key, $value, $ttlFinal);
            return $value;
        } catch (\Throwable $e) {
            Yii::warning('Error calculando KPI ' . $nombre . ': ' . $e->getMessage(), $this->logCategoria);
            return $this->defaultValueForKpi($nombre);
        }
    }

    public function invalidateKpiCache(): void
    {
        $kpis = [
            'servicios_activos',
            'citas_hoy',
            'stock_bajo',
            'ingresos_mes',
            'trabajos_listos',
            'clientes_nuevos',
            'valor_inventario',
            'lista_citas_hoy',
            'alertas_stock',
            'ordenes_activas_estado',
            // Primeros 5 indicadores
            'tasa_entrega_tiempo',
            'tiempo_promedio_resolucion',
            'tasa_cancelacion',
            'rotacion_inventario',
            'tasa_no_show',
            // Nuevos 5 indicadores (6-10)
            'ingreso_promedio_orden',
            'distribucion_prioridad',
            'tasa_retencion_clientes',
            'productividad_tecnico',
            'tasa_quiebre_stock',
            // Terceros 5 indicadores (11-15)
            'frecuencia_servicio_vehiculo',
            'margen_bruto_servicio',
            'ingresos_por_metodo_pago',
            'tasa_morosidad',
            'ocupacion_agenda',
        ];

        foreach ($kpis as $kpi) {
            Yii::$app->cache->delete($this->buildKpiCacheKey($kpi));
        }
    }

    public function refreshKpi(string $kpi): mixed
    {
        $this->invalidateKpiByName($kpi);

        return match ($kpi) {
            'servicios_activos' => $this->getServiciosActivos(),
            'citas_hoy' => $this->getCitasHoyCount(),
            'stock_bajo' => $this->getStockBajoCount(),
            'ingresos_mes' => $this->getIngresosMes(),
            'trabajos_listos' => $this->getTrabajosListos(),
            'clientes_nuevos' => $this->getClientesNuevosMes(),
            'valor_inventario' => $this->getValorInventario(),
            // Primeros 5 indicadores
            'tasa_entrega_tiempo' => $this->getTasaEntregaTiempo(),
            'tiempo_promedio_resolucion' => $this->getTiempoPromedioResolucion(),
            'tasa_cancelacion' => $this->getTasaCancelacion(),
            'rotacion_inventario' => $this->getRotacionInventario(),
            'tasa_no_show' => $this->getTasaNoShow(),
            // Nuevos 5 indicadores (6-10)
            'ingreso_promedio_orden' => $this->getIngresoPromedioOrden(),
            'distribucion_prioridad' => $this->getDistribucionPrioridad(),
            'tasa_retencion_clientes' => $this->getTasaRetencionClientes(),
            'productividad_tecnico' => $this->getProductividadTecnico(),
            'tasa_quiebre_stock' => $this->getTasaQuiebreStock(),
            // Terceros 5 indicadores (11-15)
            'frecuencia_servicio_vehiculo' => $this->getFrecuenciaServicioVehiculo(),
            'margen_bruto_servicio' => $this->getMargenBrutoServicio(),
            'ingresos_por_metodo_pago' => $this->getIngresosPorMetodoPago(),
            'tasa_morosidad' => $this->getTasaMorosidad(),
            'ocupacion_agenda' => $this->getOcupacionAgenda(),
            default => null,
        };
    }

    private function getServiciosActivos(): int
    {
        return (int) OrdenServicio::find()
            ->where(['in', 'estado', ['abierto', 'en_progreso', 'esperando_repuestos']])
            ->count('*');
    }

    private function getCitasHoyCount(): int
    {
        return (int) Cita::find()
            ->where(['fecha' => date('Y-m-d')])
            ->andWhere(['!=', 'estado', 'cancelada'])
            ->count('*');
    }

    private function getStockBajoCount(): int
    {
        return (int) InventoryItem::find()
            ->where(['status' => 1])
            ->andWhere(new Expression('cantidad <= stock_minimo'))
            ->count('*');
    }

    private function getIngresosMes(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();

        $total = Pago::find()
            ->where(['in', 'estado', ['completado', 'pagado']])
            ->andWhere(['between', 'created_at', $inicio, $fin])
            ->sum('monto');

        return (float) ($total ?? 0.0);
    }

    private function getTrabajosListos(): int
    {
        return (int) OrdenServicio::find()
            ->where(['estado' => 'listo_para_entrega'])
            ->count('*');
    }

    private function getClientesNuevosMes(): int
    {
        [$inicio, $fin] = $this->currentMonthRange();

        return (int) Cliente::find()
            ->where(['between', 'created_at', $inicio, $fin])
            ->count('*');
    }

    private function getValorInventario(): float
    {
        $value = InventoryItem::find()
            ->where(['status' => 1])
            ->sum(new Expression('precio_unitario * cantidad'));

        return (float) ($value ?? 0.0);
    }

    /**
     * Indicador 1: Tasa de eficiencia en tiempo de entrega
     * Porcentaje de órdenes entregadas a tiempo vs fuera de plazo
     */
    private function getTasaEntregaTiempo(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $totalEntregadas = OrdenServicio::find()
            ->where(['estado' => 'entregada'])
            ->andWhere(['between', 'closed_at', $inicio, $fin])
            ->count('*');
        
        if ($totalEntregadas === 0) {
            return 0.0;
        }
        
        // Contamos las que fueron entregadas dentro del tiempo estimado
        // Asumimos que si closed_at - created_at <= 3 días es "a tiempo"
        $tiempoEstimadoDias = 3;
        $segundosEstimados = $tiempoEstimadoDias * 24 * 60 * 60;
        
        $aTiempo = OrdenServicio::find()
            ->where(['estado' => 'entregada'])
            ->andWhere(['between', 'closed_at', $inicio, $fin])
            ->andWhere(['<=', new Expression('closed_at - created_at'), $segundosEstimados])
            ->count('*');
        
        return (float) (($aTiempo / $totalEntregadas) * 100);
    }

    /**
     * Indicador 2: Tiempo promedio de resolución por tipo de servicio
     * Promedio de días entre apertura y cierre de órdenes
     */
    private function getTiempoPromedioResolucion(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $query = OrdenServicio::find()
            ->select(['avg_days' => new Expression('AVG((closed_at - created_at) / 86400)')])
            ->where(['estado' => 'entregada'])
            ->andWhere(['between', 'closed_at', $inicio, $fin]);
        
        $result = $query->asArray()->one();
        
        return (float) ($result['avg_days'] ?? 0.0);
    }

    /**
     * Indicador 3: Tasa de cancelación de órdenes
     * Porcentaje de órdenes canceladas vs total creado
     */
    private function getTasaCancelacion(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $totalOrdenes = OrdenServicio::find()
            ->where(['between', 'created_at', $inicio, $fin])
            ->count('*');
        
        if ($totalOrdenes === 0) {
            return 0.0;
        }
        
        $canceladas = OrdenServicio::find()
            ->where(['estado' => 'cancelada'])
            ->andWhere(['between', 'created_at', $inicio, $fin])
            ->count('*');
        
        return (float) (($canceladas / $totalOrdenes) * 100);
    }

    /**
     * Indicador 4: Rotación de inventario
     * Calcula la rotación basada en movimientos de salida vs inventario promedio
     */
    private function getRotacionInventario(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Movimientos de salida (ventas/consumo)
        $salidas = InventoryMovement::find()
            ->where(['tipo_movimiento' => 'salida'])
            ->andWhere(['between', 'created_at', $inicio, $fin])
            ->sum('cantidad');
        
        $salidas = (float) ($salidas ?? 0.0);
        
        // Inventario promedio
        $inventarioTotal = InventoryItem::find()
            ->where(['status' => 1])
            ->sum('cantidad');
        
        $inventarioTotal = (float) ($inventarioTotal ?? 0.0);
        
        if ($inventarioTotal === 0.0) {
            return 0.0;
        }
        
        // Rotación = Salidas / Inventario Promedio
        return (float) ($salidas / $inventarioTotal);
    }

    /**
     * Indicador 5: Tasa de no-show (ausentismo en citas)
     * Porcentaje de citas donde el cliente no se presenta
     */
    private function getTasaNoShow(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $totalCitas = Cita::find()
            ->where(['between', 'fecha', date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->count('*');
        
        if ($totalCitas === 0) {
            return 0.0;
        }
        
        $noShow = Cita::find()
            ->where(['estado' => 'no_show'])
            ->andWhere(['between', 'fecha', date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->count('*');
        
        return (float) (($noShow / $totalCitas) * 100);
    }

    /**
     * Indicador 6: Ingresos promedio por orden de servicio
     * Valor promedio facturado por orden de servicio entregada
     */
    private function getIngresoPromedioOrden(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $totalOrdenes = OrdenServicio::find()
            ->where(['estado' => 'entregada'])
            ->andWhere(['between', 'closed_at', $inicio, $fin])
            ->count('*');
        
        if ($totalOrdenes === 0) {
            return 0.0;
        }
        
        $ingresoTotal = OrdenServicio::find()
            ->where(['estado' => 'entregada'])
            ->andWhere(['between', 'closed_at', $inicio, $fin])
            ->sum('total');
        
        return (float) ($ingresoTotal ?? 0.0) / $totalOrdenes;
    }

    /**
     * Indicador 7: Distribución de órdenes por prioridad
     * Retorna un array con la distribución porcentual por nivel de prioridad
     * Nota: Para el dashboard retornamos el valor dominante (prioridad más frecuente)
     */
    private function getDistribucionPrioridad(): array
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $rows = OrdenServicio::find()
            ->select(['estado', 'prioridad', 'total' => 'COUNT(*)'])
            ->where(['between', 'created_at', $inicio, $fin])
            ->groupBy('prioridad')
            ->asArray()
            ->all();
        
        $distribucion = [
            'baja' => 0,
            'normal' => 0,
            'alta' => 0,
            'urgente' => 0,
        ];
        
        foreach ($rows as $row) {
            $prioridad = (string) ($row['prioridad'] ?? 'normal');
            if (array_key_exists($prioridad, $distribucion)) {
                $distribucion[$prioridad] = (int) ($row['total'] ?? 0);
            }
        }
        
        return $distribucion;
    }

    /**
     * Indicador 8: Tasa de retención de clientes
     * Porcentaje de clientes que retornan al taller en un período de 6 meses
     */
    private function getTasaRetencionClientes(): float
    {
        // Calculamos hace 6 meses
        $haceSeisMeses = strtotime('-6 months');
        $ahora = time();
        
        // Obtenemos todos los clientes activos
        $totalClientes = Cliente::find()
            ->where(['status' => 1])
            ->count('*');
        
        if ($totalClientes === 0) {
            return 0.0;
        }
        
        // Clientes que han tenido más de una orden en los últimos 6 meses
        $clientesActivos = (new \yii\db\Query())
            ->from('{{%orden_servicio}} os')
            ->innerJoin('{{%vehiculo}} v', 'v.id = os.vehiculo_id')
            ->where(['>=', 'os.created_at', $haceSeisMeses])
            ->andWhere(['<=', 'os.created_at', $ahora])
            ->groupBy('v.cliente_id')
            ->having('COUNT(os.id) > 1')
            ->count('*');
        
        return (float) (($clientesActivos / $totalClientes) * 100);
    }

    /**
     * Indicador 9: Productividad por técnico
     * Promedio de órdenes completadas por técnico en el mes
     */
    private function getProductividadTecnico(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Total de técnicos activos
        $totalTecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->count('*');
        
        if ($totalTecnicos === 0) {
            return 0.0;
        }
        
        // Total de asignaciones completadas en el período
        $totalAsignaciones = (new \yii\db\Query())
            ->from('{{%asignacion_orden}} ao')
            ->innerJoin('{{%orden_servicio}} os', 'os.id = ao.orden_id')
            ->where(['>=', 'os.closed_at', $inicio])
            ->andWhere(['<=', 'os.closed_at', $fin])
            ->andWhere(['in', 'os.estado', ['entregada']])
            ->count('*');
        
        // Promedio de órdenes por técnico
        return (float) ($totalAsignaciones / $totalTecnicos);
    }

    /**
     * Indicador 10: Tasa de quiebre de stock
     * Porcentaje de ítems del inventario por debajo del stock mínimo
     */
    private function getTasaQuiebreStock(): float
    {
        $totalItems = InventoryItem::find()
            ->where(['status' => 1])
            ->count('*');
        
        if ($totalItems === 0) {
            return 0.0;
        }
        
        $itemsQuiebre = InventoryItem::find()
            ->where(['status' => 1])
            ->andWhere(new Expression('cantidad < stock_minimo'))
            ->count('*');
        
        return (float) (($itemsQuiebre / $totalItems) * 100);
    }

    /**
     * Indicador 11: Frecuencia de servicio por vehículo
     * Promedio de días entre órdenes consecutivas por vehículo
     */
    private function getFrecuenciaServicioVehiculo(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Obtenemos vehículos con más de una orden en los últimos 6 meses
        $haceSeisMeses = strtotime('-6 months');
        
        $vehiculosConMultipleOrden = (new \yii\db\Query())
            ->from('{{%orden_servicio}} os')
            ->select(['os.vehiculo_id', 'COUNT(*) as total_ordenes'])
            ->innerJoin('{{%vehiculo}} v', 'v.id = os.vehiculo_id')
            ->where(['>=', 'os.created_at', $haceSeisMeses])
            ->groupBy('os.vehiculo_id')
            ->having('COUNT(os.id) > 1')
            ->all();
        
        if (empty($vehiculosConMultipleOrden)) {
            return 0.0;
        }
        
        // Calculamos el promedio de días entre órdenes para cada vehículo
        $totalDias = 0;
        $contadorVehiculos = 0;
        
        foreach ($vehiculosConMultipleOrden as $row) {
            $vehiculoId = (int) $row['vehiculo_id'];
            
            // Obtenemos las órdenes de este vehículo ordenadas por fecha
            $ordenes = OrdenServicio::find()
                ->where(['vehiculo_id' => $vehiculoId])
                ->andWhere(['>=', 'created_at', $haceSeisMeses])
                ->orderBy(['created_at' => SORT_ASC])
                ->all();
            
            if (count($ordenes) < 2) {
                continue;
            }
            
            // Calculamos los días entre la primera y última orden
            $primeraOrden = reset($ordenes);
            $ultimaOrden = end($ordenes);
            
            if ($primeraOrden && $ultimaOrden) {
                $diasEntreOrdenes = ($ultimaOrden->created_at - $primeraOrden->created_at) / 86400;
                $intervalos = count($ordenes) - 1;
                
                if ($intervalos > 0) {
                    $promedioDias = $diasEntreOrdenes / $intervalos;
                    $totalDias += $promedioDias;
                    $contadorVehiculos++;
                }
            }
        }
        
        if ($contadorVehiculos === 0) {
            return 0.0;
        }
        
        return (float) ($totalDias / $contadorVehiculos);
    }

    /**
     * Indicador 12: Margen bruto por servicio
     * Calcula el margen bruto promedio de los servicios
     */
    private function getMargenBrutoServicio(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Obtenemos los detalles de las órdenes con sus costos y precios
        $detalles = (new \yii\db\Query())
            ->from('{{%orden_servicio_detalle}} osd')
            ->innerJoin('{{%servicio}} s', 's.id = osd.servicio_id')
            ->innerJoin('{{%orden_servicio}} os', 'os.id = osd.orden_id')
            ->where(['between', 'os.created_at', $inicio, $fin])
            ->andWhere(['in', 'os.estado', ['entregada', 'en_progreso']])
            ->select(['osd.costo', 'osd.precio_unitario', 'osd.cantidad'])
            ->all();
        
        if (empty($detalles)) {
            return 0.0;
        }
        
        $ingresoTotal = 0.0;
        $costoTotal = 0.0;
        
        foreach ($detalles as $detalle) {
            $precio = (float) ($detalle['precio_unitario'] ?? 0.0);
            $costo = (float) ($detalle['costo'] ?? 0.0);
            $cantidad = (int) ($detalle['cantidad'] ?? 1);
            
            $ingresoTotal += $precio * $cantidad;
            $costoTotal += $costo * $cantidad;
        }
        
        if ($ingresoTotal === 0.0) {
            return 0.0;
        }
        
        // Margen bruto = ((Ingreso - Costo) / Ingreso) × 100
        return (float) ((($ingresoTotal - $costoTotal) / $ingresoTotal) * 100);
    }

    /**
     * Indicador 13: Ingresos por método de pago
     * Retorna un array con la distribución de ingresos por método de pago
     */
    private function getIngresosPorMetodoPago(): array
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        $rows = Pago::find()
            ->select(['metodo_pago', 'total' => 'SUM(monto)'])
            ->where(['in', 'estado', ['completado', 'pagado']])
            ->andWhere(['between', 'created_at', $inicio, $fin])
            ->groupBy('metodo_pago')
            ->asArray()
            ->all();
        
        $distribucion = [
            'efectivo' => 0.0,
            'tarjeta' => 0.0,
            'transferencia' => 0.0,
            'otro' => 0.0,
        ];
        
        foreach ($rows as $row) {
            $metodo = (string) ($row['metodo_pago'] ?? 'otro');
            $monto = (float) ($row['total'] ?? 0.0);
            
            // Normalizar nombre del método
            $metodoNormalizado = match (strtolower($metodo)) {
                'efectivo', 'cash' => 'efectivo',
                'tarjeta', 'card', 'credito', 'debito' => 'tarjeta',
                'transferencia', 'transfer' => 'transferencia',
                default => 'otro',
            };
            
            if (array_key_exists($metodoNormalizado, $distribucion)) {
                $distribucion[$metodoNormalizado] += $monto;
            } else {
                $distribucion['otro'] += $monto;
            }
        }
        
        return $distribucion;
    }

    /**
     * Indicador 14: Tasa de morosidad
     * Porcentaje de pagos pendientes después de la entrega de la orden
     */
    private function getTasaMorosidad(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Total de pagos asociados a órdenes entregadas
        $totalPagos = (new \yii\db\Query())
            ->from('{{%pago}} p')
            ->innerJoin('{{%orden_servicio}} os', 'os.id = p.orden_id')
            ->where(['os.estado' => 'entregada'])
            ->andWhere(['between', 'p.created_at', $inicio, $fin])
            ->count('*');
        
        if ($totalPagos === 0) {
            return 0.0;
        }
        
        // Pagos pendientes
        $pagosPendientes = (new \yii\db\Query())
            ->from('{{%pago}} p')
            ->innerJoin('{{%orden_servicio}} os', 'os.id = p.orden_id')
            ->where(['os.estado' => 'entregada'])
            ->andWhere(['p.estado' => 'pendiente'])
            ->andWhere(['between', 'p.created_at', $inicio, $fin])
            ->count('*');
        
        return (float) (($pagosPendientes / $totalPagos) * 100);
    }

    /**
     * Indicador 15: Ocupación de la agenda
     * Porcentaje de slots disponibles que están siendo utilizados
     */
    private function getOcupacionAgenda(): float
    {
        [$inicio, $fin] = $this->currentMonthRange();
        
        // Slots disponibles: asumimos 8 horas diarias × días laborables del mes
        $diasLaborables = 0;
        $fechaInicio = new \DateTime(date('Y-m-01', $inicio));
        $fechaFin = new \DateTime(date('Y-m-t', $fin));
        $intervalo = new \DateInterval('P1D');
        $periodo = new \DatePeriod($fechaInicio, $intervalo, $fechaFin->modify('+1 day'));
        
        foreach ($periodo as $fecha) {
            $diaSemana = (int) $fecha->format('N');
            // Lunes (1) a Viernes (5) son días laborables
            if ($diaSemana >= 1 && $diaSemana <= 5) {
                $diasLaborables++;
            }
        }
        
        // Asumimos 8 horas diarias × número de técnicos activos
        $totalTecnicos = Tecnico::find()->where(['status' => 1])->count('*');
        $slotsDisponibles = $diasLaborables * 8 * max(1, $totalTecnicos);
        
        if ($slotsDisponibles === 0) {
            return 0.0;
        }
        
        // Citas confirmadas en el mes (cada cita cuenta como 1 slot de hora)
        $citasConfirmadas = Cita::find()
            ->where(['in', 'estado', ['confirmada', 'completada']])
            ->andWhere(['between', 'fecha', date('Y-m-d', $inicio), date('Y-m-d', $fin)])
            ->count('*');
        
        return (float) (($citasConfirmadas / $slotsDisponibles) * 100);
    }

    /**
     * @return array{0:int,1:int}
     */
    private function currentMonthRange(): array
    {
        $inicio = strtotime(date('Y-m-01 00:00:00'));
        $fin = strtotime(date('Y-m-t 23:59:59'));

        return [$inicio, $fin];
    }

    private function buildKpiCacheKey(string $kpi): string
    {
        return 'dashboard:kpi:' . date('Ymd') . ':' . $kpi;
    }

    private function invalidateKpiByName(string $kpi): void
    {
        Yii::$app->cache->delete($this->buildKpiCacheKey($kpi));
    }

    private function defaultValueForKpi(string $nombre): mixed
    {
        return match ($nombre) {
            'ingresos_mes', 'valor_inventario' => 0.0,
            'tasa_entrega_tiempo', 'tiempo_promedio_resolucion', 'tasa_cancelacion', 'rotacion_inventario', 'tasa_no_show' => 0.0,
            'ingreso_promedio_orden', 'tasa_retencion_clientes', 'productividad_tecnico', 'tasa_quiebre_stock' => 0.0,
            'frecuencia_servicio_vehiculo', 'margen_bruto_servicio', 'tasa_morosidad', 'ocupacion_agenda' => 0.0,
            'distribucion_prioridad' => ['baja' => 0, 'normal' => 0, 'alta' => 0, 'urgente' => 0],
            'ingresos_por_metodo_pago' => ['efectivo' => 0.0, 'tarjeta' => 0.0, 'transferencia' => 0.0, 'otro' => 0.0],
            'lista_citas_hoy', 'alertas_stock' => [],
            'ordenes_activas_estado' => [
                'abierto' => 0,
                'en_progreso' => 0,
                'esperando_repuestos' => 0,
                'listo_para_entrega' => 0,
            ],
            default => 0,
        };
    }

    /**
     * @return string[]
     */
    private function getPermisosUsuarioActual(): array
    {
        if (Yii::$app->user->isGuest) {
            return [];
        }

        $identity = Yii::$app->user->identity;
        if ($identity === null) {
            return [];
        }

        try {
            return Permiso::find()
                ->alias('p')
                ->innerJoin('{{%rol_permiso}} rp', 'rp.permiso_id = p.id')
                ->where(['rp.rol_id' => (int) $identity->rol_id])
                ->select('p.nombre')
                ->column();
        } catch (\Throwable $e) {
            Yii::warning('No se pudieron cargar permisos para accesos rapidos: ' . $e->getMessage(), $this->logCategoria);
            return [];
        }
    }
}
