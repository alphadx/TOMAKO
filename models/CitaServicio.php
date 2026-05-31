<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo CitaServicio. Mapea la tabla pivote {{%cita_servicio}}.
 *
 * @property int $cita_id
 * @property int $servicio_id
 *
 * @property-read Cita     $cita
 * @property-read Servicio $servicio
 */
class CitaServicio extends ActiveRecord
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
        return '{{%cita_servicio}}';
    }

    public static function primaryKey(): array
    {
        return ['cita_id', 'servicio_id'];
    }

    public function rules(): array
    {
        return [
            [['cita_id', 'servicio_id'], 'required'],
            [['cita_id', 'servicio_id'], 'integer'],
            ['cita_id',     'exist', 'targetClass' => Cita::class,     'targetAttribute' => 'id'],
            ['servicio_id', 'exist', 'targetClass' => Servicio::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getCita(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cita::class, ['id' => 'cita_id']);
    }

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }
}
