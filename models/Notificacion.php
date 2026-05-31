<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Notificacion interna por usuario.
 */
class Notificacion extends ActiveRecord
{
    public const TIPO_STOCK_BAJO = 'stock_bajo';
    public const TIPO_ORDEN_LISTA = 'orden_lista';
    public const TIPO_CITA_CONFIRMADA = 'cita_confirmada';
    public const TIPO_SISTEMA = 'sistema';
    public const TIPO_MANTENCION_PENDIENTE = 'mantencion_pendiente';

    public static function tableName(): string
    {
        return '{{%notificacion}}';
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
            [['usuario_id', 'tipo', 'titulo', 'mensaje'], 'required'],
            [['usuario_id', 'leida', 'leida_at', 'created_at', 'updated_at'], 'integer'],
            [['mensaje'], 'string'],
            [['tipo'], 'in', 'range' => [
                self::TIPO_STOCK_BAJO,
                self::TIPO_ORDEN_LISTA,
                self::TIPO_CITA_CONFIRMADA,
                self::TIPO_SISTEMA,
                self::TIPO_MANTENCION_PENDIENTE,
            ]],
            [['titulo'], 'string', 'max' => 150],
            [['url'], 'string', 'max' => 300],
            [['usuario_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }

    public static function getTipoBadgeClass(): array
    {
        return [
            self::TIPO_STOCK_BAJO => 'warning',
            self::TIPO_ORDEN_LISTA => 'success',
            self::TIPO_CITA_CONFIRMADA => 'info',
            self::TIPO_SISTEMA => 'secondary',
            self::TIPO_MANTENCION_PENDIENTE => 'danger',
        ];
    }
}
