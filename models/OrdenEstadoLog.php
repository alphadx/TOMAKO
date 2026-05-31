<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo OrdenEstadoLog. Mapea {{%orden_estado_log}}.
 *
 * @property int         $id
 * @property int         $orden_id
 * @property string|null $estado_anterior
 * @property string      $estado_nuevo
 * @property int|null    $usuario_id
 * @property string|null $comentario
 * @property int|null    $created_at
 *
 * @property-read OrdenServicio $orden
 */
class OrdenEstadoLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%orden_estado_log}}';
    }

    public function rules(): array
    {
        return [
            [['orden_id', 'estado_nuevo'], 'required'],
            [['orden_id', 'usuario_id'], 'integer'],
            [['estado_anterior', 'estado_nuevo'], 'string', 'max' => 30],
            ['comentario', 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'orden_id'        => 'Orden',
            'estado_anterior' => 'Estado Anterior',
            'estado_nuevo'    => 'Estado Nuevo',
            'usuario_id'      => 'Usuario',
            'comentario'      => 'Comentario',
            'created_at'      => 'Fecha',
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
