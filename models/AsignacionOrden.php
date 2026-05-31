<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo AsignacionOrden. Mapea {{%asignacion_orden}}.
 *
 * @property int      $id
 * @property int      $orden_id
 * @property int      $tecnico_id
 * @property int|null $asignado_at
 *
 * @property-read OrdenServicio $orden
 * @property-read Tecnico       $tecnico
 */
class AsignacionOrden extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%asignacion_orden}}';
    }

    public function rules(): array
    {
        return [
            [['orden_id', 'tecnico_id'], 'required'],
            [['orden_id', 'tecnico_id'], 'integer'],
            ['tecnico_id', 'exist', 'targetClass' => Tecnico::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'orden_id'    => 'Orden',
            'tecnico_id'  => 'Técnico',
            'asignado_at' => 'Asignado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert && $this->asignado_at === null) {
            $this->asignado_at = time();
        }
        return true;
    }

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }

    public function getTecnico(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_id']);
    }
}
