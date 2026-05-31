<?php

namespace app\controllers;

use Yii;
use app\models\OrdenCompra;
use app\models\OrdenCompraItem;
use app\models\Proveedor;
use app\models\InventoryItem;
use app\models\search\OrdenCompraSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\web\Response;

/**
 * OrdenCompraController implementa el CRUD para la gestión de órdenes de compra.
 */
class OrdenCompraController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'actions' => ['index', 'view'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['create', 'update', 'delete', 'enviar', 'recibir', 'cancelar', 'agregar-item', 'eliminar-item'],
                        'allow' => true,
                        'roles' => ['admin', 'gerente', 'jefe_taller'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'enviar' => ['POST'],
                    'cancelar' => ['POST'],
                    'recibir' => ['POST'],
                    'agregar-item' => ['POST'],
                    'eliminar-item' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all OrdenCompra models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new OrdenCompraSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        // KPIs para el dashboard
        $totalOrdenes = OrdenCompra::find()->count();
        $ordenesPendientes = OrdenCompra::find()->where(['in', 'estado', [OrdenCompra::ESTADO_BORRADOR, OrdenCompra::ESTADO_ENVIADA]])->count();
        $ordenesRecibidas = OrdenCompra::find()->where(['estado' => OrdenCompra::ESTADO_RECIBIDA_COMPLETO])->count();
        $montoTotalMes = OrdenCompra::find()
            ->where(['>=', 'fecha_emision', date('Y-m-01')])
            ->sum('total_monto') ?? 0;

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'totalOrdenes' => $totalOrdenes,
            'ordenesPendientes' => $ordenesPendientes,
            'ordenesRecibidas' => $ordenesRecibidas,
            'montoTotalMes' => $montoTotalMes,
        ]);
    }

    /**
     * Displays a single OrdenCompra model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        
        // Formulario para agregar items
        $itemModel = new OrdenCompraItem();
        $itemModel->orden_compra_id = $id;
        
        // Lista de items para dropdown (inventory)
        $inventoryItems = InventoryItem::find()->active()->all();
        $listaInventario = [];
        foreach ($inventoryItems as $item) {
            $listaInventario[$item->id] = "{$item->nombre} ({$item->sku})";
        }

        return $this->render('view', [
            'model' => $model,
            'itemModel' => $itemModel,
            'listaInventario' => $listaInventario,
        ]);
    }

    /**
     * Creates a new OrdenCompra model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new OrdenCompra();
        $model->numero_orden = OrdenCompra::generarNumeroOrden();
        $model->fecha_emision = date('Y-m-d');
        $model->estado = OrdenCompra::ESTADO_BORRADOR;

        if ($this->request->isPost) {
            if ($model->load($this->request->post())) {
                $model->created_by = Yii::$app->user->id;
                if ($model->save()) {
                    Yii::$app->session->setFlash('success', 'Orden de compra creada exitosamente.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            }
        }

        // Lista de proveedores activos
        $proveedores = Proveedor::getListaParaDropdown();

        return $this->render('create', [
            'model' => $model,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Updates an existing OrdenCompra model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        
        // No permitir edición si ya está recibida completa o cancelada
        if (in_array($model->estado, [OrdenCompra::ESTADO_RECIBIDA_COMPLETO, OrdenCompra::ESTADO_CANCELADA])) {
            Yii::$app->session->setFlash('error', 'No se puede modificar una orden recibida o cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        if ($this->request->isPost && $model->load($this->request->post())) {
            $model->updated_by = Yii::$app->user->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Orden de compra actualizada exitosamente.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $proveedores = Proveedor::getListaParaDropdown();

        return $this->render('update', [
            'model' => $model,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Deletes an existing OrdenCompra model.
     * Only allowed for draft orders.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
        
        // Solo permitir eliminación de borradores
        if ($model->estado !== OrdenCompra::ESTADO_BORRADOR) {
            Yii::$app->session->setFlash('error', 'Solo se pueden eliminar órdenes en estado borrador.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        $model->delete();
        Yii::$app->session->setFlash('success', 'Orden de compra eliminada exitosamente.');

        return $this->redirect(['index']);
    }

    /**
     * Envía una orden de compra al proveedor (cambia estado a 'enviada')
     * @param integer $id
     * @return mixed
     */
    public function actionEnviar($id)
    {
        $model = $this->findModel($id);
        
        if ($model->estado !== OrdenCompra::ESTADO_BORRADOR) {
            Yii::$app->session->setFlash('error', 'Solo se pueden enviar órdenes en estado borrador.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        // Calcular total antes de enviar
        $model->calcularTotal();
        $model->estado = OrdenCompra::ESTADO_ENVIADA;
        $model->updated_by = Yii::$app->user->id;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Orden de compra enviada al proveedor.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al enviar la orden de compra.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Cancela una orden de compra
     * @param integer $id
     * @return mixed
     */
    public function actionCancelar($id)
    {
        $model = $this->findModel($id);
        
        if (in_array($model->estado, [OrdenCompra::ESTADO_RECIBIDA_COMPLETO, OrdenCompra::ESTADO_CANCELADA])) {
            Yii::$app->session->setFlash('error', 'No se puede cancelar una orden recibida o ya cancelada.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        $model->estado = OrdenCompra::ESTADO_CANCELADA;
        $model->updated_by = Yii::$app->user->id;
        
        if ($model->save(false)) {
            Yii::$app->session->setFlash('success', 'Orden de compra cancelada.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al cancelar la orden de compra.');
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Agrega un item a la orden de compra
     * @param integer $id ID de la orden de compra
     * @return mixed
     */
    public function actionAgregarItem($id)
    {
        $model = $this->findModel($id);
        $item = new OrdenCompraItem();
        
        // No permitir agregar items si la orden ya fue enviada o recibida
        if (!in_array($model->estado, [OrdenCompra::ESTADO_BORRADOR])) {
            Yii::$app->session->setFlash('error', 'No se pueden agregar items a una orden enviada o recibida.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        if ($this->request->isPost && $item->load($this->request->post())) {
            $item->orden_compra_id = $id;
            if ($item->save()) {
                // Recalcular total de la orden
                $model->calcularTotal();
                $model->save(false);
                
                Yii::$app->session->setFlash('success', 'Item agregado exitosamente.');
            } else {
                Yii::$app->session->setFlash('error', 'Error al agregar el item.');
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Elimina un item de la orden de compra
     * @param integer $id ID del item a eliminar
     * @return mixed
     */
    public function actionEliminarItem($id)
    {
        $item = OrdenCompraItem::findOne($id);
        
        if ($item) {
            $ordenId = $item->orden_compra_id;
            $model = $this->findModel($ordenId);
            
            // No permitir eliminar items si la orden ya fue enviada o recibida
            if (!in_array($model->estado, [OrdenCompra::ESTADO_BORRADOR])) {
                Yii::$app->session->setFlash('error', 'No se pueden eliminar items de una orden enviada o recibida.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
            
            $item->delete();
            
            // Recalcular total de la orden
            $model->calcularTotal();
            $model->save(false);
            
            Yii::$app->session->setFlash('success', 'Item eliminado exitosamente.');
        }

        return $this->redirect(['view', 'id' => $ordenId]);
    }

    /**
     * Acción para recibir productos de una orden de compra
     * @param integer $id ID de la orden de compra
     * @return mixed
     */
    public function actionRecibir($id)
    {
        $model = $this->findModel($id);
        
        if (!in_array($model->estado, [OrdenCompra::ESTADO_ENVIADA, OrdenCompra::ESTADO_RECIBIDA_PARCIAL])) {
            Yii::$app->session->setFlash('error', 'Solo se pueden recibir órdenes enviadas o parcialmente recibidas.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        
        // Procesar recepción de items
        if ($this->request->isPost) {
            $itemsData = Yii::$app->request->post('OrdenCompraItem');
            $cantidadRecibida = Yii::$app->request->post('cantidad_recibida');
            
            $transaccion = Yii::$app->db->beginTransaction();
            try {
                foreach ($model->items as $item) {
                    if (isset($cantidadRecibida[$item->id])) {
                        $cantidad = (int)$cantidadRecibida[$item->id];
                        if ($cantidad > 0) {
                            $item->cantidad_recibida += $cantidad;
                            // No permitir recibir más de lo solicitado
                            if ($item->cantidad_recibida > $item->cantidad) {
                                $item->cantidad_recibida = $item->cantidad;
                            }
                            $item->save(false);
                            
                            // FASE 3: Actualizar inventario si hay relación con inventory_item_id
                            if ($item->inventory_item_id && $cantidad > 0) {
                                $inventoryItem = InventoryItem::findOne($item->inventory_item_id);
                                if ($inventoryItem) {
                                    $stockAnterior = $inventoryItem->cantidad;
                                    $inventoryItem->cantidad += $cantidad;
                                    $inventoryItem->save(false);
                                    
                                    // Registrar movimiento de inventario (entrada por compra)
                                    $movimiento = new \app\models\InventoryMovement();
                                    $movimiento->item_id = $item->inventory_item_id;
                                    $movimiento->tipo = 'entrada';
                                    $movimiento->cantidad_delta = $cantidad;
                                    $movimiento->cantidad_anterior = $stockAnterior;
                                    $movimiento->cantidad_nueva = $inventoryItem->cantidad;
                                    $movimiento->referencia = "OC-{$model->numero_orden}";
                                    $movimiento->usuario_id = Yii::$app->user->id;
                                    $movimiento->save(false);
                                }
                            }
                        }
                    }
                }
                
                // Actualizar estado de la orden según recepción
                $model->actualizarEstadoPorRecepcion();
                if ($model->estado === OrdenCompra::ESTADO_RECIBIDA_COMPLETO) {
                    $model->fecha_entrega_real = date('Y-m-d');
                    
                    // FASE 4: Crear evaluación automática del proveedor basada en puntualidad
                    $evaluacion = \app\models\EvaluacionProveedor::crearEvaluacionDesdeOrden($model);
                    $evaluacion->evaluado_por = Yii::$app->user->id;
                    $evaluacion->save(false);
                }
                $model->updated_by = Yii::$app->user->id;
                $model->save(false);
                
                $transaccion->commit();
                Yii::$app->session->setFlash('success', 'Recepción registrada exitosamente. Inventario actualizado.');
            } catch (\Exception $e) {
                $transaccion->rollBack();
                Yii::$app->session->setFlash('error', 'Error al registrar la recepción: ' . $e->getMessage());
            }
        }

        return $this->redirect(['view', 'id' => $model->id]);
    }

    /**
     * Finds the OrdenCompra model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return OrdenCompra the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = OrdenCompra::findOne(['id' => $id])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('La orden de compra solicitada no existe.');
    }
}
