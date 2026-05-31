<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

class PreferenciaNotificacion extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%preferencia_notificacion}}';
    }

    public function rules(): array
    {
        return [
            [['usuario_id'], 'required'],
            [['usuario_id', 'email_cita', 'email_orden_estado', 'interno_stock', 'interno_orden', 'created_at', 'updated_at'], 'integer'],
            [['usuario_id'], 'unique'],
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

    public static function findOrCreate(int $usuarioId): self
    {
        $pref = self::findOne(['usuario_id' => $usuarioId]);
        if ($pref !== null) {
            return $pref;
        }

        $pref = new self([
            'usuario_id' => $usuarioId,
            'email_cita' => 1,
            'email_orden_estado' => 1,
            'interno_stock' => 1,
            'interno_orden' => 1,
        ]);
        $pref->save(false);

        return $pref;
    }
}
