<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\InventoryItem;
use app\models\InventoryItemImage;
use app\models\search\InventoryItemSearch;
use app\components\services\InventarioService;
use app\models\User;
use app\components\behaviors\AccessControlBehavior;

/**
 * InventarioController: gestion de inventario de insumos del taller.
 *
 * Incluye gestion de imagenes de productos (captura, subida, baja, predefinida).
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class InventarioController extends BaseController
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
                    'deactivate'        => ['post'],
                    'entrada'           => ['post'],
                    'salida'            => ['post'],
                    'ajuste'            => ['post'],
                    'upload-image'      => ['post'],
                    'deactivate-image'  => ['post'],
                    'set-default-image' => ['post'],
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'inventario',
                'actionMap' => [
                    'index'              => 'ver',
                    'view'               => 'ver',
                    'create'             => 'crear',
                    'update'             => 'editar',
                    'delete'             => 'eliminar',
                    'deactivate'         => 'eliminar',
                    'entrada'            => 'editar',
                    'salida'             => 'editar',
                    'ajuste'             => 'editar',
                    'upload-image'       => 'editar',
                    'deactivate-image'   => 'editar',
                    'set-default-image'  => 'editar',
                ],
            ],
        ];
    }

    private function requireAdminOrOperador(): void
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !in_array($identity->rol_id, [1, 2], true)) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para esta accion.'));
        }
    }

    /** Listado con KPI cards y filtros. */
    public function actionIndex(): string
    {
        $searchModel  = new InventoryItemSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $service = new InventarioService();
        $kpis    = $service->getKpis();

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'kpis'         => $kpis,
        ]);
    }

    /** Detalle de item con historial de movimientos. */
    public function actionView(int $id): string
    {
        $model = $this->findModel($id);
        $imagenes = InventoryItemImage::getActiveForItem($id);
        $imagenDefault = InventoryItemImage::getDefaultForItem($id);

        return $this->render('view', [
            'model'          => $model,
            'imagenes'       => $imagenes,
            'imagenDefault'  => $imagenDefault,
        ]);
    }

    /** Formulario de creacion. */
    public function actionCreate(): Response|string
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('InventoryItem', []);
            $item = $service->create($data);
            if ($item !== null) {
                Yii::$app->session->setFlash('success', 'Item creado exitosamente.');
                return $this->redirect(['view', 'id' => $item->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new InventoryItem();
        $model->sku = InventoryItem::generarSku();

        return $this->render('create', ['model' => $model]);
    }

    /** Formulario de edicion. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdminOrOperador();
        $item    = $this->findModel($id);
        $service = new InventarioService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('InventoryItem', []);
            $updated = $service->update($item, $data);
            if ($updated !== null) {
                Yii::$app->session->setFlash('success', 'Item actualizado exitosamente.');
                return $this->redirect(['view', 'id' => $item->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', ['model' => $item]);
    }

    /** Desactiva un item (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', 'Item desactivado exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Exporta el inventario activo como CSV. */
    public function actionExportCsv(): Response
    {
        $this->requireAdminOrOperador();
        $service = new InventarioService();
        $csv = $service->exportarCsv();

        return Yii::$app->response->sendContentAsFile(
            $csv,
            'inventario-' . date('Y-m-d-His') . '.csv',
            ['mimeType' => 'text/csv; charset=UTF-8']
        );
    }

    /** Registra entrada de stock (POST). */
    public function actionEntrada(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service  = new InventarioService();
        $post     = Yii::$app->request->post();
        $cantidad = (int) ($post['cantidad'] ?? 0);
        $ref      = (string) ($post['referencia'] ?? 'Entrada manual');
        $userId   = (int) Yii::$app->user->id;

        if ($cantidad <= 0) {
            Yii::$app->session->setFlash('error', 'La cantidad debe ser mayor a cero.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $service->registrarEntrada($id, $cantidad, $ref, $userId);
            Yii::$app->session->setFlash('success', "Entrada de {$cantidad} unidades registrada.");
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /** Registra ajuste de stock (POST). */
    public function actionAjuste(int $id): Response
    {
        $this->requireAdminOrOperador();
        $service  = new InventarioService();
        $post     = Yii::$app->request->post();
        $cantidad = (int) ($post['cantidad_nueva'] ?? -1);
        $motivo   = (string) ($post['motivo'] ?? 'Ajuste manual');
        $userId   = (int) Yii::$app->user->id;

        if ($cantidad < 0) {
            Yii::$app->session->setFlash('error', 'La cantidad no puede ser negativa.');
            return $this->redirect(['view', 'id' => $id]);
        }

        try {
            $service->registrarAjuste($id, $cantidad, $motivo, $userId);
            Yii::$app->session->setFlash('success', "Stock ajustado a {$cantidad} unidades.");
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    // ── Imagenes de producto ──────────────────────────────────────────

    /**
     * Sube una o mas imagenes para un item de inventario.
     * Soporta subida desde archivo o captura de camara (base64).
     */
    public function actionUploadImage(int $id): Response
    {
        $this->requireAdminOrOperador();
        $item = $this->findModel($id);
        $request = Yii::$app->request;

        // Soporte para imagen base64 desde camara
        $base64Image = $request->post('base64_image');
        if ($base64Image) {
            return $this->handleBase64Upload($item, $base64Image);
        }

        // Soporte para subida de archivo normal
        $files = UploadedFile::getInstancesByName('image_files');
        if (empty($files)) {
            Yii::$app->session->setFlash('error', 'No se seleccionaron imagenes.');
            return $this->redirect(['view', 'id' => $id]);
        }

        $uploaded = 0;
        $isFirstImage = (count($item->imagenes) === 0);

        foreach ($files as $file) {
            $imagen = new InventoryItemImage();
            $imagen->item_id = $id;
            $imagen->imageFile = $file;
            $imagen->is_default = ($isFirstImage && $uploaded === 0) ? 1 : 0;

            if ($imagen->upload() && $imagen->save()) {
                $uploaded++;
            }
        }

        if ($uploaded > 0) {
            Yii::$app->session->setFlash('success', "{$uploaded} imagen(es) subida(s) exitosamente.");
        } else {
            Yii::$app->session->setFlash('error', 'Error al subir las imagenes.');
        }

        return $this->redirect(['view', 'id' => $id]);
    }

    /**
     * Procesa una imagen capturada desde camara (base64).
     */
    private function handleBase64Upload(InventoryItem $item, string $base64Data): Response
    {
        // Decodificar base64
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $data = base64_decode($data);
            $ext = strtolower($type[1]);
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
        } else {
            Yii::$app->session->setFlash('error', 'Formato de imagen invalido.');
            return $this->redirect(['view', 'id' => $item->id]);
        }

        $dir = InventoryItemImage::getUploadDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $filename = 'item_' . $item->id . '_' . time() . '_' . Yii::$app->security->generateRandomString(6) . '.' . $ext;
        $fullPath = $dir . $filename;

        if (file_put_contents($fullPath, $data)) {
            $imagen = new InventoryItemImage();
            $imagen->item_id = $item->id;
            $imagen->filename = 'camara_' . date('Y-m-d_His') . '.' . $ext;
            $imagen->filepath = $filename;
            $imagen->is_default = (count($item->imagenes) === 0) ? 1 : 0;

            if ($imagen->save()) {
                Yii::$app->session->setFlash('success', 'Imagen capturada y guardada exitosamente.');
            } else {
                Yii::$app->session->setFlash('error', 'Error al guardar la imagen en la base de datos.');
            }
        } else {
            Yii::$app->session->setFlash('error', 'Error al guardar el archivo de imagen.');
        }

        return $this->redirect(['view', 'id' => $item->id]);
    }

    /**
     * Da de baja una imagen de producto (soft delete).
     */
    public function actionDeactivateImage(int $imageId): Response
    {
        $this->requireAdminOrOperador();
        $imagen = InventoryItemImage::findOne($imageId);

        if ($imagen === null) {
            throw new NotFoundHttpException('Imagen no encontrada.');
        }

        $itemId = $imagen->item_id;

        if ($imagen->deactivate()) {
            // Si era la predefinida, asignar la primera activa como nueva predefinida
            if ($imagen->is_default) {
                $primera = InventoryItemImage::getActiveForItem($itemId);
                if (!empty($primera)) {
                    $primera[0]->setAsDefault();
                }
            }
            Yii::$app->session->setFlash('success', 'Imagen dada de baja exitosamente.');
        } else {
            Yii::$app->session->setFlash('error', 'Error al dar de baja la imagen.');
        }

        return $this->redirect(['view', 'id' => $itemId]);
    }

    /**
     * Marca una imagen como predefinida.
     */
    public function actionSetDefaultImage(int $imageId): Response
    {
        $this->requireAdminOrOperador();
        $imagen = InventoryItemImage::findOne($imageId);

        if ($imagen === null) {
            throw new NotFoundHttpException('Imagen no encontrada.');
        }

        $itemId = $imagen->item_id;
        $imagen->setAsDefault();
        Yii::$app->session->setFlash('success', 'Imagen marcada como predefinida.');

        return $this->redirect(['view', 'id' => $itemId]);
    }

    private function findModel(int $id): InventoryItem
    {
        $model = InventoryItem::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('Item de inventario no encontrado.');
        }
        return $model;
    }
}