<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use app\models\Servicio;
use app\models\search\ServicioSearch;
use app\components\services\ServicioService;
use app\models\Categoria;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;
use app\models\PlantillaChecklist;
use app\models\PlantillaChecklistItem;
use app\models\search\PlantillaChecklistSearch;
use app\components\services\RentabilidadService;
use app\models\ServicioRentabilidad;

/**
 * ServicioController: CRUD de servicios del taller.
 * Requiere usuario autenticado; operaciones de escritura requieren admin u operador.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ServicioController extends BaseController
{
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
                'class'   => VerbFilter::class,
                'actions' => [
                    'deactivate' => ['post'],
                    'activate'   => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'servicio',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'editar',
                    'activate' => 'editar',
                    // HU-028: Plantillas de checklist
                    'plantillas-index' => 'ver',
                    'plantillas-view' => 'ver',
                    'plantillas-create' => 'crear',
                    'plantillas-update' => 'editar',
                    'plantillas-delete' => 'eliminar',
                    'plantillas-duplicate' => 'crear',
                ],
            ],
        ];
    }

    private function requireAdminOrOperador(): void
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !in_array($identity->rol_id, [1, 2], true)) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para esta acción.'));
        }
    }

    /** Listado paginado con búsqueda. */
    public function actionIndex(): string
    {
        $searchModel  = new ServicioSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de un servicio. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new ServicioService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Servicio', []);
            $srv  = $service->create($data);
            if ($srv !== null) {
                Yii::$app->session->setFlash('success', 'Servicio creado exitosamente.');
                return $this->redirect(['view', 'id' => $srv->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new Servicio();
        $model->codigo = Servicio::generarCodigo();

        return $this->render('create', [
            'model'       => $model,
            'categorias'  => Categoria::getCategoriasList(),
        ]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $srv     = $this->findModel($id);
        $service = new ServicioService();

        if (!Yii::$app->request->isPost && $service->hasActiveOrders($id)) {
            Yii::$app->session->setFlash('warning', 'Advertencia: este servicio tiene órdenes activas asociadas.');
        }

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Servicio', []);
            $updated = $service->update($srv, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Servicio actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $srv->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', [
            'model'      => $srv,
            'categorias' => Categoria::getCategoriasList(),
        ]);
    }

    /** Desactiva un servicio (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new ServicioService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Servicio desactivado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Activa un servicio inactivo (POST). */
    public function actionActivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new ServicioService();

        if ($service->activate($id)) {
            Yii::$app->session->setFlash('success', 'Servicio activado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Exporta el catálogo de servicios como CSV. */
    public function actionExport(): Response
    {
        $this->requireAdminOrOperador();

        $servicios = Servicio::find()
            ->with('categoria')
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $csv = "Nombre,Categoría,Precio,Duración (min),Estado\n";
        foreach ($servicios as $s) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', (string) $s->nombre) . '"',
                '"' . str_replace('"', '""', (string) ($s->categoria->nombre ?? '')) . '"',
                number_format((float) $s->precio_base, 2, '.', ''),
                (string) ((int) ($s->duracion_estimada ?? 0)),
                $s->status ? 'Activo' : 'Inactivo',
            ]) . "\n";
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="servicios_' . date('Ymd') . '.csv"');
        $response->content = "\xEF\xBB\xBF" . $csv;

        return $response;
    }

    private function findModel(int $id): Servicio
    {
        $model = Servicio::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Servicio no encontrado.');
        }
        return $model;
    }

    private function findPlantillaModel(int $id): PlantillaChecklist
    {
        $model = PlantillaChecklist::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Plantilla no encontrada.');
        }
        return $model;
    }

    // ── HU-028: Gestión de Plantillas de Checklist por Servicio ─────────────

    /**
     * Listado de plantillas de checklist por servicio
     */
    public function actionPlantillasIndex(int $servicioId = null): string
    {
        $searchModel = new PlantillaChecklistSearch();
        $params = Yii::$app->request->queryParams;
        
        if ($servicioId !== null) {
            $params['PlantillaChecklistSearch']['servicio_id'] = $servicioId;
        }
        
        $dataProvider = $searchModel->search($params);

        return $this->render('plantillas-index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'servicioId' => $servicioId,
        ]);
    }

    /**
     * Detalle de una plantilla de checklist
     */
    public function actionPlantillasView(int $id): string
    {
        return $this->render('plantillas-view', [
            'model' => $this->findPlantillaModel($id),
        ]);
    }

    /**
     * Crear nueva plantilla de checklist
     */
    public function actionPlantillasCreate(int $servicioId = null): Response|string
    {
        $this->requireAdminOrOperador();
        
        $model = new PlantillaChecklist();
        if ($servicioId !== null) {
            $model->servicio_id = $servicioId;
        }

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('PlantillaChecklist', []);
            $items = Yii::$app->request->post('PlantillaChecklistItem', []);
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->load($data) && $model->validate()) {
                    if ($model->save(false)) {
                        // Guardar items
                        if (!empty($items['descripcion'])) {
                            foreach ($items['descripcion'] as $index => $descripcion) {
                                if (!empty(trim($descripcion))) {
                                    $item = new PlantillaChecklistItem([
                                        'plantilla_id' => $model->id,
                                        'descripcion' => trim($descripcion),
                                        'orden' => $items['orden'][$index] ?? 0,
                                        'obligatorio' => isset($items['obligatorio'][$index]) && $items['obligatorio'][$index],
                                    ]);
                                    if (!$item->save(false)) {
                                        throw new \Exception('Error al guardar item del checklist');
                                    }
                                }
                            }
                        }
                        
                        $transaction->commit();
                        Yii::$app->session->setFlash('success', 'Plantilla creada exitosamente.');
                        return $this->redirect(['plantillas-view', 'id' => $model->id]);
                    }
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('plantillas-create', [
            'model' => $model,
            'servicios' => Servicio::find()->orderBy(['nombre' => SORT_ASC])->all(),
        ]);
    }

    /**
     * Editar plantilla de checklist existente
     */
    public function actionPlantillasUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        
        $model = $this->findPlantillaModel($id);

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('PlantillaChecklist', []);
            $items = Yii::$app->request->post('PlantillaChecklistItem', []);
            
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->load($data) && $model->validate() && $model->save(false)) {
                    // Eliminar items existentes
                    PlantillaChecklistItem::deleteAll(['plantilla_id' => $model->id]);
                    
                    // Guardar nuevos items
                    if (!empty($items['descripcion'])) {
                        foreach ($items['descripcion'] as $index => $descripcion) {
                            if (!empty(trim($descripcion))) {
                                $item = new PlantillaChecklistItem([
                                    'plantilla_id' => $model->id,
                                    'descripcion' => trim($descripcion),
                                    'orden' => $items['orden'][$index] ?? 0,
                                    'obligatorio' => isset($items['obligatorio'][$index]) && $items['obligatorio'][$index],
                                ]);
                                if (!$item->save(false)) {
                                    throw new \Exception('Error al guardar item del checklist');
                                }
                            }
                        }
                    }
                    
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Plantilla actualizada exitosamente.');
                    return $this->redirect(['plantillas-view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', $e->getMessage());
            }
        }

        return $this->render('plantillas-update', [
            'model' => $model,
            'servicios' => Servicio::find()->orderBy(['nombre' => SORT_ASC])->all(),
        ]);
    }

    /**
     * Eliminar plantilla de checklist
     */
    public function actionPlantillasDelete(int $id): Response
    {
        $this->requireAdminOrOperador();
        
        $model = $this->findPlantillaModel($id);
        $servicioId = $model->servicio_id;
        
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Plantilla eliminada exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'No se pudo eliminar la plantilla.');
        }

        return $this->redirect(['plantillas-index', 'servicioId' => $servicioId]);
    }

    /**
     * Duplicar plantilla de checklist
     */
    public function actionPlantillasDuplicate(int $id): Response
    {
        $this->requireAdminOrOperador();
        
        $original = $this->findPlantillaModel($id);
        
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Crear nueva plantilla basada en la original
            $nueva = new PlantillaChecklist([
                'servicio_id' => $original->servicio_id,
                'nombre' => $original->nombre . ' (Copia)',
                'descripcion' => $original->descripcion,
                'activa' => true,
            ]);
            
            if (!$nueva->save(false)) {
                throw new \Exception('Error al crear la plantilla duplicada');
            }
            
            // Duplicar items
            foreach ($original->items as $item) {
                $nuevoItem = new PlantillaChecklistItem([
                    'plantilla_id' => $nueva->id,
                    'descripcion' => $item->descripcion,
                    'orden' => $item->orden,
                    'obligatorio' => $item->obligatorio,
                ]);
                if (!$nuevoItem->save(false)) {
                    throw new \Exception('Error al duplicar items de la plantilla');
                }
            }
            
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Plantilla duplicada exitosamente.');
            return $this->redirect(['plantillas-update', 'id' => $nueva->id]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['plantillas-view', 'id' => $id]);
        }
    }

    // ── HU-023: Análisis de Rentabilidad por Servicio ─────────────────────────

    /**
     * Vista principal de análisis de rentabilidad.
     */
    public function actionRentabilidad(): string
    {
        $periodo = Yii::$app->request->get('periodo', ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m'));
        
        $rentabilidadService = new RentabilidadService();
        
        // Datos para la vista
        $tablaMargenes = $rentabilidadService->getTablaMargenes($periodo);
        $top10 = $rentabilidadService->getTop10Rentables($periodo);
        $bottom5 = $rentabilidadService->getBottom5Rentables($periodo);
        $datosGraficos = $rentabilidadService->getDatosGraficos($periodo);
        
        // Comparativa con período anterior
        $comparativa = $rentabilidadService->compararPeriodos($periodo);
        
        // Períodos disponibles para el selector
        $periodosDisponibles = ServicioRentabilidad::getPeriodosDisponibles();
        
        return $this->render('rentabilidad', [
            'periodo' => $periodo,
            'tablaMargenes' => $tablaMargenes,
            'top10' => $top10,
            'bottom5' => $bottom5,
            'datosGraficos' => $datosGraficos,
            'comparativa' => $comparativa,
            'periodosDisponibles' => $periodosDisponibles,
        ]);
    }

    /**
     * Acción para recalcular rentabilidad de un período.
     */
    public function actionRecalcularRentabilidad()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        
        try {
            $periodo = Yii::$app->request->post('periodo', date('Y-m'));
            $rentabilidadService = new RentabilidadService();
            $resultado = $rentabilidadService->calcularRentabilidadPorPeriodo($periodo);
            
            return ['success' => true, 'data' => $resultado];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Exportar a Excel (CSV).
     */
    public function actionExportarExcel()
    {
        $periodo = Yii::$app->request->get('periodo', ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m'));
        $rentabilidadService = new RentabilidadService();
        $datos = $rentabilidadService->getTablaMargenes($periodo);
        
        // Headers para descarga CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="rentabilidad_' . $periodo . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Encabezados
        fputcsv($output, [
            'Servicio',
            'Total Órdenes',
            'Ingreso Total',
            'Costo Servicio',
            'Costo Repuestos',
            'Costo Mano de Obra',
            'Overhead',
            'Costo Total',
            'Utilidad Bruta',
            'Margen (%)',
            'Clasificación'
        ]);
        
        // Datos
        foreach ($datos as $registro) {
            fputcsv($output, [
                $registro->servicio->nombre ?? "Servicio #{$registro->servicio_id}",
                $registro->total_ordenes,
                number_format($registro->ingreso_total, 2),
                number_format($registro->costo_servicio, 2),
                number_format($registro->costo_repuestos, 2),
                number_format($registro->costo_mano_obra, 2),
                number_format($registro->overhead, 2),
                number_format($registro->costo_total, 2),
                number_format($registro->utilidad_bruta, 2),
                $registro->margen_porcentaje . '%',
                $registro->getClasificacionMargen()
            ]);
        }
        
        fclose($output);
        Yii::$app->end();
    }

    /**
     * Exportar a PDF.
     */
    public function actionExportarPdf()
    {
        $periodo = Yii::$app->request->get('periodo', ServicioRentabilidad::getUltimoPeriodo() ?? date('Y-m'));
        $rentabilidadService = new RentabilidadService();
        $datos = $rentabilidadService->getTablaMargenes($periodo);
        
        // HTML para PDF
        $html = '<h1>Análisis de Rentabilidad por Servicio</h1>';
        $html .= '<p>Período: ' . $periodo . '</p>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0">';
        $html .= '<thead><tr>';
        $html .= '<th>Servicio</th><th>Órdenes</th><th>Ingresos</th><th>Costos</th><th>Utilidad</th><th>Margen</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($datos as $registro) {
            $html .= '<tr>';
            $html .= '<td>' . ($registro->servicio->nombre ?? "Servicio #{$registro->servicio_id}") . '</td>';
            $html .= '<td>' . $registro->total_ordenes . '</td>';
            $html .= '<td>$' . number_format($registro->ingreso_total, 2) . '</td>';
            $html .= '<td>$' . number_format($registro->costo_total, 2) . '</td>';
            $html .= '<td>$' . number_format($registro->utilidad_bruta, 2) . '</td>';
            $html .= '<td>' . $registro->margen_porcentaje . '%</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Usar MPDF o similar si está disponible, sino retornar HTML
        if (class_exists('\Mpdf\Mpdf')) {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            $mpdf->Output('rentabilidad_' . $periodo . '.pdf', 'D');
        } else {
            // Fallback: descargar como HTML
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="rentabilidad_' . $periodo . '.html"');
            echo $html;
        }
        
        Yii::$app->end();
    }
}
