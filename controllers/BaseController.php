<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;
use yii\web\Controller;

/**
 * Controlador base de la app.
 *
 * Captura errores de base de datos asociados a tablas faltantes para evitar
 * respuestas 500 crudas y devolver una salida operativa al usuario.
 */
abstract class BaseController extends Controller
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        if (Yii::$app->user->isGuest) {
            return true;
        }

        $session = Yii::$app->session;
        $timeout = (int) (Yii::$app->user->authTimeout ?? 1800);
        $lastActivity = (int) $session->get('__last_activity_ts', time());

        if ((time() - $lastActivity) > $timeout) {
            Yii::$app->user->logout();
            $session->remove('__last_activity_ts');
            $this->redirect(['/login', 'timeout' => 1]);
            return false;
        }

        $session->set('__last_activity_ts', time());
        return true;
    }

    public function runAction($id, $params = []): mixed
    {
        try {
            return parent::runAction($id, $params);
        } catch (InvalidConfigException|DbException $e) {
            if (!$this->isMissingTableError($e)) {
                throw $e;
            }

            Yii::error(
                sprintf(
                    'Error de esquema en %s::%s. Ruta: %s. Mensaje: %s',
                    static::class,
                    (string) $id,
                    Yii::$app->request->url,
                    $e->getMessage()
                ),
                'app.db'
            );

            $mensaje = 'No se pudo cargar la informacion porque la base de datos no tiene el esquema esperado (tablas faltantes). Verifique migraciones pendientes y ejecute: docker compose exec app php yii migrate --interactive=0.';

            if (Yii::$app->request->isAjax) {
                Yii::$app->response->statusCode = 503;
                return $this->asJson([
                    'ok' => false,
                    'message' => $mensaje,
                ]);
            }

            Yii::$app->session->setFlash('error', $mensaje);
            return $this->redirect(['/site/index']);
        }
    }

    private function isMissingTableError(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'table does not exist')
            || str_contains($message, 'base table or view not found')
            || str_contains($message, 'sqlstate[42s02]');
    }
}
