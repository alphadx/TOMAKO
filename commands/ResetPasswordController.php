<?php

declare(strict_types=1);

namespace app\commands;

use app\models\User;
use Throwable;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Comandos para reseteo de contrasenas de usuarios.
 */
class ResetPasswordController extends Controller
{
    /**
     * Resetea la contrasena del usuario indicado por ID.
     *
     * Uso:
     * php yii reset-password/index 1
     */
    public function actionIndex(int $userId): int
    {
        $user = User::findOne(['id' => $userId, 'deleted_at' => null]);
        if ($user === null) {
            $this->stderr("Usuario no encontrado para id={$userId}.\n");
            return ExitCode::DATAERR;
        }

        try {
            // 10 caracteres alfanumericos para compartir temporalmente al usuario.
            $newPassword = Yii::$app->security->generateRandomString(10);

            $user->setPassword($newPassword);
            $user->removePasswordResetToken();

            if (!$user->save(false, ['password_hash', 'password_reset_token', 'updated_at'])) {
                $this->stderr("No se pudo actualizar la contrasena para id={$userId}.\n");
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("Correo: {$user->email}\n");
            $this->stdout("Nueva contrasena: {$newPassword}\n");
            return ExitCode::OK;
        } catch (Throwable $e) {
            $this->stderr("Error al resetear contrasena: {$e->getMessage()}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }
    }
}
