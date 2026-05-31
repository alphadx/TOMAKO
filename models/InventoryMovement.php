<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo InventoryMovement. Mapea la tabla {{%inventory_movement}}.
 * Registra entradas, salidas y ajustes de stock.
 *
 * @property int         $id
 * @property int         $item_id
 * @property string      $tipo        entrada|salida|ajuste
 * @property int         $cantidad_delta
 * @property int         $cantidad_anterior
 * @property int         $cantidad_nueva
 * @property int|null    $usuario_id
 * @property string|null $referencia
 * @property int|null    $created_at
 *
 * @property-read InventoryItem $item
 * @property-read User          $usuario
 */
class InventoryMovement extends ActiveRecord
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
        return '{{%inventory_movement}}';
    }

    public function beforeSave($insert): bool
    {
        if (!$insert) {
            return false;
        }

        return parent::beforeSave($insert);
    }

    public function beforeDelete(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            [['item_id', 'tipo', 'cantidad_delta', 'cantidad_anterior', 'cantidad_nueva'], 'required'],
            ['item_id',           'integer'],
            ['tipo',              'in', 'range' => ['entrada', 'salida', 'ajuste']],
            ['cantidad_delta',    'integer'],
            ['cantidad_anterior', 'integer', 'min' => 0],
            ['cantidad_nueva',    'integer', 'min' => 0],
            ['usuario_id',        'integer'],
            ['referencia',        'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                => 'ID',
            'item_id'           => 'Ítem',
            'tipo'              => 'Tipo',
            'cantidad_delta'    => 'Cantidad',
            'cantidad_anterior' => 'Stock Anterior',
            'cantidad_nueva'    => 'Stock Nuevo',
            'usuario_id'        => 'Usuario',
            'referencia'        => 'Referencia',
            'created_at'        => 'Fecha',
        ];
    }

    public static function getTiposList(): array
    {
        return ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste'];
    }

    public function getItem(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'item_id']);
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }
}
