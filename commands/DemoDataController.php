<?php

declare(strict_types=1);

namespace app\commands;

use app\models\AsignacionOrden;
use app\models\Categoria;
use app\models\Cita;
use app\models\CitaServicio;
use app\models\Cliente;
use app\models\ClienteEtiqueta;
use app\models\Especialidad;
use app\models\Etiqueta;
use app\models\EvaluacionProveedor;
use app\models\InventoryItem;
use app\models\InventoryMovement;
use app\models\Marca;
use app\models\MetodoPago;
use app\models\Modelo;
use app\models\Notificacion;
use app\models\OrdenCompra;
use app\models\OrdenCompraItem;
use app\models\OrdenEstadoLog;
use app\models\OrdenNota;
use app\models\OrdenServicio;
use app\models\OrdenServicioArchivo;
use app\models\OrdenServicioDetalle;
use app\models\Pago;
use app\models\PlantillaChecklist;
use app\models\PlantillaChecklistItem;
use app\models\Proveedor;
use app\models\ProveedorProducto;
use app\models\Seguimiento;
use app\models\Servicio;
use app\models\ServicioRentabilidad;
use app\models\Tecnico;
use app\models\User;
use app\models\Vehiculo;
use DateInterval;
use DateTimeImmutable;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\Connection;
use yii\helpers\FileHelper;

/**
 * Generador de datos demo para un mes de operacion.
 */
class DemoDataController extends Controller
{
    private const DEMO_DOMAIN = 'demo.tallersmart.local';

    /**
     * Siembra datos demo por al menos un mes y genera una historia de trabajo.
     *
     * Uso:
     * php yii demo-data/sembrar-mes
     * php yii demo-data/sembrar-mes 2026-04-24 31 1
     */
    public function actionSembrarMes(string $inicio = '2026-04-24', int $dias = 31, int $limpiar = 1): int
    {
        if ($dias < 30) {
            $this->stderr("El parametro dias debe ser >= 30.\n");
            return ExitCode::DATAERR;
        }

        $fechaInicio = DateTimeImmutable::createFromFormat('Y-m-d', $inicio);
        if ($fechaInicio === false) {
            $this->stderr("Fecha invalida. Use formato YYYY-MM-DD.\n");
            return ExitCode::DATAERR;
        }

        $fechaFin = $fechaInicio->add(new DateInterval('P' . ($dias - 1) . 'D'));
        $db = Yii::$app->db;
        mt_srand(20260424);

        $tx = $db->beginTransaction();

        try {
            if ($limpiar === 1) {
                $this->limpiarDatasetDemo($db);
                $this->stdout("[demo] Dataset demo anterior eliminado.\n");
            }

            $adminId = $this->resolverUsuarioAdminId();
            $metodosPago = $this->resolverMetodosPago();
            $servicios = $this->resolverServicios();
            $tecnicos = $this->asegurarTecnicosDemo($fechaInicio);
            $inventario = $this->asegurarInventarioDemo($fechaInicio, $adminId);
            [$clientes, $vehiculos] = $this->crearClientesYVehiculos($fechaInicio);

            $stats = [
                'clientes' => count($clientes),
                'vehiculos' => count($vehiculos),
                'citas' => 0,
                'ordenes' => 0,
                'pagos' => 0,
                'notificaciones' => 0,
                'movimientos' => 0,
                'ingresos' => 0.0,
            ];

            $eventosSemanales = [];
            $ordenConsecutivo = 1;
            $pagoConsecutivo = 1;

            for ($d = 0; $d < $dias; $d++) {
                $dia = $fechaInicio->add(new DateInterval('P' . $d . 'D'));
                $esFinSemana = in_array((int) $dia->format('N'), [6, 7], true);
                $citasDia = $esFinSemana ? 1 : mt_rand(2, 4);

                if ((int) $dia->format('N') === 1) {
                    $stats['movimientos'] += $this->registrarEntradaSemanalInventario($inventario, $dia, $adminId);
                }

                for ($i = 0; $i < $citasDia; $i++) {
                    $cliente = $clientes[array_rand($clientes)];
                    $vehiculo = $vehiculos[$cliente->id][array_rand($vehiculos[$cliente->id])];
                    $bloque = 8 + ($i * 2);
                    $horaInicio = sprintf('%02d:00:00', min($bloque, 17));
                    $horaFin = sprintf('%02d:00:00', min($bloque + 1, 18));

                    $estadoCita = $this->determinarEstadoCita($dia, $fechaFin);
                    $cita = new Cita([
                        'cliente_id' => (int) $cliente->id,
                        'vehiculo_id' => (int) $vehiculo->id,
                        'fecha' => $dia->format('Y-m-d'),
                        'hora_inicio' => $horaInicio,
                        'hora_fin' => $horaFin,
                        'estado' => $estadoCita,
                        'notas' => '[DEMO] ' . $this->notaCitaPorEstado($estadoCita),
                    ]);
                    $cita->save(false);
                    $this->setCreatedUpdated('{{%cita}}', (int) $cita->id, $dia->getTimestamp(), $dia->getTimestamp());
                    $stats['citas']++;

                    $serviciosCita = $this->seleccionarServicios($servicios, mt_rand(1, 3));
                    foreach ($serviciosCita as $servicio) {
                        $citaServicio = new CitaServicio([
                            'cita_id' => (int) $cita->id,
                            'servicio_id' => (int) $servicio->id,
                        ]);
                        $citaServicio->save(false);
                    }

                    if (!in_array($estadoCita, ['confirmada', 'en_progreso', 'completada'], true)) {
                        continue;
                    }

                    $estadoOrden = $this->determinarEstadoOrden($dia, $fechaFin, $estadoCita);
                    $orden = new OrdenServicio([
                        'codigo' => sprintf('DMO-%s-%03d', $fechaInicio->format('ymd'), $ordenConsecutivo++),
                        'cliente_id' => (int) $cliente->id,
                        'vehiculo_id' => (int) $vehiculo->id,
                        'cita_id' => (int) $cita->id,
                        'estado' => $estadoOrden,
                        'prioridad' => $this->prioridadPorEstado($estadoOrden),
                        'notas_generales' => '[DEMO] Orden generada automaticamente para historia operativa.',
                        'total' => 0,
                    ]);
                    $orden->save(false);

                    $aperturaTs = $dia->setTime((int) substr($horaInicio, 0, 2), 0)->getTimestamp();
                    $cierreTs = in_array($estadoOrden, ['entregada', 'cancelada'], true)
                        ? $dia->add(new DateInterval('PT' . mt_rand(2, 6) . 'H'))->getTimestamp()
                        : null;

                    $this->setOrdenTiempos((int) $orden->id, (int) $cita->id, $aperturaTs, $cierreTs);
                    $stats['ordenes']++;

                    $totalOrden = 0.0;
                    foreach ($serviciosCita as $servicio) {
                        $cantidad = mt_rand(1, 2);
                        $precioUnitario = (float) $servicio->precio_base;

                        $detalle = new OrdenServicioDetalle([
                            'orden_id' => (int) $orden->id,
                            'servicio_id' => (int) $servicio->id,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precioUnitario,
                            'nota' => 'Servicio aplicado durante jornada demo.',
                        ]);
                        $detalle->save(false);
                        $totalOrden += $cantidad * $precioUnitario;

                        $stats['movimientos'] += $this->consumirInventarioPorServicio($inventario, $servicio, $orden, $dia, $adminId);
                    }

                    Yii::$app->db->createCommand()->update('{{%orden_servicio}}', [
                        'total' => round($totalOrden, 2),
                        'updated_at' => $aperturaTs,
                    ], ['id' => (int) $orden->id])->execute();

                    $tecnico = $tecnicos[array_rand($tecnicos)];
                    $asignacion = new AsignacionOrden([
                        'orden_id' => (int) $orden->id,
                        'tecnico_id' => (int) $tecnico->id,
                    ]);
                    $asignacion->save(false);
                    Yii::$app->db->createCommand()->update('{{%asignacion_orden}}', [
                        'asignado_at' => $aperturaTs,
                    ], ['id' => (int) $asignacion->id])->execute();

                    $nota = new OrdenNota([
                        'orden_id' => (int) $orden->id,
                        'usuario_id' => $adminId,
                        'texto' => '[DEMO] Diagnostico inicial realizado y plan de trabajo asignado.',
                    ]);
                    $nota->save(false);
                    Yii::$app->db->createCommand()->update('{{%orden_nota}}', [
                        'created_at' => $aperturaTs,
                    ], ['id' => (int) $nota->id])->execute();

                    $this->registrarLogEstado((int) $orden->id, null, 'abierto', $adminId, $aperturaTs, 'Ingreso desde agenda de cita.');
                    if ($estadoOrden !== 'abierto') {
                        $this->registrarLogEstado((int) $orden->id, 'abierto', $estadoOrden, $adminId, $aperturaTs + 1800, 'Actualizacion operativa segun avance de taller.');
                    }

                    if (in_array($estadoOrden, ['listo_para_entrega', 'entregada'], true)) {
                        $this->crearNotificacionDemo(
                            $adminId,
                            Notificacion::TIPO_ORDEN_LISTA,
                            '[DEMO] Orden ' . $orden->codigo . ' lista para entrega',
                            'La orden ' . $orden->codigo . ' del vehiculo ' . $vehiculo->patente . ' esta lista para cierre.',
                            '/orden/' . (int) $orden->id,
                            $dia->add(new DateInterval('PT5H'))->getTimestamp()
                        );
                        $stats['notificaciones']++;
                    }

                    if (in_array($estadoOrden, ['en_progreso', 'listo_para_entrega', 'entregada'], true)) {
                        $estadoPago = $estadoOrden === 'entregada' ? 'pagado' : 'pendiente';
                        $metodo = $metodosPago[array_rand($metodosPago)];
                        $pagoTs = $dia->add(new DateInterval('PT6H'))->getTimestamp();

                        $pago = new Pago([
                            'orden_id' => (int) $orden->id,
                            'usuario_id' => $adminId,
                            'monto' => round($totalOrden, 2),
                            'metodo_pago_id' => (int) $metodo['id'],
                            'metodo_pago' => (string) $metodo['codigo'],
                            'referencia' => sprintf('DEMO-%s-%03d', $fechaInicio->format('ymd'), $pagoConsecutivo++),
                            'estado' => $estadoPago,
                            'notas' => 'Pago de demostracion para dataset mensual.',
                            'observaciones' => 'Operacion registrada por comando de consola.',
                            'pagado_at' => $estadoPago === 'pagado' ? $pagoTs : null,
                        ]);
                        $pago->save(false);
                        $this->setCreatedUpdated('{{%pago}}', (int) $pago->id, $pagoTs, $pagoTs);
                        $stats['pagos']++;

                        if ($estadoPago === 'pagado') {
                            $stats['ingresos'] += (float) $pago->monto;
                        }
                    }

                    $semana = 'Semana ' . ($this->numeroSemanaRelativa($fechaInicio, $dia));
                    $eventosSemanales[$semana][] = sprintf(
                        '%s: %s atendio %s (%s), orden %s en estado %s por $%s.',
                        $dia->format('d-m-Y'),
                        $tecnico->getFullName(),
                        $cliente->nombre,
                        $vehiculo->patente,
                        $orden->codigo,
                        str_replace('_', ' ', $estadoOrden),
                        number_format($totalOrden, 0, ',', '.')
                    );
                }
            }

            $historia = $this->generarHistoriaMarkdown($fechaInicio, $fechaFin, $stats, $eventosSemanales);
            $rutaHistoria = $this->guardarHistoria($historia, $fechaInicio, $fechaFin);

            $tx->commit();

            $this->stdout("\n[demo] Datos demo generados con exito.\n");
            $this->stdout("Clientes: {$stats['clientes']} | Vehiculos: {$stats['vehiculos']} | Citas: {$stats['citas']} | Ordenes: {$stats['ordenes']} | Pagos: {$stats['pagos']}\n");
            $this->stdout('Ingresos simulados: $' . number_format((float) $stats['ingresos'], 0, ',', '.') . "\n");
            $this->stdout("Historia: {$rutaHistoria}\n\n");

            return ExitCode::OK;
        } catch (Throwable $e) {
            $tx->rollBack();
            $this->stderr("[demo] Error al sembrar datos: {$e->getMessage()}\n");
            $this->stderr($e->getTraceAsString() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }

    private function limpiarDatasetDemo(Connection $db): void
    {
        $ordenIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%orden_servicio}}')
            ->where(['like', 'codigo', 'DMO-%', false])
            ->column($db);

        $citaIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%cita}}')
            ->where(['like', 'notas', '[DEMO]%', false])
            ->column($db);

        if ($ordenIds !== []) {
            $db->createCommand()->delete('{{%pago}}', ['orden_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%orden_estado_log}}', ['orden_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%orden_nota}}', ['orden_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%asignacion_orden}}', ['orden_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%orden_servicio_detalle}}', ['orden_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%seguimiento}}', ['orden_servicio_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%orden_servicio_archivo}}', ['orden_servicio_id' => $ordenIds])->execute();
            $db->createCommand()->delete('{{%orden_servicio}}', ['id' => $ordenIds])->execute();
        }

        if ($citaIds !== []) {
            $db->createCommand()->delete('{{%cita_servicio}}', ['cita_id' => $citaIds])->execute();
            $db->createCommand()->delete('{{%cita}}', ['id' => $citaIds])->execute();
        }

        $itemIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%inventory_item}}')
            ->where(['like', 'sku', 'DMO-INS-%', false])
            ->column($db);

        if ($itemIds !== []) {
            $db->createCommand()->delete('{{%inventory_movement}}', ['item_id' => $itemIds])->execute();
            $db->createCommand()->delete('{{%inventory_item}}', ['id' => $itemIds])->execute();
        }

        // Limpiar datos relacionados con proveedores demo
        $ordenCompraIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%orden_compra}}')
            ->where(['like', 'numero_orden', 'DMO-OC-%', false])
            ->column($db);

        if ($ordenCompraIds !== []) {
            $db->createCommand()->delete('{{%orden_compra_item}}', ['orden_compra_id' => $ordenCompraIds])->execute();
            $db->createCommand()->delete('{{%evaluacion_proveedor}}', ['orden_compra_id' => $ordenCompraIds])->execute();
            $db->createCommand()->delete('{{%orden_compra}}', ['id' => $ordenCompraIds])->execute();
        }

        $db->createCommand()->delete('{{%inventory_movement}}', ['like', 'referencia', 'DEMO-', false])->execute();
        $db->createCommand()->delete('{{%notificacion}}', ['like', 'titulo', '[DEMO]%', false])->execute();
        $db->createCommand()->delete('{{%tecnico}}', ['like', 'email', 'tecnico.demo.%@' . self::DEMO_DOMAIN, false])->execute();
        $db->createCommand()->delete('{{%vehiculo}}', ['like', 'patente', 'ZD%', false])->execute();
        $db->createCommand()->delete('{{%cliente}}', ['like', 'email', '%@' . self::DEMO_DOMAIN, false])->execute();
        
        // Limpiar etiquetas, cliente_etiqueta, proveedor y proveedor_producto demo
        $db->createCommand()->delete('{{%cliente_etiqueta}}', ['like', 'notas', '[DEMO]%', false])->execute();
        $db->createCommand()->delete('{{%etiqueta}}', ['like', 'nombre', 'Demo %', false])->execute();
        $db->createCommand()->delete('{{%proveedor_producto}}', ['like', 'observaciones', '[DEMO]%', false])->execute();
        $db->createCommand()->delete('{{%proveedor}}', ['like', 'email', '%demo.%@' . self::DEMO_DOMAIN, false])->execute();
        $db->createCommand()->delete('{{%proveedor}}', ['like', 'nombre', 'Demo %', false])->execute();
        $db->createCommand()->delete('{{%plantilla_checklist}}', ['like', 'nombre', 'Demo %', false])->execute();
        $db->createCommand()->delete('{{%servicio_rentabilidad}}', ['like', 'periodo', '2026-05%', false])->execute();
    }

    private function resolverUsuarioAdminId(): int
    {
        $admin = User::find()->where(['rol_id' => 1, 'activo' => 1])->orderBy(['id' => SORT_ASC])->one();
        if ($admin === null) {
            throw new \RuntimeException('No se encontro un usuario administrador activo.');
        }

        return (int) $admin->id;
    }

    /**
     * @return array<int, array{id:int,codigo:string}>
     */
    private function resolverMetodosPago(): array
    {
        $rows = MetodoPago::find()->where(['activo' => 1])->orderBy(['id' => SORT_ASC])->all();
        if ($rows === []) {
            $this->crearMetodoPagoFallback();
            $rows = MetodoPago::find()->where(['activo' => 1])->orderBy(['id' => SORT_ASC])->all();
        }

        $metodos = [];
        foreach ($rows as $row) {
            $metodos[] = ['id' => (int) $row->id, 'codigo' => (string) $row->codigo];
        }

        return $metodos;
    }

    private function crearMetodoPagoFallback(): void
    {
        $ahora = time();
        $catalogo = [
            ['efectivo', 'Efectivo'],
            ['tarjeta_debito', 'Tarjeta Debito'],
            ['tarjeta_credito', 'Tarjeta Credito'],
            ['transferencia', 'Transferencia'],
            ['otro', 'Otro'],
        ];

        foreach ($catalogo as [$codigo, $nombre]) {
            Yii::$app->db->createCommand()->insert('{{%metodo_pago}}', [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'activo' => 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ])->execute();
        }
    }

    /**
     * @return Servicio[]
     */
    private function resolverServicios(): array
    {
        $servicios = Servicio::find()->where(['status' => 1])->orderBy(['id' => SORT_ASC])->all();
        if ($servicios !== []) {
            return $servicios;
        }

        $categoria = Categoria::find()->where(['in', 'tipo', ['servicio', 'ambos']])->orderBy(['id' => SORT_ASC])->one();
        if ($categoria === null) {
            throw new \RuntimeException('No hay categorias para registrar servicios demo.');
        }

        $base = [
            ['S-DMO-01', 'Cambio de aceite premium', 32000, 45],
            ['S-DMO-02', 'Alineacion y balanceo', 28000, 60],
            ['S-DMO-03', 'Diagnostico electrico', 22000, 40],
            ['S-DMO-04', 'Mantencion de frenos', 36000, 90],
            ['S-DMO-05', 'Limpieza de inyectores', 30000, 70],
        ];

        foreach ($base as [$codigo, $nombre, $precio, $duracion]) {
            $servicio = new Servicio([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => 'Servicio demo generado desde consola.',
                'precio_base' => $precio,
                'duracion_estimada' => $duracion,
                'categoria_id' => (int) $categoria->id,
                'status' => 1,
            ]);
            $servicio->save(false);
        }

        return Servicio::find()->where(['status' => 1])->orderBy(['id' => SORT_ASC])->all();
    }

    /**
     * @return Tecnico[]
     */
    private function asegurarTecnicosDemo(DateTimeImmutable $fechaInicio): array
    {
        $tecnicos = Tecnico::find()
            ->where(['like', 'email', 'tecnico.demo.%@' . self::DEMO_DOMAIN, false])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (count($tecnicos) >= 4) {
            return $tecnicos;
        }

        $especialidades = Especialidad::find()->where(['status' => 1])->orderBy(['id' => SORT_ASC])->all();
        if ($especialidades === []) {
            $this->crearEspecialidadesFallback();
            $especialidades = Especialidad::find()->where(['status' => 1])->orderBy(['id' => SORT_ASC])->all();
        }

        $nombres = [
            ['Matias', 'Rojas'],
            ['Camila', 'Nunez'],
            ['Diego', 'Pizarro'],
            ['Fernanda', 'Lopez'],
            ['Sebastian', 'Salas'],
        ];

        for ($i = 1; $i <= 4; $i++) {
            $especialidad = $especialidades[($i - 1) % count($especialidades)];
            $tecnico = new Tecnico([
                'nombre' => $nombres[$i - 1][0],
                'apellido' => $nombres[$i - 1][1],
                'email' => sprintf('tecnico.demo.%02d@%s', $i, self::DEMO_DOMAIN),
                'telefono' => sprintf('+56 9 %04d %04d', 4500 + $i, 2200 + $i),
                'especialidad_id' => (int) $especialidad->id,
                'costo_hora' => 15000 + ($i * 1200),
                'status' => 1,
            ]);
            $tecnico->save(false);
            $ts = $fechaInicio->add(new DateInterval('P' . $i . 'D'))->getTimestamp();
            $this->setCreatedUpdated('{{%tecnico}}', (int) $tecnico->id, $ts, $ts);
        }

        return Tecnico::find()
            ->where(['like', 'email', 'tecnico.demo.%@' . self::DEMO_DOMAIN, false])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    private function crearEspecialidadesFallback(): void
    {
        $ahora = time();
        $base = [
            ['Mecanica General', 'Especialidad creada para dataset demo.'],
            ['Electricidad Automotriz', 'Especialidad creada para dataset demo.'],
            ['Frenos y Suspension', 'Especialidad creada para dataset demo.'],
        ];

        foreach ($base as [$nombre, $descripcion]) {
            Yii::$app->db->createCommand()->insert('{{%especialidad}}', [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'status' => 1,
                'created_at' => $ahora,
            ])->execute();
        }
    }

    /**
     * @return InventoryItem[]
     */
    private function asegurarInventarioDemo(DateTimeImmutable $fechaInicio, int $adminId): array
    {
        $items = InventoryItem::find()
            ->where(['like', 'sku', 'DMO-INS-%', false])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        if (count($items) >= 6) {
            return $items;
        }

        $categoria = Categoria::find()->where(['in', 'tipo', ['insumo', 'ambos']])->orderBy(['id' => SORT_ASC])->one();
        if ($categoria === null) {
            throw new \RuntimeException('No hay categorias de insumo para crear inventario demo.');
        }

        $insumos = [
            ['DMO-INS-001', 'Aceite 5W30', 9500, 90, 20, 'litro'],
            ['DMO-INS-002', 'Filtro de aceite', 5500, 70, 18, 'unidad'],
            ['DMO-INS-003', 'Pastilla de freno delantera', 14000, 35, 10, 'unidad'],
            ['DMO-INS-004', 'Liquido de frenos DOT4', 6900, 40, 12, 'litro'],
            ['DMO-INS-005', 'Bujia iridio', 7200, 55, 16, 'unidad'],
            ['DMO-INS-006', 'Filtro de aire', 6000, 45, 14, 'unidad'],
        ];

        foreach ($insumos as $idx => [$sku, $nombre, $precio, $cantidad, $stockMinimo, $unidad]) {
            $item = new InventoryItem([
                'sku' => $sku,
                'nombre' => $nombre,
                'descripcion' => 'Insumo demo para consumo en ordenes de servicio.',
                'categoria_id' => (int) $categoria->id,
                'precio_unitario' => $precio,
                'cantidad' => $cantidad,
                'stock_minimo' => $stockMinimo,
                'stock_maximo' => $cantidad + 50,
                'unidad' => $unidad,
                'ubicacion' => 'Bodega A-' . ($idx + 1),
                'status' => 1,
            ]);
            $item->save(false);

            $ts = $fechaInicio->add(new DateInterval('P' . $idx . 'D'))->getTimestamp();
            $this->setCreatedUpdated('{{%inventory_item}}', (int) $item->id, $ts, $ts);

            $mov = new InventoryMovement([
                'item_id' => (int) $item->id,
                'tipo' => 'entrada',
                'cantidad_delta' => (int) $cantidad,
                'cantidad_anterior' => 0,
                'cantidad_nueva' => (int) $cantidad,
                'usuario_id' => $adminId,
                'referencia' => 'DEMO-APERTURA',
                'created_at' => $ts,
            ]);
            $mov->save(false);
        }

        return InventoryItem::find()->where(['like', 'sku', 'DMO-INS-%', false])->orderBy(['id' => SORT_ASC])->all();
    }

    /**
     * @return array{0:Cliente[],1:array<int,Vehiculo[]>}
     */
    private function crearClientesYVehiculos(DateTimeImmutable $fechaInicio): array
    {
        $nombres = [
            'Carlos Gomez', 'Andrea Soto', 'Javier Torres', 'Paula Reyes', 'Nicolas Fuentes', 'Daniela Silva',
            'Mauricio Rivas', 'Valentina Diaz', 'Hector Mendez', 'Gabriela Ortega', 'Ignacio Molina', 'Francisca Lara',
            'Pedro Araya', 'Antonia Ruiz', 'Ramon Caceres', 'Monica Espinoza', 'Felipe Contreras', 'Carolina Vergara',
        ];

        $marcasNombres = ['Toyota', 'Kia', 'Hyundai', 'Mazda', 'Chevrolet', 'Nissan'];
        $modelosPorMarca = [
            'Toyota' => ['Yaris', 'Corolla', 'RAV4'],
            'Kia' => ['Rio', 'Seltos', 'Sportage'],
            'Hyundai' => ['Accent', 'Creta', 'Tucson'],
            'Mazda' => ['Mazda 3', 'CX-3', 'CX-5'],
            'Chevrolet' => ['Onix', 'Tracker', 'Equinox'],
            'Nissan' => ['Versa', 'Kicks', 'X-Trail'],
        ];

        $clientes = [];
        $vehiculosPorCliente = [];

        // Crear etiquetas demo para segmentación de clientes
        $etiquetasDemo = [];
        $etiquetasNombres = [
            ['nombre' => 'Demo Frecuente', 'color' => Etiqueta::COLOR_SUCCESS],
            ['nombre' => 'Demo VIP', 'color' => Etiqueta::COLOR_PRIMARY],
            ['nombre' => 'Demo Nuevo', 'color' => Etiqueta::COLOR_INFO],
        ];

        foreach ($etiquetasNombres as $etiquetaData) {
            $etiqueta = new Etiqueta([
                'nombre' => $etiquetaData['nombre'],
                'color' => $etiquetaData['color'],
                'descripcion' => 'Etiqueta demo para segmentación de clientes.',
                'status' => 1,
            ]);
            $etiqueta->save(false);
            $etiquetasDemo[] = $etiqueta;
        }

        foreach ($nombres as $idx => $nombreCompleto) {
            $cliente = new Cliente([
                'nombre' => $nombreCompleto,
                'email' => sprintf('cliente.demo.%02d@%s', $idx + 1, self::DEMO_DOMAIN),
                'telefono' => sprintf('+56 9 %04d %04d', 3000 + $idx, 7000 + $idx),
                'direccion' => 'Av. Demo ' . (1200 + $idx) . ', Santiago',
                'status' => 1,
                'notas' => 'Cliente demo incorporado para historia mensual de trabajo.',
            ]);
            $cliente->save(false);

            $tsCliente = $fechaInicio->add(new DateInterval('P' . min($idx, 20) . 'D'))->getTimestamp();
            $this->setCreatedUpdated('{{%cliente}}', (int) $cliente->id, $tsCliente, $tsCliente);
            $clientes[] = $cliente;

            // Asignar etiqueta según patrón
            if ($idx % 3 === 0) {
                $clienteEtiqueta = new ClienteEtiqueta([
                    'cliente_id' => (int) $cliente->id,
                    'etiqueta_id' => (int) $etiquetasDemo[0]->id,
                    'notas' => '[DEMO] Cliente frecuente con múltiples servicios.',
                ]);
                $clienteEtiqueta->save(false);
            } elseif ($idx % 3 === 1) {
                $clienteEtiqueta = new ClienteEtiqueta([
                    'cliente_id' => (int) $cliente->id,
                    'etiqueta_id' => (int) $etiquetasDemo[1]->id,
                    'notas' => '[DEMO] Cliente VIP con preferencias especiales.',
                ]);
                $clienteEtiqueta->save(false);
            } else {
                $clienteEtiqueta = new ClienteEtiqueta([
                    'cliente_id' => (int) $cliente->id,
                    'etiqueta_id' => (int) $etiquetasDemo[2]->id,
                    'notas' => '[DEMO] Cliente nuevo en el sistema.',
                ]);
                $clienteEtiqueta->save(false);
            }

            $cantidadVehiculos = ($idx % 5 === 0) ? 2 : 1;
            $vehiculosPorCliente[(int) $cliente->id] = [];

            for ($v = 0; $v < $cantidadVehiculos; $v++) {
                $numero = ($idx * 2) + $v + 1;
                $marcaNombre = $marcasNombres[$numero % count($marcasNombres)];
                
                // Buscar o crear marca
                $marca = Marca::buscarOCrear($marcaNombre);
                
                // Buscar o crear modelo
                $modeloNombre = $modelosPorMarca[$marcaNombre][$numero % count($modelosPorMarca[$marcaNombre])];
                $modelo = Modelo::buscarOCrear((int) $marca->id, $modeloNombre);
                
                $patente = sprintf('ZD%04d', $numero);
                $vehiculo = new Vehiculo([
                    'patente' => $patente,
                    'marca' => $marcaNombre,
                    'modelo' => $modeloNombre,
                    'marca_id' => (int) $marca->id,
                    'modelo_id' => (int) $modelo->id,
                    'anio' => 2016 + ($numero % 9),
                    'vin' => strtoupper(substr(md5('VIN-' . $numero), 0, 17)),
                    'cliente_id' => (int) $cliente->id,
                    'ultimo_km' => 35000 + ($numero * 1200),
                    'status' => 1,
                ]);
                $vehiculo->save(false);
                $this->setCreatedUpdated('{{%vehiculo}}', (int) $vehiculo->id, $tsCliente, $tsCliente);
                $vehiculosPorCliente[(int) $cliente->id][] = $vehiculo;
            }
        }

        return [$clientes, $vehiculosPorCliente];
    }

    private function determinarEstadoCita(DateTimeImmutable $dia, DateTimeImmutable $fin): string
    {
        if ($dia->getTimestamp() > $fin->sub(new DateInterval('P2D'))->getTimestamp()) {
            return mt_rand(0, 100) < 55 ? 'confirmada' : 'pendiente';
        }

        $sorteo = mt_rand(1, 100);
        if ($sorteo <= 70) {
            return 'completada';
        }
        if ($sorteo <= 85) {
            return 'confirmada';
        }
        if ($sorteo <= 92) {
            return 'cancelada';
        }

        return 'no_show';
    }

    private function notaCitaPorEstado(string $estado): string
    {
        return match ($estado) {
            'completada' => 'Servicio completado dentro de jornada normal.',
            'confirmada' => 'Cliente confirmo asistencia, pendiente ingreso a box.',
            'cancelada' => 'Cliente reagendo por conflicto horario.',
            'no_show' => 'Cliente no asistio a la cita agendada.',
            default => 'Cita en planificacion de agenda.',
        };
    }

    private function determinarEstadoOrden(DateTimeImmutable $dia, DateTimeImmutable $fin, string $estadoCita): string
    {
        if ($estadoCita === 'confirmada') {
            return 'abierto';
        }

        if ($dia->getTimestamp() > $fin->sub(new DateInterval('P3D'))->getTimestamp()) {
            return mt_rand(0, 1) === 1 ? 'en_progreso' : 'listo_para_entrega';
        }

        $r = mt_rand(1, 100);
        if ($r <= 55) {
            return 'entregada';
        }
        if ($r <= 75) {
            return 'listo_para_entrega';
        }
        if ($r <= 90) {
            return 'en_progreso';
        }

        return 'esperando_repuestos';
    }

    private function prioridadPorEstado(string $estado): string
    {
        return match ($estado) {
            'esperando_repuestos' => 'alta',
            'en_progreso' => 'normal',
            'listo_para_entrega' => 'normal',
            'entregada' => 'baja',
            default => 'normal',
        };
    }

    /**
     * @param Servicio[] $servicios
     * @return Servicio[]
     */
    private function seleccionarServicios(array $servicios, int $cantidad): array
    {
        shuffle($servicios);
        return array_slice($servicios, 0, min($cantidad, count($servicios)));
    }

    private function setCreatedUpdated(string $tabla, int $id, int $createdAt, int $updatedAt): void
    {
        Yii::$app->db->createCommand()->update($tabla, [
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ], ['id' => $id])->execute();
    }

    private function setOrdenTiempos(int $ordenId, int $citaId, int $openedAt, ?int $closedAt): void
    {
        Yii::$app->db->createCommand()->update('{{%orden_servicio}}', [
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'created_at' => $openedAt,
            'updated_at' => $closedAt ?? $openedAt,
        ], ['id' => $ordenId])->execute();

        Yii::$app->db->createCommand()->update('{{%cita}}', [
            'orden_servicio_id' => $ordenId,
        ], ['id' => $citaId])->execute();
    }

    private function registrarLogEstado(int $ordenId, ?string $estadoAnterior, string $estadoNuevo, int $usuarioId, int $timestamp, string $comentario): void
    {
        $log = new OrdenEstadoLog([
            'orden_id' => $ordenId,
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => $estadoNuevo,
            'usuario_id' => $usuarioId,
            'comentario' => $comentario,
        ]);
        $log->save(false);

        Yii::$app->db->createCommand()->update('{{%orden_estado_log}}', [
            'created_at' => $timestamp,
        ], ['id' => (int) $log->id])->execute();
    }

    private function crearNotificacionDemo(int $usuarioId, string $tipo, string $titulo, string $mensaje, string $url, int $timestamp): void
    {
        $notificacion = new Notificacion([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'url' => $url,
            'leida' => 0,
        ]);
        $notificacion->save(false);

        Yii::$app->db->createCommand()->update('{{%notificacion}}', [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ], ['id' => (int) $notificacion->id])->execute();
    }

    /**
     * @param InventoryItem[] $inventario
     */
    private function registrarEntradaSemanalInventario(array $inventario, DateTimeImmutable $dia, int $adminId): int
    {
        $movimientos = 0;
        $seleccion = $inventario;
        shuffle($seleccion);

        foreach (array_slice($seleccion, 0, 2) as $item) {
            $delta = mt_rand(8, 16);
            $anterior = (int) $item->cantidad;
            $nuevo = $anterior + $delta;

            Yii::$app->db->createCommand()->update('{{%inventory_item}}', [
                'cantidad' => $nuevo,
                'updated_at' => $dia->getTimestamp(),
            ], ['id' => (int) $item->id])->execute();

            $mov = new InventoryMovement([
                'item_id' => (int) $item->id,
                'tipo' => 'entrada',
                'cantidad_delta' => $delta,
                'cantidad_anterior' => $anterior,
                'cantidad_nueva' => $nuevo,
                'usuario_id' => $adminId,
                'referencia' => 'DEMO-REABASTECIMIENTO',
                'created_at' => $dia->add(new DateInterval('PT7H'))->getTimestamp(),
            ]);
            $mov->save(false);
            $item->cantidad = $nuevo;
            $movimientos++;
        }

        return $movimientos;
    }

    /**
     * @param InventoryItem[] $inventario
     */
    private function consumirInventarioPorServicio(array $inventario, Servicio $servicio, OrdenServicio $orden, DateTimeImmutable $dia, int $adminId): int
    {
        if ($inventario === []) {
            return 0;
        }

        $item = $inventario[array_rand($inventario)];
        $delta = mt_rand(1, 3) * -1;
        $anterior = (int) $item->cantidad;
        $nuevo = max(0, $anterior + $delta);

        Yii::$app->db->createCommand()->update('{{%inventory_item}}', [
            'cantidad' => $nuevo,
            'updated_at' => $dia->add(new DateInterval('PT5H'))->getTimestamp(),
        ], ['id' => (int) $item->id])->execute();

        $mov = new InventoryMovement([
            'item_id' => (int) $item->id,
            'tipo' => 'salida',
            'cantidad_delta' => $delta,
            'cantidad_anterior' => $anterior,
            'cantidad_nueva' => $nuevo,
            'usuario_id' => $adminId,
            'referencia' => 'DEMO-ORDEN-' . $orden->codigo . '-SERV-' . $servicio->codigo,
            'created_at' => $dia->add(new DateInterval('PT5H'))->getTimestamp(),
        ]);
        $mov->save(false);

        $item->cantidad = $nuevo;

        return 1;
    }

    private function numeroSemanaRelativa(DateTimeImmutable $inicio, DateTimeImmutable $dia): int
    {
        $diffDias = (int) floor(($dia->getTimestamp() - $inicio->getTimestamp()) / 86400);
        return intdiv($diffDias, 7) + 1;
    }

    /**
     * @param array<string, mixed> $stats
     * @param array<string, array<int, string>> $eventosSemanales
     */
    private function generarHistoriaMarkdown(DateTimeImmutable $inicio, DateTimeImmutable $fin, array $stats, array $eventosSemanales): string
    {
        $lineas = [];
        $lineas[] = '# Historia operativa demo';
        $lineas[] = '';
        $lineas[] = '- Periodo: ' . $inicio->format('d-m-Y') . ' al ' . $fin->format('d-m-Y');
        $lineas[] = '- Clientes atendidos: ' . $stats['clientes'];
        $lineas[] = '- Vehiculos en operacion: ' . $stats['vehiculos'];
        $lineas[] = '- Citas registradas: ' . $stats['citas'];
        $lineas[] = '- Ordenes de servicio: ' . $stats['ordenes'];
        $lineas[] = '- Pagos registrados: ' . $stats['pagos'];
        $lineas[] = '- Notificaciones internas: ' . $stats['notificaciones'];
        $lineas[] = '- Movimientos de inventario: ' . $stats['movimientos'];
        $lineas[] = '- Ingresos simulados: $' . number_format((float) $stats['ingresos'], 0, ',', '.');
        $lineas[] = '- Proveedores creados: ' . ($stats['proveedores'] ?? 0);
        $lineas[] = '- Ordenes de compra: ' . ($stats['ordenes_compra'] ?? 0);
        $lineas[] = '- Evaluaciones proveedor: ' . ($stats['evaluaciones'] ?? 0);
        $lineas[] = '- Seguimientos realizados: ' . ($stats['seguimientos'] ?? 0);
        $lineas[] = '- Archivos adjuntos: ' . ($stats['archivos'] ?? 0);
        $lineas[] = '- Plantillas checklist: ' . ($stats['plantillas'] ?? 0);
        $lineas[] = '- Etiquetas cliente: ' . ($stats['etiquetas'] ?? 0);
        $lineas[] = '- Rentabilidad calculada: ' . ($stats['rentabilidad'] ?? 0) . ' periodos';
        $lineas[] = '';
        $lineas[] = '## Relato del mes';
        $lineas[] = '';
        $lineas[] = 'El 24 de abril de 2026 comenzo un ciclo intensivo de trabajo en taller. Durante las primeras jornadas se consolidaron los turnos de diagnostico, y en la segunda semana ya se operaba con flujo continuo de recepcion, reparacion y cierre.';
        $lineas[] = '';
        $lineas[] = 'La operacion mostro un patron estable: las citas completadas alimentaron ordenes de servicio, las ordenes gatillaron consumo de insumos y los cierres exitosos terminaron en pagos y notificaciones internas de entrega.';
        $lineas[] = '';
        $lineas[] = 'Adicionalmente, se incorporaron proveedores para gestion de compras, evaluaciones de desempeno, seguimientos post-servicio para medir satisfaccion de clientes, plantillas de checklist estandarizadas, archivos adjuntos en ordenes, etiquetas para segmentacion de clientes, y calculos de rentabilidad por servicio.';
        $lineas[] = '';

        ksort($eventosSemanales);
        foreach ($eventosSemanales as $semana => $eventos) {
            $lineas[] = '### ' . $semana;
            $lineas[] = '';
            foreach (array_slice($eventos, 0, 8) as $evento) {
                $lineas[] = '- ' . $evento;
            }
            if (count($eventos) > 8) {
                $lineas[] = '- ... y ' . (count($eventos) - 8) . ' eventos adicionales en esta semana.';
            }
            $lineas[] = '';
        }

        $lineas[] = '## Cierre narrativo';
        $lineas[] = '';
        $lineas[] = 'Al finalizar el 24 de mayo de 2026, el taller habia completado un mes de operacion trazable de punta a punta: agenda, diagnostico, ejecucion tecnica, control de inventario y facturacion. El dataset resultante permite demo funcional, pruebas de reportes y validacion de flujos entre modulos.';
        $lineas[] = '';

        return implode("\n", $lineas) . "\n";
    }

    private function guardarHistoria(string $contenido, DateTimeImmutable $inicio, DateTimeImmutable $fin): string
    {
        $dir = Yii::getAlias('@app/runtime/reportes');
        FileHelper::createDirectory($dir);

        $path = $dir . '/historia_demo_' . $inicio->format('Ymd') . '_' . $fin->format('Ymd') . '.md';
        file_put_contents($path, $contenido);

        return $path;
    }
}
