<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use app\models\Cliente;
use app\models\search\ClienteSearch;
use app\components\services\ClienteService;
use app\models\User;
use app\models\Etiqueta;
use app\models\ClienteEtiqueta;
use app\components\behaviors\AccessControlBehavior;

/**
 * ClienteController: CRUD de clientes del taller.
 * Requiere usuario autenticado; operaciones de escritura requieren admin u operador.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ClienteController extends BaseController
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
                    'create-ajax' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'cliente',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'eliminar',
                    'create-ajax' => 'crear',
                    'asignar-etiqueta' => 'editar',
                    'export-segmento' => 'ver',
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

    /** Listado paginado con KPI cards y búsqueda. */
    public function actionIndex(): string
    {
        $searchModel  = new ClienteSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $service = new ClienteService();
        $stats   = $service->getEstadisticas();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'stats'        => $stats,
        ]);
    }

    /** Detalle de cliente. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new ClienteService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Cliente', []);
            $cliente = $service->create($data);
            if ($cliente !== null) {
                Yii::$app->session->setFlash('success', 'Cliente creado exitosamente.');
                return $this->redirect(['view', 'id' => $cliente->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('create', ['model' => new Cliente()]);
    }

    /** Formulario de edición. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $cliente = $this->findModel($id);
        $service = new ClienteService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Cliente', []);
            $updated = $service->update($cliente, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Cliente actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $cliente->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', ['model' => $cliente]);
    }

    /** Desactiva un cliente (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new ClienteService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Cliente desactivado exitosamente.');
            if ($service->getWarning() !== '') {
                Yii::$app->session->setFlash('warning', $service->getWarning());
            }
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Exporta el listado de clientes como CSV. */
    public function actionExport(): Response
    {
        $this->requireAdminOrOperador();

        $clientes = Cliente::find()->orderBy('nombre')->all();

        $csv   = "ID,Nombre,Email,Telefono,RUT,Estado,Creado\n";
        foreach ($clientes as $c) {
            $csv .= implode(',', [
                $c->id,
                '"' . str_replace('"', '""', $c->nombre) . '"',
                '"' . ($c->email ?? '') . '"',
                '"' . ($c->telefono ?? '') . '"',
                '"' . ($c->rut ?? '') . '"',
                $c->status ? 'Activo' : 'Inactivo',
                $c->created_at ? date('d/m/Y', $c->created_at) : '',
            ]) . "\n";
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="clientes_' . date('Ymd') . '.csv"');
        $response->content = "\xEF\xBB\xBF" . $csv; // BOM para Excel

        return $response;
    }

    /**
     * Exporta segmento de clientes por etiqueta como CSV (HU-006)
     */
    public function actionExportSegmento(): Response
    {
        $this->requireAdminOrOperador();
        
        $etiquetaId = Yii::$app->request->get('etiqueta_id', 0);
        
        if (!$etiquetaId) {
            Yii::$app->session->setFlash('error', 'Debe seleccionar una etiqueta para exportar.');
            return $this->redirect(['index']);
        }
        
        $etiqueta = Etiqueta::findOne($etiquetaId);
        if (!$etiqueta) {
            Yii::$app->session->setFlash('error', 'Etiqueta no encontrada.');
            return $this->redirect(['index']);
        }
        
        // Obtener clientes con esta etiqueta
        $clientes = Cliente::find()
            ->innerJoin('cliente_etiqueta', 'cliente_etiqueta.cliente_id = cliente.id')
            ->where(['cliente_etiqueta.etiqueta_id' => $etiquetaId])
            ->andWhere(['cliente.status' => 1])
            ->orderBy('cliente.nombre')
            ->all();
        
        $csv = "ID,Nombre,Email,Teléfono,RUT,Cumpleaños,Fuente,Etiquetas\n";
        foreach ($clientes as $c) {
            // Obtener etiquetas del cliente
            $etiquetasCliente = [];
            foreach ($c->etiquetas as $etq) {
                $etiquetasCliente[] = $etq->nombre;
            }
            
            $csv .= implode(',', [
                $c->id,
                '"' . str_replace('"', '""', $c->nombre) . '"',
                '"' . ($c->email ?? '') . '"',
                '"' . ($c->telefono ?? '') . '"',
                '"' . ($c->rut ?? '') . '"',
                '"' . ($c->cumpleanos ?? '') . '"',
                '"' . ($c->fuente ?? '') . '"',
                '"' . implode('; ', $etiquetasCliente) . '"',
            ]) . "\n";
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="segmento_' . strtolower(str_replace(' ', '_', $etiqueta->nombre)) . '_' . date('Ymd') . '.csv"');
        $response->content = "\xEF\xBB\xBF" . $csv; // BOM para Excel

        return $response;
    }

    /**
     * Asigna o elimina una etiqueta a un cliente (AJAX - HU-006)
     */
    public function actionAsignarEtiqueta(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        if (!Yii::$app->request->isPost) {
            return $this->asJson([
                'success' => false,
                'message' => 'Método no permitido.',
            ]);
        }
        
        $etiquetaId = Yii::$app->request->post('etiqueta_id', 0);
        $clienteId = Yii::$app->request->post('cliente_id', 0);
        $asignado = Yii::$app->request->post('asignado', false);
        
        if (!$etiquetaId || !$clienteId) {
            return $this->asJson([
                'success' => false,
                'message' => 'Datos inválidos.',
            ]);
        }
        
        $etiqueta = Etiqueta::findOne($etiquetaId);
        $cliente = Cliente::findOne($clienteId);
        
        if (!$etiqueta || !$cliente) {
            return $this->asJson([
                'success' => false,
                'message' => 'Etiqueta o cliente no encontrado.',
            ]);
        }
        
        try {
            if ($asignado) {
                // Verificar si ya existe la relación
                $existe = ClienteEtiqueta::find()
                    ->where(['cliente_id' => $clienteId, 'etiqueta_id' => $etiquetaId])
                    ->exists();
                
                if (!$existe) {
                    $relacion = new ClienteEtiqueta();
                    $relacion->cliente_id = $clienteId;
                    $relacion->etiqueta_id = $etiquetaId;
                    if (!$relacion->save()) {
                        return $this->asJson([
                            'success' => false,
                            'message' => 'No se pudo asignar la etiqueta.',
                        ]);
                    }
                }
            } else {
                // Eliminar la relación
                ClienteEtiqueta::deleteAll([
                    'cliente_id' => $clienteId,
                    'etiqueta_id' => $etiquetaId,
                ]);
            }
            
            return $this->asJson([
                'success' => true,
                'message' => $asignado ? 'Etiqueta asignada correctamente.' : 'Etiqueta eliminada correctamente.',
            ]);
        } catch (\Exception $e) {
            return $this->asJson([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /** Búsqueda AJAX de clientes para creación rápida de órdenes (HU-001). */
    public function actionSearch(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $q = Yii::$app->request->get('q', '');
        if (strlen($q) < 2) {
            return $this->asJson([]);
        }
        
        $clientes = Cliente::find()
            ->where(['status' => 1])
            ->andWhere(['or',
                ['like', 'nombre', $q],
                ['like', 'rut', $q],
                ['like', 'telefono', $q],
                ['like', 'email', $q],
            ])
            ->orderBy(['nombre' => SORT_ASC])
            ->limit(10)
            ->all();
        
        $results = [];
        foreach ($clientes as $cliente) {
            $results[] = [
                'id' => $cliente->id,
                'nombre' => $cliente->nombre,
                'rut' => $cliente->rut ?? '',
                'telefono' => $cliente->telefono ?? '',
                'email' => $cliente->email ?? '',
                'text' => "{$cliente->nombre} (RUT: " . ($cliente->rut ?? 'N/A') . ")",
            ];
        }
        
        return $this->asJson($results);
    }

    /** Alta rápida de cliente desde modal (AJAX). */
    public function actionCreateAjax(): Response
    {
        $this->requireAdminOrOperador();
        Yii::$app->response->format = Response::FORMAT_JSON;

        if (!Yii::$app->request->isPost) {
            return $this->asJson([
                'success' => false,
                'message' => 'Método no permitido.',
            ]);
        }

        $service = new ClienteService();
        $data = Yii::$app->request->post('Cliente', []);
        $cliente = $service->create($data);

        if ($cliente === null) {
            return $this->asJson([
                'success' => false,
                'message' => $service->getPrimerError() ?: 'No fue posible crear el cliente.',
            ]);
        }

        return $this->asJson([
            'success' => true,
            'id' => $cliente->id,
            'nombre' => $cliente->nombre,
        ]);
    }

    /**
     * Reporte de clientes más frecuentes (HU-024)
     * Muestra ranking de clientes por cantidad de órdenes en un período
     */
    public function actionReporteFrecuentes(): string
    {
        $fechaDesde = Yii::$app->request->get('fecha_desde', date('Y-m-d', strtotime('-6 months')));
        $fechaHasta = Yii::$app->request->get('fecha_hasta', date('Y-m-d'));
        
        // Query para obtener clientes con más órdenes
        $clientesFrecuentes = Cliente::find()
            ->select([
                'cliente.id',
                'cliente.nombre',
                'cliente.email',
                'cliente.telefono',
                'cliente.rut',
                'COUNT(orden_servicio.id) AS total_ordenes',
                'SUM(orden_servicio.total) AS total_gastado',
            ])
            ->innerJoin('orden_servicio', 'orden_servicio.cliente_id = cliente.id')
            ->where(['between', 'orden_servicio.created_at', 
                strtotime($fechaDesde . ' 00:00:00'), 
                strtotime($fechaHasta . ' 23:59:59')
            ])
            ->groupBy('cliente.id, cliente.nombre, cliente.email, cliente.telefono, cliente.rut')
            ->orderBy(['total_ordenes' => SORT_DESC])
            ->limit(20)
            ->asArray()
            ->all();

        return $this->render('reporte-frecuentes', [
            'clientesFrecuentes' => $clientesFrecuentes,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
        ]);
    }

    /**
     * Exporta reporte de clientes frecuentes a CSV (HU-024)
     */
    public function actionReporteFrecuentesCsv(): Response
    {
        $fechaDesde = Yii::$app->request->get('fecha_desde', date('Y-m-d', strtotime('-6 months')));
        $fechaHasta = Yii::$app->request->get('fecha_hasta', date('Y-m-d'));
        
        $clientesFrecuentes = Cliente::find()
            ->select([
                'cliente.id',
                'cliente.nombre',
                'cliente.email',
                'cliente.telefono',
                'cliente.rut',
                'COUNT(orden_servicio.id) AS total_ordenes',
                'SUM(orden_servicio.total) AS total_gastado',
            ])
            ->innerJoin('orden_servicio', 'orden_servicio.cliente_id = cliente.id')
            ->where(['between', 'orden_servicio.created_at', 
                strtotime($fechaDesde . ' 00:00:00'), 
                strtotime($fechaHasta . ' 23:59:59')
            ])
            ->groupBy('cliente.id, cliente.nombre, cliente.email, cliente.telefono, cliente.rut')
            ->orderBy(['total_ordenes' => SORT_DESC])
            ->limit(100)
            ->asArray()
            ->all();

        $csv = "Rank,Cliente,RUT,Teléfono,Email,Órdenes,Total Gastado\n";
        $rank = 0;
        foreach ($clientesFrecuentes as $cliente) {
            $rank++;
            $csv .= implode(',', [
                $rank,
                '"' . str_replace('"', '""', $cliente['nombre']) . '"',
                '"' . ($cliente['rut'] ?? '') . '"',
                '"' . ($cliente['telefono'] ?? '') . '"',
                '"' . ($cliente['email'] ?? '') . '"',
                (int)$cliente['total_ordenes'],
                number_format((float)$cliente['total_gastado'], 2, '.', ''),
            ]) . "\n";
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="clientes_frecuentes_' . date('Ymd') . '.csv"');
        $response->content = "\xEF\xBB\xBF" . $csv; // BOM para Excel

        return $response;
    }

    private function findModel(int $id): Cliente
    {
        $model = Cliente::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Cliente no encontrado.');
        }
        return $model;
    }
}
