<?php
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo LoginAttempt. Registra intentos de inicio de sesión. Tabla {{%login_attempt}}.
 *
 * @property int $id
 * @property string $ip
 * @property string|null $email
 * @property int $exitoso
 * @property int $created_at
 */
class LoginAttempt extends ActiveRecord
{
    public static function tableName(): string { return '{{%login_attempt}}'; }

    public function rules(): array
    {
        return [
            [['ip', 'created_at'], 'required'],
            ['ip', 'string', 'max' => 45],
            ['email', 'string', 'max' => 150],
            ['exitoso', 'integer'],
            ['exitoso', 'default', 'value' => 0],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;
        if ($insert) $this->created_at = time();
        return true;
    }

    /**
     * Cuenta intentos fallidos de una IP en los últimos $minutos minutos.
     */
    public static function contarFallidosRecientes(string $ip, int $minutos = 15): int
    {
        $desde = time() - ($minutos * 60);
        return (int) static::find()
            ->where(['ip' => $ip, 'exitoso' => 0])
            ->andWhere(['>=', 'created_at', $desde])
            ->count();
    }

    /**
     * Registra un intento de login.
     */
    public static function registrar(string $ip, ?string $email, bool $exitoso): void
    {
        try {
            $intento = new static();
            $intento->ip = $ip;
            $intento->email = $email;
            $intento->exitoso = $exitoso ? 1 : 0;
            $intento->created_at = time();
            $intento->save(false);
        } catch (\Throwable $e) {
            \Yii::warning('No se pudo registrar LoginAttempt: ' . $e->getMessage(), 'app.auth');
        }
    }
}
