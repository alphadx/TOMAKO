<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\Pago;
use app\models\OrdenServicio;
use app\models\MetodoPago;
use app\models\search\PagoSearch;
use app\components\services\PagoService;
use app\components\services\CierreCajaService;

/**
 * PagoController: gestión de pagos de órdenes de servicio.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class PagoController extends BaseController
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
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'confirmar' => ['post'],
                    'anular'    => ['post'],
                    'abrir-caja' => ['post'],
                    'cerrar-caja' => ['post'],
                ],
            ],
        ];
    }

    /** Listado con KPI cards y buscador. */
    public function actionIndex(): string
    {
        $searchModel  = new PagoSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $service      = new PagoService();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'kpis'         => $service->getKpis(),
        ]);
    }

    /** Detalle de un pago. */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario para registrar un nuevo pago. */
    public function actionCreate(?int $orden_id = null): Response|string
    {
        $model   = new Pago();
        $service = new PagoService();

        if ($orden_id !== null) {
            $model->orden_id = $orden_id;
        }

        if ($model->load(Yii::$app->request->post())) {
            $pago = $service->registrar($model->getAttributes(null, ['id', 'created_at', 'updated_at']));

            if ($pago !== null) {
                Yii::$app->session->setFlash('success', 'Pago registrado correctamente.');
                return $this->redirect(['view', 'id' => $pago->id]);
            }

            foreach ($service->getErrors() as $err) {
                Yii::$app->session->addFlash('danger', $err);
            }
        }

        $ordenes = OrdenServicio::find()
            ->where(['not', ['estado' => ['cancelada']]])
            ->orderBy(['codigo' => SORT_ASC])
            ->all();

        $metodosPago = MetodoPago::getActivosDropdown();

        return $this->render('create', [
            'model'   => $model,
            'ordenes' => $ordenes,
            'metodosPago' => $metodosPago,
        ]);
    }

    /** Confirma un pago pendiente. */
    public function actionConfirmar(int $id): Response
    {
        $service = new PagoService();
        $pago    = $service->confirmar($id);

        if ($pago !== null) {
            Yii::$app->session->setFlash('success', 'Pago confirmado.');
        } else {
            foreach ($service->getErrors() as $err) {
                Yii::$app->session->addFlash('danger', $err);
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    /** Anula un pago. */
    public function actionAnular(int $id): Response
    {
        $service = new PagoService();
        $motivo = trim((string) Yii::$app->request->post('motivo', ''));
        $pago    = $service->anularConMotivo($id, $motivo !== '' ? $motivo : null);

        if ($pago !== null) {
            Yii::$app->session->setFlash('success', 'Pago anulado.');
        } else {
            foreach ($service->getErrors() as $err) {
                Yii::$app->session->addFlash('danger', $err);
            }
        }

        return $this->redirect(Yii::$app->request->referrer ?: ['index']);
    }

    public function actionHistorial(int $ordenId): string
    {
        $orden = OrdenServicio::findOne($ordenId);
        if ($orden === null) {
            throw new NotFoundHttpException('Orden no encontrada.');
        }

        $service = new PagoService();

        return $this->render('historial', [
            'orden' => $orden,
            'pagos' => $service->getPagosPorOrden($ordenId),
            'saldoPendiente' => $service->getSaldoPendiente($ordenId),
        ]);
    }

    public function actionReporteIngresos(?string $desde = null, ?string $hasta = null): string
    {
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $service = new PagoService();
        $data = $service->getReporteIngresos($desde, $hasta);

        return $this->render('reportes', [
            'tipo' => 'ingresos',
            'desde' => $desde,
            'hasta' => $hasta,
            'data' => $data,
        ]);
    }

    public function actionReportePorMetodo(?string $desde = null, ?string $hasta = null): string
    {
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $service = new PagoService();
        $data = $service->getReportePorMetodo($desde, $hasta);

        return $this->render('reportes', [
            'tipo' => 'metodo',
            'desde' => $desde,
            'hasta' => $hasta,
            'data' => $data,
        ]);
    }

    public function actionExportarCsv(string $tipo, ?string $desde = null, ?string $hasta = null): Response
    {
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');
        $service = new PagoService();

        $rows = $tipo === 'metodo'
            ? $service->getReportePorMetodo($desde, $hasta)
            : $service->getReporteIngresos($desde, $hasta);

        Yii::$app->response->format = Response::FORMAT_RAW;
        Yii::$app->response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        Yii::$app->response->headers->set('Content-Disposition', 'attachment; filename="reporte_' . $tipo . '_' . date('Ymd_His') . '.csv"');
        Yii::$app->response->content = $service->exportarCsv($rows);
        return Yii::$app->response;
    }

    public function actionCierreCaja(?string $desde = null, ?string $hasta = null): string
    {
        $desde = $desde ?: date('Y-m-01');
        $hasta = $hasta ?: date('Y-m-d');

        $service = new CierreCajaService();
        $usuarioId = (int) Yii::$app->user->id;
        $cierreActual = $service->getCierreActual($usuarioId);
        $cierresDataProvider = $service->getCierresPorPeriodo($desde, $hasta);
        $totalesMetodo = $cierreActual ? $service->getTotalesPorMetodo($cierreActual->fecha, $cierreActual->id) : [];

        return $this->render('cierre-caja', [
            'cierreActual' => $cierreActual,
            'cierresDataProvider' => $cierresDataProvider,
            'totalesMetodo' => $totalesMetodo,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    public function actionAbrirCaja(): Response
    {
        $montoInicial = (float) Yii::$app->request->post('monto_inicial', 0);
        $service = new CierreCajaService();
        $cierre = $service->abrirCaja((int) Yii::$app->user->id, $montoInicial);

        if ($cierre !== null) {
            Yii::$app->session->setFlash('success', 'Caja abierta correctamente.');
        } else {
            Yii::$app->session->setFlash('danger', $service->getPrimerError());
        }

        return $this->redirect(['cierre-caja']);
    }

    public function actionCerrarCaja(int $id): Response
    {
        $montoFinal = (float) Yii::$app->request->post('monto_final', 0);
        $service = new CierreCajaService();
        $cierre = $service->cerrarCaja($id, $montoFinal);

        if ($cierre !== null) {
            Yii::$app->session->setFlash('success', 'Caja cerrada correctamente.');
        } else {
            Yii::$app->session->setFlash('danger', $service->getPrimerError());
        }

        return $this->redirect(['cierre-caja']);
    }

    private function findModel(int $id): Pago
    {
        $model = Pago::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Pago no encontrado.');
        }
        return $model;
    }
}
