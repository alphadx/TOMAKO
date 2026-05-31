<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;
use app\components\services\DatabaseInitService;

/**
 * AdminController: gestión de administración del sistema.
 * Acceso restringido a perfil administrador.
 *
 * Acciones:
 * - database: visualizar y ejecutar inicialización de datos maestros.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class AdminController extends Controller
{
    /** @inheritdoc */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow'         => true,
                        'roles'         => ['@'],
                        'matchCallback' => function () {
                            $identity = \Yii::$app->user->identity;
                            return $identity instanceof \app\models\User && $identity->isAdmin();
                        },
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'database-init' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Muestra el estado de la base de datos e inicialización del sistema.
     * GET /admin/database
     */
    public function actionDatabase(): string
    {
        $servicio = new DatabaseInitService();
        $estado   = $servicio->obtenerEstado();

        return $this->render('database', [
            'estado'   => $estado,
            'servicio' => $servicio,
        ]);
    }

    /**
     * Ejecuta la inicialización de datos maestros.
     * POST /admin/database-init
     */
    public function actionDatabaseInit(): Response
    {
        $servicio  = new DatabaseInitService();
        $resultado = $servicio->inicializar();

        if ($resultado === null) {
            Yii::$app->session->setFlash('error',
                Yii::t('app', 'Error al inicializar el sistema') . ': ' . $servicio->getPrimerError()
            );
        } else {
            Yii::$app->session->setFlash('success',
                Yii::t('app', 'Sistema inicializado correctamente') .
                ' — Roles: ' . $resultado['roles'] .
                ', Idiomas: ' . $resultado['idiomas'] .
                ', Parámetros: ' . $resultado['parametros']
            );
        }

        return $this->redirect(['database']);
    }
}
