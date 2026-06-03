<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use app\models\OrdenServicio;
use app\models\search\OrdenServicioSearch;
use app\models\OrdenServicioArchivo;
use app\models\ChecklistItem;
use app\models\Seguimiento;
use app\models\Tecnico;
use app\models\Vehiculo;
use app\components\services\OrdenServicioService;
use app\components\behaviors\AccessControlBehavior;
use Exception;

/**
 * OrdenServicioController handles work order operations
 * Routes:
 *  GET    /orden-servicio              index (list)
 *  GET    /orden-servicio/create        create (form)
 *  POST   /orden-servicio/create        create (submit)
 *  GET    /orden-servicio/<id>         view (detail)
 *  POST   /orden-servicio/<id>/cambiar-estado    cambiarEstado
 *  POST   /orden-servicio/<id>/cancelar       cancelar
 *  POST   /orden-servicio/<id>/asignar-tecnico    asignarTecnico
 *  POST   /orden-servicio/<id>/agregar-servicio   agregarServicio
 *  GET    /orden-servicio/<id>/cerrar         verCerrar
 *  POST   /orden-servicio/<id>/cerrar         cerrarOrden
 *  GET    /orden-servicio/reporte/tecnico     reporteTecnico
 */
class OrdenServicioController extends Controller
{
    private OrdenServicioService $service;

    public function __construct($id, $module, ?OrdenServicioService $service = null, array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service ?? new OrdenServicioService();
    }

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'index' => ['get'],
                    'view' => ['get'],
                    'create' => ['get', 'post'],
                    'cambiar-estado' => ['post'],
                    'cancelar' => ['post'],
                    'asignar-tecnico' => ['post'],
                    'agregar-servicio' => ['post'],
                    'ver-cerrar' => ['get'],
                    'cerrar-orden' => ['post'],
                    'reporte-tecnico' => ['get'],
                    'vehiculos-by-cliente' => ['get'],
                    'gestionar-checklist' => ['get', 'post'],
                    'actualizar-checklist-item' => ['post'],
                    // HU-002: Kanban
                    'kanban' => ['get'],
                    'actualizar-estado-kanban' => ['post'],
                    // HU-004: Archivos adjuntos
                    'subir-archivo' => ['post'],
                    'eliminar-archivo' => ['post'],
                    'gestionar-archivos' => ['get', 'post'],
                    // HU-020: Seguimiento post-servicio
                    'programar-seguimiento' => ['post'],
                    'ver-seguimientos' => ['get'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'orden',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'cambiar-estado' => 'editar',
                    'cancelar' => 'editar',
                    'asignar-tecnico' => 'editar',
                    'agregar-servicio' => 'editar',
                    'ver-cerrar' => 'editar',
                    'cerrar-orden' => 'editar',
                    'gestionar-checklist' => 'editar',
                    'actualizar-checklist-item' => 'editar',
                    'reporte-tecnico' => 'ver',
                    'vehiculos-by-cliente' => 'ver',
                    // HU-002: Kanban
                    'kanban' => 'ver',
                    'actualizar-estado-kanban' => 'editar',
                    // HU-004: Archivos adjuntos
                    'subir-archivo' => 'editar',
                    'eliminar-archivo' => 'editar',
                    'gestionar-archivos' => 'editar',
                    // HU-020: Seguimiento post-servicio
                    'programar-seguimiento' => 'editar',
                    'ver-seguimientos' => 'ver',
                ],
            ],
        ];
    }

    /**
     * List all work orders with KPIs and filters
     * HU-001, 009, 018, 023, 028
     */
    public function actionIndex()
    {
        $searchModel = new OrdenServicioSearch();
        $dataProvider = $searchModel->search($this->request->queryParams);
        $kpis = $this->service->getKPIs();

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'kpis' => $kpis,
        ]);
    }

    /**
     * Gestionar checklist de ingreso para una orden (HU-008)
     */
    public function actionGestionarChecklist($id)
    {
        $orden = $this->findModel($id);
        
        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                // Eliminar items existentes
                ChecklistItem::deleteAll(['orden_id' => $id]);
                
                // Crear nuevos items desde el POST
                $items = Yii::$app->request->post('checklist_items', []);
                foreach ($items as $itemDescripcion) {
                    if (!empty(trim($itemDescripcion))) {
                        $checklistItem = new ChecklistItem([
                            'orden_id' => $id,
                            'item' => trim($itemDescripcion),
                            'completado' => false,
                        ]);
                        if (!$checklistItem->save(false)) {
                            throw new Exception('Error al guardar item del checklist');
                        }
                    }
                }
                
                $transaction->commit();
                Yii::$app->session->setFlash('success', 'Checklist de ingreso actualizado correctamente.');
                return $this->redirect(['view', 'id' => $id]);
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }
        
        return $this->render('checklist', [
            'model' => $orden,
        ]);
    }

    /**
     * Actualizar estado de un item del checklist (AJAX) (HU-008)
     */
    public function actionActualizarChecklistItem($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $item = ChecklistItem::findOne($id);
        if ($item === null) {
            return ['success' => false, 'error' => 'Item no encontrado'];
        }
        
        if ($this->request->isPost) {
            $completado = Yii::$app->request->post('completado', false);
            $item->completado = (bool)$completado;
            
            if ($item->save(false)) {
                return ['success' => true, 'porcentaje' => $item->orden->checklistPorcentaje];
            }
            
            return ['success' => false, 'error' => 'No se pudo actualizar el item'];
        }
        
        return ['success' => false, 'error' => 'Método no permitido'];
    }

    /**
     * Create new work order
     * HU-004
     */
    public function actionCreate()
    {
        if ($this->request->isPost) {
            $transaction = Yii::$app->db->beginTransaction();
            try {
                $data = Yii::$app->request->post();
                $usuarioId = (int)Yii::$app->user->id;

                $servicioIds = array_map(
                    'intval',
                    (array)Yii::$app->request->post('servicio_ids', [])
                );
                $servicioIds = array_filter($servicioIds, static fn(int $id): bool => $id > 0);
                $servicioCantidades = array_count_values($servicioIds);

                $orden = $this->service->create($data, $usuarioId);

                foreach ($servicioCantidades as $servicioId => $cantidad) {
                    $this->service->agregarServicio((int)$orden->id, (int)$servicioId, (int)$cantidad, $usuarioId);
                }

                $transaction->commit();

                Yii::$app->session->setFlash('success', 'Orden creada exitosamente: ' . $orden->codigo);
                return $this->redirect(['view', 'id' => $orden->id]);
            } catch (Exception $e) {
                if ($transaction->isActive) {
                    $transaction->rollBack();
                }
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('create');
    }

    /**
     * View order detail with all tabs
     * HU-011, 020, 029
     */
    public function actionView($id)
    {
        $orden = $this->findModel($id);

        return $this->render('view', [
            'model' => $orden,
        ]);
    }

    /**
     * Change order state
     * HU-007, 021
     */
    public function actionCambiarEstado($id)
    {
        if ($this->request->isPost) {
            try {
                $nuevoEstado = Yii::$app->request->post('estado');
                $motivo = Yii::$app->request->post('motivo', '');
                $usuarioId = Yii::$app->user->id;

                $orden = $this->service->cambiarEstado((int)$id, $nuevoEstado, $motivo, $usuarioId);

                Yii::$app->session->setFlash('success', 'Estado actualizado a: ' . $orden->estado);
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Cancel order
     * HU-022
     */
    public function actionCancelar($id)
    {
        if ($this->request->isPost) {
            try {
                $motivo = Yii::$app->request->post('motivo', 'Cancelada por usuario');
                $usuarioId = Yii::$app->user->id;

                $this->service->cancelarOrden((int)$id, $motivo, $usuarioId);

                Yii::$app->session->setFlash('success', 'Orden cancelada.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Assign technician
     * HU-017
     */
    public function actionAsignarTecnico($id)
    {
        if ($this->request->isPost) {
            try {
                $tecnicoId = Yii::$app->request->post('tecnico_id');
                $usuarioId = Yii::$app->user->id;

                $this->service->asignarTecnico((int)$id, (int)$tecnicoId, $usuarioId);

                if ($this->request->isAjax) {
                    return $this->asJson(['success' => true, 'message' => 'Técnico asignado']);
                }

                Yii::$app->session->setFlash('success', 'Técnico asignado correctamente.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                if ($this->request->isAjax) {
                    return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
                }

                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Add service line to order
     * HU-012
     */
    public function actionAgregarServicio($id)
    {
        if ($this->request->isPost) {
            try {
                $servicioId = Yii::$app->request->post('servicio_id');
                $cantidad = (int)Yii::$app->request->post('cantidad', 1);
                $usuarioId = Yii::$app->user->id;

                $detalle = $this->service->agregarServicio((int)$id, (int)$servicioId, $cantidad, $usuarioId);

                if ($this->request->isAjax) {
                    return $this->asJson(['success' => true, 'detalle' => $detalle]);
                }

                Yii::$app->session->setFlash('success', 'Servicio agregado.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                if ($this->request->isAjax) {
                    return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
                }

                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Agregar repuesto a orden (AJAX)
     * HU-013
     */
    public function actionAgregarRepuesto($id)
    {
        if ($this->request->isPost) {
            try {
                $repuestoId = Yii::$app->request->post('repuesto_id');
                $cantidad = (int)Yii::$app->request->post('cantidad', 1);
                $usuarioId = Yii::$app->user->id;

                $ordenRepuesto = $this->service->agregarRepuesto((int)$id, (int)$repuestoId, $cantidad, $usuarioId);

                if ($this->request->isAjax) {
                    return $this->asJson([
                        'success' => true,
                        'message' => 'Repuesto agregado correctamente',
                        'data' => [
                            'id' => $ordenRepuesto->id,
                            'cantidad' => $ordenRepuesto->cantidad,
                            'subtotal' => $ordenRepuesto->subtotal,
                        ]
                    ]);
                }

                Yii::$app->session->setFlash('success', 'Repuesto agregado correctamente.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                if ($this->request->isAjax) {
                    return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
                }

                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Eliminar repuesto de orden
     * HU-013
     */
    public function actionEliminarRepuesto($ordenId, $repuestoId)
    {
        if ($this->request->isPost) {
            try {
                $usuarioId = Yii::$app->user->id;
                $this->service->eliminarRepuesto((int)$ordenId, (int)$repuestoId, $usuarioId);

                if ($this->request->isAjax) {
                    return $this->asJson(['success' => true, 'message' => 'Repuesto eliminado']);
                }

                Yii::$app->session->setFlash('success', 'Repuesto eliminado correctamente.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                if ($this->request->isAjax) {
                    return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
                }

                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $ordenId]);
    }

    /**
     * Actualizar cantidad de repuesto en orden (AJAX)
     * HU-013
     */
    public function actionActualizarCantidadRepuesto($id)
    {
        if ($this->request->isPost) {
            try {
                $nuevaCantidad = (int)Yii::$app->request->post('cantidad');
                $usuarioId = Yii::$app->user->id;

                $ordenRepuesto = $this->service->actualizarCantidadRepuesto((int)$id, $nuevaCantidad, $usuarioId);

                if ($this->request->isAjax) {
                    return $this->asJson([
                        'success' => true,
                        'data' => [
                            'cantidad' => $ordenRepuesto->cantidad,
                            'subtotal' => $ordenRepuesto->subtotal,
                        ]
                    ]);
                }

                Yii::$app->session->setFlash('success', 'Cantidad actualizada.');
                return $this->redirectIsCalling();
            } catch (Exception $e) {
                if ($this->request->isAjax) {
                    return $this->asJson(['success' => false, 'error' => $e->getMessage()]);
                }

                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirectIsCalling();
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Show closing checklist form
     * HU-015
     */
    public function actionVerCerrar($id)
    {
        $orden = $this->findModel($id);

        if ($orden->estado !== OrdenServicio::ESTADO_LISTO_PARA_ENTREGA) {
            throw new NotFoundHttpException('Orden no está en estado "Listo para Entrega".');
        }

        return $this->render('cerrar', ['model' => $orden]);
    }

    /**
     * Close order (mark as entregada)
     * HU-030
     */
    public function actionCerrarOrden($id)
    {
        if ($this->request->isPost) {
            try {
                $checklistIds = Yii::$app->request->post('checklist_ids', []);
                $usuarioId = Yii::$app->user->id;

                $orden = $this->service->cerrarOrden((int)$id, $checklistIds, $usuarioId);

                Yii::$app->session->setFlash('success', 'Orden entregada correctamente.');
                return $this->redirect(['view', 'id' => $orden->id]);
            } catch (Exception $e) {
                Yii::$app->session->setFlash('error', $e->getMessage());
                return $this->redirect(['ver-cerrar', 'id' => $id]);
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Return vehicles for a given client (AJAX)
     */
    public function actionVehiculosByCliente()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $clienteId = (int) Yii::$app->request->get('cliente_id');

        if (!$clienteId) {
            return [];
        }

        return Vehiculo::find()
            ->select(['id', 'patente', 'marca', 'modelo'])
            ->where(['cliente_id' => $clienteId, 'status' => 1])
            ->orderBy(['patente' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Technician productivity report
     * HU-027
     */
    public function actionReporteTecnico()
    {
        // Placeholder for report implementation (Fase 9.5)
        return $this->render('reporte-tecnico');
    }

    /**
     * Gestiona archivos adjuntos de una orden (HU-004)
     * Muestra la vista de gestión de archivos
     */
    public function actionGestionarArchivos($id)
    {
        $orden = $this->findModel($id);
        
        return $this->renderAjax('_archivos', [
            'model' => $orden,
        ]);
    }

    /**
     * Sube un archivo a una orden (HU-004)
     * Soporta carga múltiple vía AJAX
     */
    public function actionSubirArchivo($id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $orden = $this->findModel($id);
        
        if ($this->request->isPost) {
            $archivos = UploadedFile::getInstancesByName('archivos');
            $tipo = Yii::$app->request->post('tipo', OrdenServicioArchivo::TIPO_FOTO);
            $descripcion = Yii::$app->request->post('descripcion', '');
            
            if (empty($archivos)) {
                return ['success' => false, 'error' => 'No se seleccionaron archivos'];
            }
            
            $modelosGuardados = [];
            $errores = [];
            
            foreach ($archivos as $archivo) {
                if ($archivo->error === UPLOAD_ERR_OK) {
                    $modelo = \app\components\helpers\UploadHelper::subirArchivo(
                        $orden->id,
                        $archivo,
                        $tipo,
                        $descripcion
                    );
                    
                    if ($modelo !== null) {
                        $modelosGuardados[] = $modelo;
                    } else {
                        $errores[] = 'Error al subir: ' . $archivo->name;
                    }
                }
            }
            
            if (!empty($modelosGuardados)) {
                return [
                    'success' => true,
                    'message' => count($modelosGuardados) . ' archivo(s) subido(s) correctamente',
                    'archivos' => array_map(function($modelo) {
                        return [
                            'id' => $modelo->id,
                            'nombre' => $modelo->nombre_original,
                            'tipo' => $modelo->tipo,
                            'tamaño' => $modelo->getTamañoFormateado(),
                            'url' => $modelo->getUrl(),
                            'thumbnailUrl' => $modelo->getThumbnailUrl(),
                            'fecha' => Yii::$app->formatter->asDatetime($modelo->created_at),
                        ];
                    }, $modelosGuardados),
                    'errores' => $errores,
                ];
            }
            
            return ['success' => false, 'error' => implode(', ', $errores)];
        }
        
        return ['success' => false, 'error' => 'Método no permitido'];
    }

    /**
     * Elimina un archivo de una orden (HU-004)
     */
    public function actionEliminarArchivo($id, $archivoId)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        $orden = $this->findModel($id);
        
        if ($this->request->isPost) {
            $archivo = OrdenServicioArchivo::findOne(['id' => $archivoId, 'orden_servicio_id' => $orden->id]);
            
            if ($archivo === null) {
                return ['success' => false, 'error' => 'Archivo no encontrado'];
            }
            
            try {
                $nombreArchivo = $archivo->nombre_original;
                if ($archivo->delete()) {
                    return [
                        'success' => true,
                        'message' => 'Archivo "' . $nombreArchivo . '" eliminado correctamente',
                    ];
                }
                
                return ['success' => false, 'error' => 'No se pudo eliminar el archivo'];
            } catch (Exception $e) {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
        
        return ['success' => false, 'error' => 'Método no permitido'];
    }

    /**
     * HU-020: Programa un seguimiento post-servicio para una orden
     */
    public function actionProgramarSeguimiento(int $id)
    {
        $orden = $this->findModel($id);
        
        if ($orden->estado !== 'entregada') {
            Yii::$app->session->setFlash('error', 'Solo se pueden programar seguimientos para órdenes entregadas.');
            return $this->redirect(['view', 'id' => $id]);
        }

        // Verificar si ya existe un seguimiento pendiente para esta orden
        $seguimientoExistente = Seguimiento::find()
            ->where(['orden_servicio_id' => $orden->id, 'estado' => 'pendiente'])
            ->one();

        if ($seguimientoExistente) {
            Yii::$app->session->setFlash('info', 'Ya existe un seguimiento pendiente para esta orden.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $seguimiento = Seguimiento::crearParaOrden($orden);
        
        if ($seguimiento->save()) {
            Yii::$app->session->setFlash('success', 'Seguimiento programado exitosamente para el ' . date('d/m/Y', $seguimiento->fecha_programada));
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo programar el seguimiento. Errores: ' . json_encode($seguimiento->errors));
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * HU-020: Muestra los seguimientos de una orden
     */
    public function actionVerSeguimientos(int $id)
    {
        $orden = $this->findModel($id);
        
        $seguimientosProvider = new \yii\data\ActiveDataProvider([
            'query' => Seguimiento::find()
                ->where(['orden_servicio_id' => $id])
                ->orderBy(['fecha_programada' => SORT_DESC]),
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);

        return $this->renderPartial('_seguimientos', [
            'orden' => $orden,
            'seguimientosProvider' => $seguimientosProvider,
        ]);
    }

    // ─── Private Helper Methods ───────────────────────────────────────

    /**
     * Find model or throw 404
     */
    protected function findModel($id): OrdenServicio
    {
        $model = OrdenServicio::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Orden no encontrada.');
        }
        return $model;
    }

    /**
     * Redirect caller or default to index
     */
    private function redirectIsCalling()
    {
        $referrer = Yii::$app->request->referrer;
        return $this->redirect($referrer ?: ['index']);
    }

    /**
     * Reporte de cumplimiento de checklists
     * HU-028
     */
    public function actionReporteChecklist(?string $fechaDesde = null, ?string $fechaHasta = null): string
    {
        // Por defecto, último mes
        if (!$fechaDesde) {
            $fechaDesde = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$fechaHasta) {
            $fechaHasta = date('Y-m-d');
        }

        $timestampDesde = strtotime($fechaDesde);
        $timestampHasta = strtotime($fechaHasta . ' 23:59:59');

        // Crear modelo de formulario para el reporte
        $formModel = new \app\models\ChecklistReportForm();
        $formModel->fechaDesde = $fechaDesde;
        $formModel->fechaHasta = $fechaHasta;

        // Obtener órdenes en el rango de fechas
        $ordenes = OrdenServicio::find()
            ->where(['between', 'created_at', $timestampDesde, $timestampHasta])
            ->andWhere(['NOT', ['estado' => ['cancelada']]])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $estadisticas = [
            'total_ordenes' => count($ordenes),
            'con_checklist' => 0,
            'checklist_completo' => 0,
            'checklist_parcial' => 0,
            'sin_checklist' => 0,
            'porcentaje_cumplimiento' => 0,
            'items_totales' => 0,
            'items_completados' => 0,
        ];

        $datosPorServicio = [];

        foreach ($ordenes as $orden) {
            $checklistItems = $orden->checklistItems;
            
            if (empty($checklistItems)) {
                $estadisticas['sin_checklist']++;
                continue;
            }

            $estadisticas['con_checklist']++;
            $totalItems = count($checklistItems);
            $itemsCompletados = 0;

            foreach ($checklistItems as $item) {
                if ($item->completado) {
                    $itemsCompletados++;
                }
            }

            $estadisticas['items_totales'] += $totalItems;
            $estadisticas['items_completados'] += $itemsCompletados;

            if ($itemsCompletados === $totalItems) {
                $estadisticas['checklist_completo']++;
            } else {
                $estadisticas['checklist_parcial']++;
            }

            // Agrupar por servicio
            $detalles = $orden->detalles;
            foreach ($detalles as $detalle) {
                    $servicioNombre = $detalle->servicio?->nombre ?? 'Sin servicio';
                if (!isset($datosPorServicio[$servicioNombre])) {
                    $datosPorServicio[$servicioNombre] = [
                        'total_ordenes' => 0,
                        'con_checklist' => 0,
                        'completados' => 0,
                        'items_totales' => 0,
                        'items_completados' => 0,
                    ];
                }
                $datosPorServicio[$servicioNombre]['total_ordenes']++;
                if (!empty($checklistItems)) {
                    $datosPorServicio[$servicioNombre]['con_checklist']++;
                    $datosPorServicio[$servicioNombre]['items_totales'] += $totalItems;
                    $datosPorServicio[$servicioNombre]['items_completados'] += $itemsCompletados;
                    if ($itemsCompletados === $totalItems) {
                        $datosPorServicio[$servicioNombre]['completados']++;
                    }
                }
            }
        }

        // Calcular porcentajes
        if ($estadisticas['items_totales'] > 0) {
            $estadisticas['porcentaje_cumplimiento'] = round(
                ($estadisticas['items_completados'] / $estadisticas['items_totales']) * 100,
                2
            );
        }

        return $this->render('reporte-checklist', [
            'formModel' => $formModel,
            'estadisticas' => $estadisticas,
            'datosPorServicio' => $datosPorServicio,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    /**
     * Vista Kanban de órdenes de servicio
     * HU-002: Seguimiento de Estados de Orden (Kanban)
     */
    public function actionKanban()
    {
        // Obtener filtros de la petición
        $tecnicoId = Yii::$app->request->get('tecnico_id');
        $prioridad = Yii::$app->request->get('prioridad');
        $fechaDesde = Yii::$app->request->get('fecha_desde');
        $fechaHasta = Yii::$app->request->get('fecha_hasta');

        // Query base para las órdenes
        $query = OrdenServicio::find()
            ->where(['NOT', ['estado' => ['cancelada']]])
            ->orderBy(['created_at' => SORT_DESC]);

        // Aplicar filtro por técnico
        if ($tecnicoId) {
            $query->joinWith('asignaciones')
                ->andWhere(['asignacion_orden.tecnico_id' => $tecnicoId]);
        }

        // Aplicar filtro por prioridad
        if ($prioridad && in_array($prioridad, OrdenServicio::PRIORIDADES)) {
            $query->andWhere(['prioridad' => $prioridad]);
        }

        // Aplicar filtro por fecha
        if ($fechaDesde) {
            $timestampDesde = strtotime($fechaDesde);
            $query->andWhere(['>=', 'created_at', $timestampDesde]);
        }
        if ($fechaHasta) {
            $timestampHasta = strtotime($fechaHasta . ' 23:59:59');
            $query->andWhere(['<=', 'created_at', $timestampHasta]);
        }

        // Agrupar órdenes por estado
        $ordenesPorEstado = [];
        foreach (OrdenServicio::ESTADOS as $estado) {
            if ($estado === 'cancelada') {
                continue; // No mostrar canceladas en el kanban
            }
            $ordenesPorEstado[$estado] = (clone $query)->andWhere(['estado' => $estado])->all();
        }

        // Obtener lista de técnicos para el filtro
        $tecnicos = Tecnico::find()->orderBy(['nombre' => SORT_ASC])->all();

        // Calcular tiempo máximo permitido por estado (en horas)
        $tiempoMaximoEstado = [
            'abierto' => 24,
            'en_progreso' => 48,
            'esperando_repuestos' => 72,
            'listo_para_entrega' => 24,
        ];

        return $this->render('kanban', [
            'ordenesPorEstado' => $ordenesPorEstado,
            'tecnicos' => $tecnicos,
            'tecnicoId' => $tecnicoId,
            'prioridad' => $prioridad,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'tiempoMaximoEstado' => $tiempoMaximoEstado,
        ]);
    }

    /**
     * Actualizar estado de orden desde Kanban (drag & drop)
     * HU-002: Seguimiento de Estados de Orden (Kanban)
     */
    public function actionActualizarEstadoKanban()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        try {
            $id = Yii::$app->request->post('id');
            $nuevoEstado = Yii::$app->request->post('estado');

            if (!$id || !$nuevoEstado) {
                return ['success' => false, 'message' => 'Datos incompletos'];
            }

            if (!in_array($nuevoEstado, OrdenServicio::ESTADOS)) {
                return ['success' => false, 'message' => 'Estado inválido'];
            }

            $orden = OrdenServicio::findOne($id);
            if (!$orden) {
                return ['success' => false, 'message' => 'Orden no encontrada'];
            }

            // Verificar si la transición es válida
            if (!$orden->puedeTransicionar($nuevoEstado)) {
                return ['success' => false, 'message' => 'Transición no permitida'];
            }

            $orden->estado = $nuevoEstado;
            if ($orden->save(false)) {
                return ['success' => true, 'message' => 'Estado actualizado correctamente'];
            }

            return ['success' => false, 'message' => 'Error al guardar el estado'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
