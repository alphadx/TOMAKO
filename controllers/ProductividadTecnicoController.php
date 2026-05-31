<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use app\models\Tecnico;
use app\models\OrdenServicio;
use app\models\AsignacionOrden;
use app\models\OrdenServicioDetalle;
use yii\db\Expression;

/**
 * Controlador para reporte de productividad de mecánicos.
 * HU-022: Reporte de Productividad de Mecánicos
 */
class ProductividadTecnicoController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /**
     * Dashboard principal de productividad.
     */
    public function actionIndex(): string
    {
        $fechaInicio = Yii::$app->request->get('fecha_inicio', date('Y-m-d', strtotime('-30 days')));
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        // Obtener todos los técnicos activos
        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->with(['especialidad'])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $estadisticas = [];
        foreach ($tecnicos as $tecnico) {
            $stats = $this->calcularEstadisticasTecnico($tecnico, $fechaInicio, $fechaFin);
            
            $estadisticas[$tecnico->id] = [
                'tecnico' => $tecnico,
                'ordenes_completadas' => $stats['ordenes_completadas'],
                'horas_trabajadas' => $stats['horas_trabajadas'],
                'ingreso_generado' => $stats['ingreso_generado'],
                'eficiencia' => $stats['eficiencia'],
            ];
        }

        // Calcular totales
        $totalOrdenes = array_sum(array_column($estadisticas, 'ordenes_completadas'));
        $totalHoras = array_sum(array_column($estadisticas, 'horas_trabajadas'));
        $totalIngreso = array_sum(array_column($estadisticas, 'ingreso_generado'));

        return $this->render('index', [
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'estadisticas' => $estadisticas,
            'totalOrdenes' => $totalOrdenes,
            'totalHoras' => $totalHoras,
            'totalIngreso' => $totalIngreso,
        ]);
    }

    /**
     * Calcula las estadísticas de un técnico en un período determinado.
     */
    private function calcularEstadisticasTecnico(Tecnico $tecnico, string $fechaInicio, string $fechaFin): array
    {
        // Convertir fechas a timestamps para comparación
        $timestampInicio = strtotime($fechaInicio);
        $timestampFin = strtotime($fechaFin) + 86399; // Fin del día

        // Obtener órdenes asignadas al técnico en el período
        $asignaciones = AsignacionOrden::find()
            ->where(['tecnico_id' => $tecnico->id])
            ->joinWith('orden')
            ->andWhere([
                '>=', '{{%orden_servicio}}.created_at',
                $timestampInicio
            ])
            ->andWhere([
                '<=', '{{%orden_servicio}}.closed_at',
                $timestampFin
            ])
            ->all();

        $ordenesCompletadas = 0;
        $horasTrabajadas = 0;
        $ingresoGenerado = 0;

        foreach ($asignaciones as $asignacion) {
            $orden = $asignacion->orden;
            if ($orden === null) {
                continue;
            }

            // Contar solo órdenes completadas/entregadas
            if (in_array($orden->estado, ['listo_para_entrega', 'entregada'])) {
                $ordenesCompletadas++;
            }

            // Calcular horas trabajadas (diferencia entre created_at y closed_at)
            if ($orden->closed_at !== null && $orden->created_at !== null) {
                $horasTrabajadas += ($orden->closed_at - $orden->created_at) / 3600;
            }

            // Calcular ingreso generado (suma de detalles de la orden)
            $detalles = OrdenServicioDetalle::find()
                ->where(['orden_id' => $orden->id])
                ->all();
            
            foreach ($detalles as $detalle) {
                $ingresoGenerado += $detalle->precio_total ?? 0;
            }
        }

        // Calcular eficiencia (órdenes completadas / horas trabajadas)
        $eficiencia = 0;
        if ($horasTrabajadas > 0) {
            $eficiencia = round(($ordenesCompletadas / $horasTrabajadas) * 100, 2);
        }

        return [
            'ordenes_completadas' => $ordenesCompletadas,
            'horas_trabajadas' => round($horasTrabajadas, 2),
            'ingreso_generado' => round($ingresoGenerado, 2),
            'eficiencia' => $eficiencia,
        ];
    }

    /**
     * Detalle de productividad por técnico.
     */
    public function actionDetalle(int $id): string
    {
        $tecnico = Tecnico::findOne($id);
        if ($tecnico === null) {
            throw new \yii\web\NotFoundHttpException('Técnico no encontrado.');
        }

        $fechaInicio = Yii::$app->request->get('fecha_inicio', date('Y-m-d', strtotime('-30 days')));
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        // Cargar las órdenes asignadas al técnico en el período
        $timestampInicio = strtotime($fechaInicio);
        $timestampFin = strtotime($fechaFin) + 86399;

        $asignaciones = AsignacionOrden::find()
            ->where(['tecnico_id' => $tecnico->id])
            ->joinWith(['orden' => function($query) {
                $query->with(['cliente', 'detalles.servicio']);
            }])
            ->andWhere([
                '>=', '{{%orden_servicio}}.created_at',
                $timestampInicio
            ])
            ->andWhere([
                '<=', '{{%orden_servicio}}.closed_at',
                $timestampFin
            ])
            ->orderBy(['{{%orden_servicio}}.created_at' => SORT_DESC])
            ->all();

        $ordenes = [];
        foreach ($asignaciones as $asignacion) {
            if ($asignacion->orden !== null) {
                $ordenes[] = $asignacion->orden;
            }
        }

        return $this->render('detalle', [
            'tecnico' => $tecnico,
            'fechaInicio' => $fechaInicio,
            'fechaFin' => $fechaFin,
            'ordenes' => $ordenes,
        ]);
    }

    /**
     * Exportar reporte a CSV.
     */
    public function actionExportCsv(): Response
    {
        Yii::$app->response->format = Response::FORMAT_HTML;
        
        $fechaInicio = Yii::$app->request->get('fecha_inicio', date('Y-m-d', strtotime('-30 days')));
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->with(['especialidad'])
            ->all();

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \yii\web\ServerErrorHttpException('No se pudo preparar la exportación CSV.');
        }

        fputcsv($handle, [
            'ID',
            'Nombre',
            'Apellido',
            'Especialidad',
            'Órdenes Completadas',
            'Horas Trabajadas',
            'Ingreso Generado',
            'Eficiencia (%)',
        ]);

        foreach ($tecnicos as $tecnico) {
            $stats = $this->calcularEstadisticasTecnico($tecnico, $fechaInicio, $fechaFin);
            
            fputcsv($handle, [
                $tecnico->id,
                $tecnico->nombre,
                $tecnico->apellido,
                $tecnico->especialidad ? $tecnico->especialidad->nombre : 'General',
                $stats['ordenes_completadas'],
                $stats['horas_trabajadas'],
                $stats['ingreso_generado'],
                $stats['eficiencia'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Yii::$app->response->sendContentAsFile(
            $csv !== false ? $csv : '',
            'productividad-tecnicos-' . date('Y-m-d-His') . '.csv',
            ['mimeType' => 'text/csv; charset=UTF-8']
        );
    }

    /**
     * API JSON para gráficos.
     */
    public function actionChartData(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $fechaInicio = Yii::$app->request->get('fecha_inicio', date('Y-m-d', strtotime('-30 days')));
        $fechaFin = Yii::$app->request->get('fecha_fin', date('Y-m-d'));

        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $labels = [];
        $dataOrdenes = [];
        $dataHoras = [];

        foreach ($tecnicos as $tecnico) {
            $labels[] = $tecnico->getFullName();
            $stats = $this->calcularEstadisticasTecnico($tecnico, $fechaInicio, $fechaFin);
            $dataOrdenes[] = $stats['ordenes_completadas'];
            $dataHoras[] = $stats['horas_trabajadas'];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Órdenes Completadas',
                    'data' => $dataOrdenes,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                ],
                [
                    'label' => 'Horas Trabajadas',
                    'data' => $dataHoras,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                ],
            ],
        ];
    }
}
