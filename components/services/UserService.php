<?php
declare(strict_types=1);
namespace app\components\services;

use Yii;
use app\models\User;

/**
 * UserService: gestión de usuarios del sistema.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class UserService extends BaseService
{
    protected string $logCategoria = 'app.user';

    /**
     * Crea un nuevo usuario.
     *
     * @param array $data Datos del usuario (nombre, apellido, username, email, password, rol_id).
     * @return User|null El usuario creado o null en caso de error.
     */
    public function create(array $data): ?User
    {
        return $this->executeInTransaction(function () use ($data): User {
            $password = $this->extractPassword($data);
            if ($password === '') {
                throw new ServiceException('La contraseña es obligatoria.');
            }
            if (strlen($password) < 10) {
                throw new ServiceException('Mínimo 10 caracteres.');
            }

            $user = new User();
            $user->username  = $data['username'] ?? '';
            $user->email     = strtolower(trim($data['email'] ?? ''));
            $user->nombre    = $data['nombre'] ?? null;
            $user->apellido  = $data['apellido'] ?? null;
            $user->rol_id    = (int) ($data['rol_id'] ?? 1);
            $user->activo    = (int) ($data['activo'] ?? 1);

            $user->setPassword($password);
            $user->generateAuthKey();

            if (!$user->validate()) {
                throw new ServiceException(implode('; ', $user->getFirstErrors()));
            }
            $this->asegurar($user->save(false), 'Error al guardar el usuario.');
            $this->log("Usuario creado: #{$user->id} ({$user->email})");
            return $user;
        });
    }

    /**
     * Actualiza un usuario existente.
     *
     * @param User  $user Instancia del usuario a actualizar.
     * @param array $data Datos a actualizar.
     * @return User|null El usuario actualizado o null en caso de error.
     */
    public function update(User $user, array $data): ?User
    {
        return $this->executeInTransaction(function () use ($user, $data): User {
            if (isset($data['nombre']))   $user->nombre   = $data['nombre'];
            if (isset($data['apellido'])) $user->apellido = $data['apellido'];
            if (isset($data['email']))    $user->email    = strtolower(trim($data['email']));
            if (isset($data['username'])) $user->username = $data['username'];
            if (isset($data['rol_id']))   $user->rol_id   = (int) $data['rol_id'];
            if (isset($data['activo']))   $user->activo   = (int) $data['activo'];

            $password = $this->extractPassword($data);
            if ($password !== '') {
                if (strlen($password) < 10) {
                    throw new ServiceException('Mínimo 10 caracteres.');
                }
                $user->setPassword($password);
            }

            if (!$user->validate()) {
                throw new ServiceException(implode('; ', $user->getFirstErrors()));
            }
            $this->asegurar($user->save(false), 'Error al actualizar el usuario.');
            $this->log("Usuario actualizado: #{$user->id}");
            return $user;
        });
    }

    /**
     * Desactiva un usuario (soft delete: activo=0).
     * No permite que el usuario se desactive a sí mismo.
     *
     * @param int $id ID del usuario a desactivar.
     * @return bool True si se desactivó exitosamente.
     */
    public function deactivate(int $id): bool
    {
        $currentUserId = Yii::$app->user->id;
        if ((int) $currentUserId === $id) {
            $this->agregarError(Yii::t('app', 'No puede desactivar su propio usuario.'));
            return false;
        }

        $user = User::findOne($id);
        if ($user === null) {
            $this->agregarError(Yii::t('app', 'Usuario no encontrado.'));
            return false;
        }

        $user->activo     = 0;
        $user->deleted_at = time();
        if (!$user->save(false, ['activo', 'deleted_at', 'updated_at'])) {
            $this->agregarError(Yii::t('app', 'Error al desactivar el usuario.'));
            return false;
        }

        $this->log("Usuario desactivado: #{$id}");
        return true;
    }

    /**
     * Cambia la contraseña verificando la contraseña actual.
     *
     * @param User   $user       Instancia del usuario.
     * @param string $currentPwd Contraseña actual.
     * @param string $newPwd     Nueva contraseña.
     * @return bool True si el cambio fue exitoso.
     */
    public function changePassword(User $user, string $currentPwd, string $newPwd): bool
    {
        if (!$user->validatePassword($currentPwd)) {
            $this->agregarError(Yii::t('app', 'La contraseña actual es incorrecta.'));
            return false;
        }

        if (strlen($newPwd) < 10) {
            $this->agregarError(Yii::t('app', 'La nueva contraseña debe tener al menos 10 caracteres.'));
            return false;
        }

        $user->setPassword($newPwd);
        if (!$user->save(false, ['password_hash', 'updated_at'])) {
            $this->agregarError(Yii::t('app', 'Error al guardar la nueva contraseña.'));
            return false;
        }

        $this->log("Contraseña cambiada para usuario: #{$user->id}");
        return true;
    }

    private function extractPassword(array $data): string
    {
        $raw = $data['password'] ?? $data['password_hash'] ?? '';
        return trim((string) $raw);
    }
}
