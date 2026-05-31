<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo OrdenNota. Mapea {{%orden_nota}}.
 *
 * @property int      $id
 * @property int      $orden_id
 * @property int|null $usuario_id
 * @property string   $texto
 * @property int|null $created_at
 *
 * @property-read OrdenServicio $orden
 */
class OrdenNota extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%orden_nota}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['orden_id', 'texto'], 'required'],
            ['orden_id',   'integer'],
            ['usuario_id', 'integer'],
            ['texto',      'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'orden_id'   => 'Orden',
            'usuario_id' => 'Usuario',
            'texto'      => 'Nota',
            'created_at' => 'Fecha',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert && $this->created_at === null) {
            $this->created_at = time();
        }
        return true;
    }

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }
}
