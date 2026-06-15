<?php
declare(strict_types=1);

namespace app\components\services;

use app\models\Servicio;
use app\models\OrdenServicio;
use app\models\OrdenServicioDetalle;
use app\models\OrdenServicioRepuesto;
use app\models\InventoryItem;
use app\models\Tecnico;
use app\models\ServicioRentabilidad;
use yii\db\Exception;

/**
 * Servicio de cálculos de rentabilidad por servicio (HU-023).
 * Calcula ingresos, costos directos, mano de obra, overhead y márgenes.
 */
class RentabilidadService
{
    /**
     * Porcentaje de overhead aplicado sobre costos directos.
     * Configurable según política de la empresa (típicamente 15-25%).
     */
    private float $overheadPercentage = 0.20;

    /**
     * Costo fijo de overhead por orden (luz, espacio, administración).
     */
    private float $overheadFijoPorOrden = 5000.0;

    public function __construct()
    {
        // Se puede cargar desde configuración en una implementación real
        // $this->overheadPercentage = Yii::$app->params['rentabilidad.overhead_percentage'] ?? 0.20;
    }

    /**
     * Establece el porcentaje de overhead.
     */
    public function setOverheadPercentage(float $percentage): void
    {
        $this->overheadPercentage = max(0, min(1, $percentage));
    }

    /**
     * Establece el overhead fijo por orden.
     */
    public function setOverheadFijoPorOrden(float $monto): void
    {
        $this->overheadFijoPorOrden = max(0, $monto);
    }

    /**
     * Calcula la rentabilidad para un período específico.
     * 
     * @param string|null $periodo Formato YYYY-MM. Si es null, usa el mes actual.
     * @return array Resultado del cálculo con estadísticas.
     * @throws Exception
     */
    public function calcularRentabilidadPorPeriodo(?string $periodo = null): array
    {
        if ($periodo === null) {
            $periodo = date('Y-m');
        }

        // Obtener todas las órdenes completadas/entregadas en el período
        $fechaInicio = $periodo . '-01';
        $fechaFin = date('Y-m-t', strtotime($fechaInicio)); // Último día del mes

        $ordenes = OrdenServicio::find()
            ->where(['between', 'closed_at', strtotime($fechaInicio), strtotime($fechaFin)])
            ->andWhere(['in', 'estado', ['entregada']])
            ->all();

        $resultados = [];
        $serviciosProcesados = [];

        foreach ($ordenes as $orden) {
            $detalles = $orden->getDetalles()->all();
            
            foreach ($detalles as $detalle) {
                $servicioId = $detalle->servicio_id;
                
                if (!isset($serviciosProcesados[$servicioId])) {
                    $serviciosProcesados[$servicioId] = [
                        'total_ordenes' => 0,
                        'ingreso_total' => 0,
                        'costo_servicio' => 0,
                        'costo_repuestos' => 0,
                        'costo_mano_obra' => 0,
                        'ordenes_ids' => [],
                    ];
                }

                // Evitar doble conteo si el mismo servicio está múltiples veces en la misma orden
                $ordenKey = $orden->id . '_' . $servicioId;
                if (isset($serviciosProcesados[$servicioId]['ordenes_ids'][$ordenKey])) {
                    continue;
                }
                $serviciosProcesados[$servicioId]['ordenes_ids'][$ordenKey] = true;
                $serviciosProcesados[$servicioId]['total_ordenes']++;

                // Ingreso por este servicio en esta orden
                $serviciosProcesados[$servicioId]['ingreso_total'] += (float)$detalle->subtotal;

                // Costo directo del servicio (desde el modelo Servicio o parámetros)
                $servicio = $detalle->servicio;
                if ($servicio && $servicio->duracion_estimada) {
                    // Si hay duración estimada, podríamos usar un costo base
                    $serviciosProcesados[$servicioId]['costo_servicio'] += (float)$servicio->precio_base * 0.3; // 30% como costo estimado
                }

                // Costo de repuestos utilizados en esta orden para este servicio
                $repuestos = OrdenServicioRepuesto::find()
                    ->where(['orden_id' => $orden->id])
                    ->all();
                
                foreach ($repuestos as $repuesto) {
                    $item = $repuesto->repuesto;
                    if ($item) {
                        $serviciosProcesados[$servicioId]['costo_repuestos'] += (float)$repuesto->costo_unitario * (int)$repuesto->cantidad;
                    }
                }

                // Costo de mano de obra (técnicos asignados)
                $asignaciones = $orden->getAsignaciones()->all();
                foreach ($asignaciones as $asignacion) {
                    $tecnico = $asignacion->tecnico;
                    if ($tecnico && $tecnico->costo_hora) {
                        $horasTrabajadas = $asignacion->horas_trabajadas ?? ($servicio->duracion_estimada ? $servicio->duracion_estimada / 60 : 1);
                        $serviciosProcesados[$servicioId]['costo_mano_obra'] += (float)$tecnico->costo_hora * $horasTrabajadas;
                    }
                }
            }
        }

        // Guardar resultados en la tabla de rentabilidad
        foreach ($serviciosProcesados as $servicioId => $datos) {
            $rentabilidad = ServicioRentabilidad::findOne([
                'servicio_id' => $servicioId,
                'periodo' => $periodo,
            ]);

            if (!$rentabilidad) {
                $rentabilidad = new ServicioRentabilidad();
                $rentabilidad->servicio_id = $servicioId;
                $rentabilidad->periodo = $periodo;
            }

            $rentabilidad->total_ordenes = $datos['total_ordenes'];
            $rentabilidad->ingreso_total = round($datos['ingreso_total'], 2);
            $rentabilidad->costo_servicio = round($datos['costo_servicio'], 2);
            $rentabilidad->costo_repuestos = round($datos['costo_repuestos'], 2);
            $rentabilidad->costo_mano_obra = round($datos['costo_mano_obra'], 2);
            
            // Calcular overhead
            $costosDirectos = $datos['costo_servicio'] + $datos['costo_repuestos'] + $datos['costo_mano_obra'];
            $overhead = ($costosDirectos * $this->overheadPercentage) + ($datos['total_ordenes'] * $this->overheadFijoPorOrden);
            $rentabilidad->overhead = round($overhead, 2);

            // El método beforeSave calculará costo_total, utilidad_bruta y margen_porcentaje
            $rentabilidad->save(false);
        }

        return [
            'periodo' => $periodo,
            'servicios_procesados' => count($serviciosProcesados),
            'ordenes_procesadas' => count($ordenes),
            'fecha_calculo' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Obtiene datos para tabla de márgenes por servicio.
     * 
     * @param string|null $periodo Formato YYYY-MM
     * @return array Datos para la vista.
     */
    public function getTablaMargenes(?string $periodo = null): array
    {
        if ($periodo === null) {
            $periodo = ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m');
        }

        $query = ServicioRentabilidad::find()
            ->alias('sr')
            ->innerJoinWith('servicio s')
            ->where(['sr.periodo' => $periodo])
            ->orderBy(['sr.margen_porcentaje' => SORT_DESC]);

        return $query->all();
    }

    /**
     * Obtiene Top 10 servicios más rentables.
     * 
     * @param string|null $periodo Formato YYYY-MM
     * @return array Top 10 servicios.
     */
    public function getTop10Rentables(?string $periodo = null): array
    {
        if ($periodo === null) {
            $periodo = ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m');
        }

        return ServicioRentabilidad::find()
            ->alias('sr')
            ->innerJoinWith('servicio s')
            ->where(['sr.periodo' => $periodo])
            ->andWhere(['>', 'sr.ingreso_total', 0])
            ->orderBy(['sr.margen_porcentaje' => SORT_DESC])
            ->limit(10)
            ->all();
    }

    /**
     * Obtiene Bottom 5 servicios menos rentables.
     * 
     * @param string|null $periodo Formato YYYY-MM
     * @return array Bottom 5 servicios.
     */
    public function getBottom5Rentables(?string $periodo = null): array
    {
        if ($periodo === null) {
            $periodo = ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m');
        }

        return ServicioRentabilidad::find()
            ->alias('sr')
            ->innerJoinWith('servicio s')
            ->where(['sr.periodo' => $periodo])
            ->andWhere(['>', 'sr.ingreso_total', 0])
            ->orderBy(['sr.margen_porcentaje' => SORT_ASC])
            ->limit(5)
            ->all();
    }

    /**
     * Compara rentabilidad entre dos períodos.
     * 
     * @param string $periodoActual Formato YYYY-MM
     * @param string|null $periodoAnterior Formato YYYY-MM. Si es null, calcula el mes anterior.
     * @return array Comparativa con variaciones.
     */
    public function compararPeriodos(string $periodoActual, ?string $periodoAnterior = null): array
    {
        if ($periodoAnterior === null) {
            $periodoAnterior = date('Y-m', strtotime($periodoActual . ' -1 month'));
        }

        $datosActual = $this->getResumenPeriodo($periodoActual);
        $datosAnterior = $this->getResumenPeriodo($periodoAnterior);

        $variaciones = [];
        
        foreach ($datosActual as $key => $valorActual) {
            $valorAnterior = $datosAnterior[$key] ?? 0;
            $variacion = $valorAnterior > 0 ? (($valorActual - $valorAnterior) / $valorAnterior) * 100 : 0;
            $variaciones[$key] = [
                'actual' => $valorActual,
                'anterior' => $valorAnterior,
                'variacion_porcentaje' => round($variacion, 2),
            ];
        }

        return [
            'periodo_actual' => $periodoActual,
            'periodo_anterior' => $periodoAnterior,
            'datos' => $variaciones,
        ];
    }

    /**
     * Obtiene resumen de un período.
     * 
     * @param string $periodo Formato YYYY-MM
     * @return array Resumen con totales.
     */
    private function getResumenPeriodo(string $periodo): array
    {
        $registros = ServicioRentabilidad::find()
            ->where(['periodo' => $periodo])
            ->all();

        $resumen = [
            'ingreso_total' => 0,
            'costo_total' => 0,
            'utilidad_bruta' => 0,
            'margen_promedio' => 0,
            'servicios_count' => count($registros),
        ];

        foreach ($registros as $r) {
            $resumen['ingreso_total'] += (float)$r->ingreso_total;
            $resumen['costo_total'] += (float)$r->costo_total;
            $resumen['utilidad_bruta'] += (float)$r->utilidad_bruta;
        }

        if ($resumen['ingreso_total'] > 0) {
            $resumen['margen_promedio'] = round(($resumen['utilidad_bruta'] / $resumen['ingreso_total']) * 100, 2);
        }

        return $resumen;
    }

    /**
     * Genera datos para gráficos.
     * 
     * @param string|null $periodo Formato YYYY-MM
     * @return array Datos formateados para Chart.js.
     */
    public function getDatosGraficos(?string $periodo = null): array
    {
        $top10 = $this->getTop10Rentables($periodo);
        $bottom5 = $this->getBottom5Rentables($periodo);

        return [
            'top10' => [
                'labels' => array_map(fn($r) => $r->servicio->nombre ?? "Servicio #{$r->servicio_id}", $top10),
                'margenes' => array_map(fn($r) => (float)$r->margen_porcentaje, $top10),
                'ingresos' => array_map(fn($r) => (float)$r->ingreso_total, $top10),
            ],
            'bottom5' => [
                'labels' => array_map(fn($r) => $r->servicio->nombre ?? "Servicio #{$r->servicio_id}", $bottom5),
                'margenes' => array_map(fn($r) => (float)$r->margen_porcentaje, $bottom5),
                'ingresos' => array_map(fn($r) => (float)$r->ingreso_total, $bottom5),
            ],
        ];
    }

    /**
     * Recalcula todos los períodos disponibles.
     * Útil para actualizar datos históricos.
     * 
     * @return array Resultado del proceso.
     */
    public function recalcularTodosPeriodos(): array
    {
        // Obtener rango de fechas desde las órdenes
        $primeraOrden = OrdenServicio::find()
            ->select('MIN(closed_at) as fecha')
            ->scalar();
        
        if (!$primeraOrden) {
            return ['error' => 'No hay órdenes en el sistema'];
        }

        $periodos = [];
        $fechaActual = strtotime(date('Y-m-01'));
        $fechaMinima = (int)$primeraOrden;

        while ($fechaActual >= $fechaMinima) {
            $periodos[] = date('Y-m', $fechaActual);
            $fechaActual = strtotime('-1 month', $fechaActual);
        }

        $resultados = [];
        foreach (array_reverse($periodos) as $periodo) {
            $resultados[$periodo] = $this->calcularRentabilidadPorPeriodo($periodo);
        }

        return [
            'periodos_recalculados' => count($periodos),
            'resultados' => $resultados,
        ];
    }
}
