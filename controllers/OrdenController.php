<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use app\models\OrdenServicio;
use app\models\Servicio;
use app\models\Cliente;
use app\models\Vehiculo;
use app\models\Tecnico;
use app\models\search\OrdenServicioSearch;
use app\components\services\OrdenService;

/**
 * OrdenController: gestión de órdenes de servicio.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class OrdenController extends BaseController
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
                    'cambiar-estado'  => ['post'],
                    'agregar-nota'    => ['post'],
                    'asignar-tecnico' => ['post'],
                    'desasignar-tecnico' => ['post'],
                ],
            ],
        ];
    }

    /** Listado con KPI cards y buscador. */
    public function actionIndex(): string
    {
        $searchModel  = new OrdenServicioSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $service      = new OrdenService();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'kpis'         => $service->getKpis(),
        ]);
    }

    /** Detalle completo de la orden. */
    public function actionView(int $id): string
    {
        $orden    = $this->findModel($id);
        $tecnicos = Tecnico::find()->where(['status' => 1])->orderBy(['apellido' => SORT_ASC])->all();

        return $this->render('view', [
            'model'    => $orden,
            'tecnicos' => $tecnicos,
        ]);
    }

    /** Formulario de creación. */
    public function actionCreate(): Response|string
    {
        $model     = new OrdenServicio();
        $service   = new OrdenService();
        $clientes  = Cliente::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $servicios = Servicio::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $tecnicos  = Tecnico::find()->where(['status' => 1])->orderBy(['apellido' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $data           = Yii::$app->request->post('OrdenServicio', []);
            $servicioItems  = Yii::$app->request->post('servicios', []);
            $tecnicoIds     = (array) Yii::$app->request->post('tecnico_ids', []);
            $checklistItems = (array) Yii::$app->request->post('checklist_items', []);

            $orden = $service->create($data, $servicioItems, $tecnicoIds, $checklistItems);
            if ($orden !== null) {
                Yii::$app->session->setFlash('success', "Orden {$orden->codigo} creada exitosamente.");
                return $this->redirect(['view', 'id' => $orden->id]);
            }
            $model->setAttributes($data);
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('create', [
            'model'     => $model,
            'clientes'  => $clientes,
            'servicios' => $servicios,
            'tecnicos'  => $tecnicos,
        ]);
    }

    /** Edición (solo estado abierto). */
    public function actionUpdate(int $id): Response|string
    {
        $orden = $this->findModel($id);

        if ($orden->estado !== 'abierto') {
            Yii::$app->session->setFlash('error', 'Solo se pueden editar órdenes en estado abierto.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $service   = new OrdenService();
        $clientes  = Cliente::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $servicios = Servicio::find()->where(['status' => 1])->orderBy(['nombre' => SORT_ASC])->all();
        $tecnicos  = Tecnico::find()->where(['status' => 1])->orderBy(['apellido' => SORT_ASC])->all();

        if (Yii::$app->request->isPost) {
            $data          = Yii::$app->request->post('OrdenServicio', []);
            $servicioItems = Yii::$app->request->post('servicios', []);
            $tecnicoIds    = (array) Yii::$app->request->post('tecnico_ids', []);

            $orden->setAttributes($data);
            if ($orden->validate() && $orden->save(false)) {
                // Reconstruir detalles
                \app\models\OrdenServicioDetalle::deleteAll(['orden_id' => $orden->id]);
                foreach ($servicioItems as $item) {
                    $service->agregarDetalle(
                        $orden,
                        (int) ($item['servicio_id'] ?? 0),
                        (int) ($item['cantidad'] ?? 1),
                        isset($item['precio_unitario']) ? (float) $item['precio_unitario'] : null,
                        isset($item['nota']) ? trim((string) $item['nota']) : null
                    );
                }
                // Actualizar técnicos
                \app\models\AsignacionOrden::deleteAll(['orden_id' => $orden->id]);
                foreach (array_unique($tecnicoIds) as $tid) {
                    $service->asignarTecnico($orden, (int) $tid);
                }
                $orden->calcularTotal();
                Yii::$app->session->setFlash('success', 'Orden actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $orden->id]);
            }
            Yii::$app->session->setFlash('error', 'Error al actualizar la orden.');
        }

        return $this->render('update', [
            'model'     => $orden,
            'clientes'  => $clientes,
            'servicios' => $servicios,
            'tecnicos'  => $tecnicos,
        ]);
    }

    /** Cambia el estado de la orden (POST). */
    public function actionCambiarEstado(int $id): Response
    {
        $orden       = $this->findModel($id);
        $nuevoEstado = Yii::$app->request->post('nuevo_estado', '');
        $comentario  = Yii::$app->request->post('comentario', '');
        $service     = new OrdenService();
        $userId      = (int) Yii::$app->user->id;

        if ($service->cambiarEstado($orden, $nuevoEstado, $comentario, $userId)) {
            Yii::$app->session->setFlash('success', 'Estado actualizado correctamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Agrega una nota a la orden (POST). */
    public function actionAgregarNota(int $id): Response
    {
        $texto   = trim(Yii::$app->request->post('texto', ''));
        $service = new OrdenService();
        $userId  = (int) Yii::$app->user->id;

        if ($texto !== '') {
            if ($service->agregarNota($id, $texto, $userId) === null) {
                Yii::$app->session->setFlash('error', $service->getPrimerError());
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Asigna un técnico a la orden (POST). */
    public function actionAsignarTecnico(int $id): Response
    {
        $tecnicoId = (int) Yii::$app->request->post('tecnico_id', 0);
        $service   = new OrdenService();
        $orden     = $this->findModel($id);

        if ($tecnicoId > 0) {
            if ($service->asignarTecnico($orden, $tecnicoId)) {
                Yii::$app->session->setFlash('success', 'Técnico asignado y notificado correctamente.');
            } else {
                Yii::$app->session->setFlash('error', $service->getPrimerError());
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Desasigna un técnico de la orden (POST). */
    public function actionDesasignarTecnico(int $id): Response
    {
        $tecnicoId = (int) Yii::$app->request->post('tecnico_id', 0);
        $service   = new OrdenService();
        $orden     = $this->findModel($id);

        if ($tecnicoId > 0) {
            if ($service->desasignarTecnico($orden, $tecnicoId)) {
                Yii::$app->session->setFlash('success', 'Técnico desasignado correctamente.');
            } else {
                Yii::$app->session->setFlash('error', $service->getPrimerError());
            }
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Crea una orden pre-llenada desde una cita. */
    public function actionDesdeCita(int $citaId): Response|string
    {
        $service = new OrdenService();
        $orden   = $service->crearDesdeCita($citaId);

        if ($orden !== null) {
            Yii::$app->session->setFlash('success', "Orden {$orden->codigo} creada desde la cita #{$citaId}.");
            return $this->redirect(['view', 'id' => $orden->id]);
        }

        Yii::$app->session->setFlash('error', $service->getPrimerError() ?: 'Error al crear la orden desde la cita.');
        return $this->redirect(['/cita/view', 'id' => $citaId]);
    }

    private function findModel(int $id): OrdenServicio
    {
        $model = OrdenServicio::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Orden de servicio no encontrada.');
        }
        return $model;
    }
}
