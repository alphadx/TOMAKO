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
use app\models\User;
use app\models\Rol;
use app\models\search\UserSearch;
use app\components\services\UserService;
use app\components\behaviors\AccessControlBehavior;

/**
 * UsuarioController: CRUD de usuarios del sistema.
 * Acceso restringido a usuarios autenticados. Las acciones de gestión requieren rol Administrador.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class UsuarioController extends BaseController
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
                ],
            ],
            'granularAccess' => [
                'class' => AccessControlBehavior::class,
                'permisoBase' => 'usuario',
                'actionMap' => [
                    'index' => 'ver',
                    'view' => 'ver',
                    'create' => 'crear',
                    'update' => 'editar',
                    'delete' => 'eliminar',
                    'deactivate' => 'eliminar',
                ],
            ],
        ];
    }

    /** Verifica que el usuario actual sea administrador. */
    private function requireAdmin(): void
    {
        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        if (!$identity || !$identity->isAdmin()) {
            throw new ForbiddenHttpException(Yii::t('app', 'No tiene permisos para acceder a esta sección.'));
        }
    }

    /** Listado paginado de usuarios con búsqueda. */
    public function actionIndex(): string
    {
        $this->requireAdmin();
        $searchModel  = new UserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /** Detalle de un usuario. */
    public function actionView(int $id): string
    {
        $this->requireAdmin();
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /** Formulario de creación de usuario. */
    public function actionCreate(): Response|string
    {
        $this->requireAdmin();
        $service = new UserService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('User', []);
            $user = $service->create($data);

            if ($user !== null) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Usuario creado exitosamente.'));
                return $this->redirect(['view', 'id' => $user->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        $model = new User();
        return $this->render('create', [
            'model' => $model,
            'roles' => Rol::getRolesArray(),
        ]);
    }

    /** Formulario de edición de usuario. */
    public function actionUpdate(int $id): Response|string
    {
        $this->requireAdmin();
        $user    = $this->findModel($id);
        $service = new UserService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('User', []);
            $updated = $service->update($user, $data);

            if ($updated !== null) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Usuario actualizado exitosamente.'));
                return $this->redirect(['view', 'id' => $user->id]);
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('update', [
            'model' => $user,
            'roles' => Rol::getRolesArray(),
        ]);
    }

    /** Desactiva un usuario (POST). */
    public function actionDeactivate(int $id): Response
    {
        $this->requireAdmin();
        $service = new UserService();

        if ($service->deactivate($id)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Usuario desactivado exitosamente.'));
        } else {
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->redirect(['index']);
    }

    /** Perfil del usuario autenticado. */
    public function actionProfile(): Response|string
    {
        /** @var User $user */
        $user    = Yii::$app->user->identity;
        $service = new UserService();

        if (Yii::$app->request->isPost) {
            $data = Yii::$app->request->post('User', []);
            // Solo permitir actualizar nombre, apellido, email
            $allowed = array_intersect_key($data, array_flip(['nombre', 'apellido', 'email']));
            $updated = $service->update($user, $allowed);

            if ($updated !== null) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Perfil actualizado exitosamente.'));
                return $this->refresh();
            }
            Yii::$app->session->setFlash('error', $service->getPrimerError());
        }

        return $this->render('profile', ['model' => $user]);
    }

    /** Formulario de cambio de contraseña. */
    public function actionChangePassword(): Response|string
    {
        /** @var User $user */
        $user    = Yii::$app->user->identity;
        $service = new UserService();
        $error   = null;

        if (Yii::$app->request->isPost) {
            $post       = Yii::$app->request->post();
            $currentPwd = $post['current_password'] ?? '';
            $newPwd     = $post['new_password'] ?? '';
            $confirmPwd = $post['confirm_password'] ?? '';

            if ($newPwd !== $confirmPwd) {
                $error = Yii::t('app', 'Las contraseñas no coinciden.');
            } elseif ($service->changePassword($user, $currentPwd, $newPwd)) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Contraseña cambiada exitosamente.'));
                return $this->redirect(['profile']);
            } else {
                $error = $service->getPrimerError();
            }
        }

        return $this->render('change-password', ['error' => $error]);
    }

    private function findModel(int $id): User
    {
        $model = User::findOne(['id' => $id, 'deleted_at' => null]);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Usuario no encontrado.'));
        }
        return $model;
    }
}
