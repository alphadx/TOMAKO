<?php
declare(strict_types=1);
namespace app\components\services;

use Yii;
use app\models\User;
use app\models\LoginAttempt;

/**
 * AuthService: gestión de autenticación y sesiones.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class AuthService extends BaseService
{
    protected string $logCategoria = 'app.auth';

    /** Máximo de intentos fallidos permitidos en la ventana de tiempo */
    private int $maxIntentos = 5;
    /** Ventana de tiempo en minutos para el bloqueo por intentos fallidos */
    private int $ventanaMinutos = 15;

    /**
     * Autentica al usuario con email y contraseña.
     *
     * @param string $email     Correo electrónico (se normaliza a minúsculas).
     * @param string $password  Contraseña en texto plano.
     * @param bool   $rememberMe Si true, mantiene la sesión por 30 días.
     * @return bool True si el login fue exitoso.
     */
    public function login(string $email, string $password, bool $rememberMe = false): bool
    {
        $email = strtolower(trim($email));
        $ip    = Yii::$app->request->userIP ?? '0.0.0.0';

        $bloqueoInfo = $this->getBloqueoInfoPorIp($ip);
        if ($bloqueoInfo['isBlocked']) {
            $this->agregarError(Yii::t('app', 'Bloqueo temporal activo. Tiempo restante: {seconds} segundos.', ['seconds' => $bloqueoInfo['remainingSeconds']]));
            $this->registrarAudit('LOGIN_BLOQUEADO', 0, ['ip' => $ip, 'email' => $email]);
            return false;
        }

        $user = User::findByEmail($email);

        if ($user === null || !$user->validatePassword($password)) {
            LoginAttempt::registrar($ip, $email, false);
            $this->agregarError(Yii::t('app', 'Credenciales inválidas'));
            $this->registrarAudit('LOGIN_FALLIDO', 0, ['ip' => $ip, 'email' => $email]);
            return false;
        }

        if ($user->activo !== 1) {
            LoginAttempt::registrar($ip, $email, false);
            $this->agregarError(Yii::t('app', 'Credenciales inválidas'));
            $this->registrarAudit('LOGIN_INACTIVO', $user->id, ['ip' => $ip, 'email' => $email]);
            return false;
        }

        // Login exitoso
        $duration = $rememberMe ? 3600 * 24 * 7 : 0;
        Yii::$app->user->login($user, $duration);
        Yii::$app->session->regenerateID(true);

        // Registrar intento exitoso
        LoginAttempt::registrar($ip, $email, true);

        // Actualizar último login
        $user->ultimo_login = time();
        $user->save(false, ['ultimo_login']);

        $this->registrarAudit('LOGIN_EXITOSO', $user->id, ['ip' => $ip, 'email' => $email]);
        $this->log("Login exitoso: usuario #{$user->id} ({$email}) desde {$ip}");

        return true;
    }

    /**
     * Cierra la sesión del usuario autenticado.
     */
    public function logout(): void
    {
        if (!Yii::$app->user->isGuest) {
            $userId = Yii::$app->user->id;
            $this->registrarAudit('LOGOUT', (int) $userId, []);
            
            // Eliminar cookie de identidad si existe
            $identityCookie = Yii::$app->user->identityCookie;
            if ($identityCookie !== null) {
                $cookieName = $identityCookie['name'] ?? '_identity-tallersmart';
                setcookie($cookieName, '', time() - 3600, '/');
            }
            
            Yii::$app->user->logout(true); // true fuerza el cierre completo
            $this->log("Logout: usuario #{$userId}");
        }
    }

    /**
     * Verifica si la IP está bloqueada por demasiados intentos fallidos.
     */
    public function isRateLimited(string $ip): bool
    {
        try {
            return $this->getBloqueoInfoPorIp($ip)['isBlocked'];
        } catch (\Throwable $e) {
            // Tabla login_attempt no disponible (migraciones pendientes); no bloquear
            Yii::warning('Rate limiting no disponible: ' . $e->getMessage(), 'app.auth');
            return false;
        }
    }

    /**
     * Obtiene el estado de bloqueo por IP y segundos restantes.
     *
     * @return array{isBlocked: bool, remainingSeconds: int}
     */
    public function getBloqueoInfoPorIp(string $ip): array
    {
        try {
            $desde = time() - ($this->ventanaMinutos * 60);
            $fallidos = LoginAttempt::find()
                ->where(['ip' => $ip, 'exitoso' => 0])
                ->andWhere(['>=', 'created_at', $desde])
                ->orderBy(['created_at' => SORT_ASC])
                ->all();

            if (count($fallidos) < $this->maxIntentos) {
                return ['isBlocked' => false, 'remainingSeconds' => 0];
            }

            $pivotIndex = count($fallidos) - $this->maxIntentos;
            $pivotTs = (int) ($fallidos[$pivotIndex]->created_at ?? time());
            $remaining = max(0, ($pivotTs + ($this->ventanaMinutos * 60)) - time());

            return [
                'isBlocked' => $remaining > 0,
                'remainingSeconds' => $remaining,
            ];
        } catch (\Throwable $e) {
            Yii::warning('No se pudo calcular bloqueo por IP: ' . $e->getMessage(), 'app.auth');
            return ['isBlocked' => false, 'remainingSeconds' => 0];
        }
    }

    /**
     * Restablece la contraseña mediante token.
     *
     * @param string $token      Token de reseteo.
     * @param string $newPassword Nueva contraseña.
     * @return bool True si se restableció correctamente.
     */
    public function resetPasswordByToken(string $token, string $newPassword): bool
    {
        $user = User::findByPasswordResetToken($token);
        if ($user === null) {
            $this->agregarError(Yii::t('app', 'Token inválido o expirado.'));
            return false;
        }

        $user->setPassword($newPassword);
        $user->removePasswordResetToken();

        if (!$user->save(false, ['password_hash', 'password_reset_token', 'updated_at'])) {
            $this->agregarError(Yii::t('app', 'Error al guardar la nueva contraseña.'));
            return false;
        }

        $this->registrarAudit('PASSWORD_RESET', $user->id, []);
        return true;
    }

    /**
     * Inserta un registro en audit_log.
     */
    private function registrarAudit(string $accion, int $usuarioId, array $datos): void
    {
        try {
            Yii::$app->db->createCommand()->insert('{{%audit_log}}', [
                'tabla'       => 'usuario',
                'registro_id' => $usuarioId,
                'accion'      => $accion,
                'usuario_id'  => $usuarioId ?: null,
                'ip'          => Yii::$app->request->userIP ?? null,
                'cambios'     => json_encode($datos, JSON_UNESCAPED_UNICODE),
                'created_at'  => time(),
            ])->execute();
        } catch (\Throwable $e) {
            Yii::error('AuthService: error en audit: ' . $e->getMessage(), $this->logCategoria);
        }
    }
}
