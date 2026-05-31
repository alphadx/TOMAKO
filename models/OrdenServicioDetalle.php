<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo OrdenServicioDetalle. Mapea {{%orden_servicio_detalle}}.
 *
 * @property int   $id
 * @property int   $orden_id
 * @property int   $servicio_id
 * @property int   $cantidad
 * @property float $precio_unitario
 * @property float $subtotal
 * @property string|null $nota
 *
 * @property-read OrdenServicio $orden
 * @property-read Servicio      $servicio
 */
class OrdenServicioDetalle extends ActiveRecord
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
        return '{{%orden_servicio_detalle}}';
    }

    public function rules(): array
    {
        return [
            [['orden_id', 'servicio_id', 'precio_unitario'], 'required'],
            [['orden_id', 'servicio_id', 'cantidad'], 'integer', 'min' => 1],
            [['precio_unitario', 'subtotal'], 'number', 'min' => 0],
            ['nota', 'string', 'max' => 500],
            ['cantidad', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'orden_id'        => 'Orden',
            'servicio_id'     => 'Servicio',
            'cantidad'        => 'Cantidad',
            'precio_unitario' => 'Precio Unitario',
            'subtotal'        => 'Subtotal',
            'nota'            => 'Nota',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->subtotal = (float) $this->cantidad * (float) $this->precio_unitario;
        return true;
    }

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }
}
