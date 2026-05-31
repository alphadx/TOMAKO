<?php
declare(strict_types=1);
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo de usuario. Mapea la tabla {{%usuario}}.
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string|null $auth_key
 * @property string|null $password_reset_token
 * @property int $rol_id
 * @property string|null $nombre
 * @property string|null $apellido
 * @property int $activo
 * @property int|null $ultimo_login
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property int|null $deleted_at
 * @property-read Rol $rol
 */
class User extends ActiveRecord implements IdentityInterface
{
    /** Escenario para cambio de contraseña */
    const SCENARIO_CHANGE_PASSWORD = 'change_password';

    public static function tableName(): string
    {
        return '{{%usuario}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    public function rules(): array
    {
        return [
            [['username', 'email', 'rol_id'], 'required'],
            [['rol_id', 'activo'], 'integer'],
            ['email', 'email'],
            ['email', 'string', 'max' => 150],
            ['username', 'string', 'max' => 60],
            ['nombre', 'string', 'max' => 100],
            ['apellido', 'string', 'max' => 100],
            ['activo', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'           => 'ID',
            'username'     => 'Usuario',
            'email'        => 'Correo',
            'rol_id'       => 'Rol',
            'nombre'       => 'Nombre',
            'apellido'     => 'Apellido',
            'activo'       => 'Activo',
            'ultimo_login' => 'Último Acceso',
            'created_at'   => 'Creado',
            'updated_at'   => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->email = strtolower(trim((string) $this->email));

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    // -------------------------------------------------------------------------
    // IdentityInterface
    // -------------------------------------------------------------------------

    public static function findIdentity($id): ?static
    {
        return static::findOne(['id' => $id, 'activo' => 1, 'deleted_at' => null]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        return null; // No se usa token de acceso en esta aplicación
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key !== null && $this->auth_key === $authKey;
    }

    // -------------------------------------------------------------------------
    // Métodos de negocio
    // -------------------------------------------------------------------------

    public static function findByEmail(string $email): ?static
    {
        return static::findOne(['email' => strtolower(trim($email)), 'deleted_at' => null]);
    }

    public static function findByUsername(string $username): ?static
    {
        return static::findOne(['username' => $username, 'deleted_at' => null]);
    }

    public function validatePassword(string $password): bool
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString(32);
    }

    public function generatePasswordResetToken(): void
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function removePasswordResetToken(): void
    {
        $this->password_reset_token = null;
    }

    public static function findByPasswordResetToken(string $token): ?static
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        return static::findOne(['password_reset_token' => $token, 'activo' => 1]);
    }

    public static function isPasswordResetTokenValid(string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        $parts = explode('_', $token);
        $timestamp = (int) end($parts);
        $expire = Yii::$app->params['passwordResetTokenExpire'] ?? 3600;
        return $timestamp + $expire >= time();
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->nombre, $this->apellido]);
        return $parts ? implode(' ', $parts) : $this->username;
    }

    public function isAdmin(): bool
    {
        return $this->rol_id === 1;
    }

    /**
     * Verifica si el usuario tiene un permiso específico.
     * 
     * @param string $permisoNombre Nombre del permiso (ej: 'orden.crear', 'cita.editar')
     * @return bool true si tiene el permiso, false en caso contrario
     */
    public function canAccess(string $permisoNombre): bool
    {
        // El administrador tiene todos los permisos
        if ($this->isAdmin()) {
            return true;
        }

        $cacheKey = "user_perms_{$this->id}";
        $permisos = Yii::$app->cache->get($cacheKey);

        if ($permisos === false) {
            $permisos = $this->loadPermisosFromDB();
            Yii::$app->cache->set($cacheKey, $permisos, 300); // 5 minutos
        }

        return in_array($permisoNombre, $permisos, true);
    }

    /**
     * Carga los permisos del usuario desde la base de datos.
     * 
     * @return array Array de nombres de permisos
     */
    private function loadPermisosFromDB(): array
    {
        return (new \yii\db\Query())
            ->select('p.nombre')
            ->from('{{%permiso}} p')
            ->innerJoin('{{%rol_permiso}} rp', 'rp.permiso_id = p.id')
            ->where(['rp.rol_id' => $this->rol_id])
            ->column();
    }

    /**
     * Invalida la caché de permisos del usuario.
     * Debe llamarse cuando se modifican los permisos de un rol.
     */
    public static function invalidatePermisoCache(int $rolId): void
    {
        // Obtener todos los usuarios con este rol
        $userIds = static::find()->where(['rol_id' => $rolId])->select('id')->column();
        
        foreach ($userIds as $userId) {
            Yii::$app->cache->delete("user_perms_{$userId}");
        }
    }

    // -------------------------------------------------------------------------
    // Relaciones
    // -------------------------------------------------------------------------

    public function getRol(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Rol::class, ['id' => 'rol_id']);
    }
}
