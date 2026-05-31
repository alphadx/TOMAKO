<?php
declare(strict_types=1);
namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\data\ActiveDataProvider;
use app\models\Rol;
use app\models\Permiso;
use app\components\services\RolService;

/**
 * RolController: CRUD de roles del sistema. Solo accesible por Administradores.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class RolController extends BaseController
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
        ];
    }

    private function requireAdmin(): void
    {
        /** @var \app\models\User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !$identity->isAdmin()) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para acceder a esta sección.'));
        }
    }

    /** Listado de roles. */
    public function actionIndex(): string
    {
        $this->requireAdmin();

        $dataProvider = new ActiveDataProvider([
            'query' => Rol::find()->orderBy('id'),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /** Detalle de un rol con sus permisos. */
    public function actionView(int $id): string
    {
        $this->requireAdmin();
        $rol      = $this->findModel($id);
        $permisos  = Permiso::getPermisosAgrupadosPorModulo();
        $service   = new RolService();
        $asignados = $service->getPermisosForRol($id);
        return $this->render('view', [
            'model'     => $rol,
            'permisos'  => $permisos,
            'asignados' => $asignados,
        ]);
    }

    /** Crear rol. */
    public function actionCreate(): Response|string
    {
        $this->requireAdmin();
        $service = new RolService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('Rol', []);
            $rol  = $service->create($data);
            if ($rol !== null) {
                $permisoIds = Yii::$app->request->post('permisos', []);
                if (!empty($permisoIds)) {
                    $service->assignPermissions($rol->id, array_map('intval', $permisoIds));
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'Rol creado exitosamente.'));
                return $this->redirect(['view', 'id' => $rol->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $permisos = Permiso::getPermisosAgrupadosPorModulo();
        return $this->render('create', [
            'model'    => new Rol(),
            'permisos' => $permisos,
        ]);
    }

    /** Editar rol + asignación de permisos. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdmin();
        $rol     = $this->findModel($id);
        $service = new RolService();

        if (Yii::$app->request->isPost) {
            $data    = Yii::$app->request->post('Rol', []);
            $updated = $service->update($rol, $data);
            if ($updated !== null) {
                $permisoIds = Yii::$app->request->post('permisos', []);
                $service->assignPermissions($rol->id, array_map('intval', $permisoIds));
                Yii::$app->session->setFlash('success', Yii::t('app', 'Rol actualizado exitosamente.'));
                return $this->redirect(['view', 'id' => $rol->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $permisos  = Permiso::getPermisosAgrupadosPorModulo();
        $asignados = $service->getPermisosForRol($id);
        return $this->render('update', [
            'model'     => $rol,
            'permisos'  => $permisos,
            'asignados' => $asignados,
        ]);
    }

    private function findModel(int $id): Rol
    {
        $model = Rol::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Rol no encontrado.'));
        }
        return $model;
    }
}
