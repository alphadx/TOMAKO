<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo HistorialPrecio. Mapea la tabla {{%historial_precio}}.
 * Registra cambios de precio en servicios.
 *
 * @property int         $id
 * @property int         $servicio_id
 * @property float       $precio_anterior
 * @property float       $precio_nuevo
 * @property int|null    $usuario_id
 * @property string|null $motivo
 * @property int|null    $created_at
 *
 * @property-read Servicio  $servicio
 * @property-read User|null $usuario
 */
class HistorialPrecio extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%historial_precio}}';
    }

    public function rules(): array
    {
        return [
            [['servicio_id', 'precio_anterior', 'precio_nuevo'], 'required'],
            [['servicio_id', 'usuario_id'], 'integer'],
            [['precio_anterior', 'precio_nuevo'], 'number'],
            ['motivo', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'servicio_id'     => 'Servicio',
            'precio_anterior' => 'Precio Anterior',
            'precio_nuevo'    => 'Precio Nuevo',
            'usuario_id'      => 'Usuario',
            'motivo'          => 'Motivo',
            'created_at'      => 'Fecha',
        ];
    }

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }
}
