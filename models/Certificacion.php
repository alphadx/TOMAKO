<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Certificacion. Mapea la tabla {{%certificacion}}.
 *
 * @property int         $id
 * @property int         $tecnico_id
 * @property string      $titulo
 * @property string|null $entidad_emisora
 * @property string|null $fecha_emision
 * @property string|null $fecha_vencimiento
 * @property int|null    $created_at
 *
 * @property-read Tecnico $tecnico
 */
class Certificacion extends ActiveRecord
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
        return '{{%certificacion}}';
    }

    public function rules(): array
    {
        return [
            [['tecnico_id', 'titulo'], 'required'],
            ['tecnico_id',       'integer'],
            ['titulo',           'string', 'max' => 150],
            ['entidad_emisora',  'string', 'max' => 100],
            ['fecha_emision',    'date', 'format' => 'php:Y-m-d'],
            ['fecha_vencimiento','date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                => 'ID',
            'tecnico_id'        => 'Técnico',
            'titulo'            => 'Título',
            'entidad_emisora'   => 'Entidad Emisora',
            'fecha_emision'     => 'Fecha de Emisión',
            'fecha_vencimiento' => 'Fecha de Vencimiento',
            'created_at'        => 'Registrado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = time();
        }
        return true;
    }

    public function getTecnico(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Tecnico::class, ['id' => 'tecnico_id']);
    }
}
