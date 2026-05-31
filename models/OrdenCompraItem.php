<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * This is the model class for table "orden_compra_item".
 *
 * @property int $id
 * @property int $orden_compra_id Orden de compra asociada
 * @property int|null $inventory_item_id Repuesto/inventario asociado
 * @property string $descripcion Descripción del item
 * @property int $cantidad Cantidad solicitada
 * @property int $cantidad_recibida Cantidad recibida (actualización durante recepción)
 * @property decimal $precio_unitario Precio unitario del item
 * @property decimal $subtotal Subtotal (cantidad * precio_unitario)
 * @property string|null $observaciones Observaciones adicionales
 * @property string|null $created_at Fecha de creación
 * @property string|null $updated_at Fecha de actualización
 *
 * @property OrdenCompra $ordenCompra
 * @property InventoryItem $inventoryItem
 */
class OrdenCompraItem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%orden_compra_item}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            'audit' => [
                'class' => AuditBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['orden_compra_id', 'descripcion', 'cantidad', 'precio_unitario'], 'required'],
            [['orden_compra_id', 'inventory_item_id'], 'integer'],
            [['cantidad', 'cantidad_recibida'], 'integer', 'min' => 0],
            [['precio_unitario'], 'number', 'scale' => 2],
            [['descripcion'], 'string', 'max' => 255],
            [['observaciones'], 'string'],
            [['orden_compra_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrdenCompra::class, 'targetAttribute' => ['orden_compra_id' => 'id']],
            [['inventory_item_id'], 'exist', 'skipOnError' => true, 'targetClass' => InventoryItem::class, 'targetAttribute' => ['inventory_item_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orden_compra_id' => 'Orden de Compra',
            'inventory_item_id' => 'Repuesto/Inventario',
            'descripcion' => 'Descripción',
            'cantidad' => 'Cantidad Solicitada',
            'cantidad_recibida' => 'Cantidad Recibida',
            'precio_unitario' => 'Precio Unitario',
            'subtotal' => 'Subtotal',
            'observaciones' => 'Observaciones',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
        ];
    }

    /**
     * Relación con orden de compra
     */
    public function getOrdenCompra()
    {
        return $this->hasOne(OrdenCompra::class, ['id' => 'orden_compra_id']);
    }

    /**
     * Relación con item de inventario
     */
    public function getInventoryItem()
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'inventory_item_id']);
    }

    /**
     * Before save: calcular subtotal automáticamente
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->subtotal = $this->cantidad * $this->precio_unitario;
            return true;
        }
        return false;
    }

    /**
     * Verifica si el item ha sido recibido completamente
     */
    public function esRecibidoCompleto()
    {
        return $this->cantidad_recibida >= $this->cantidad;
    }

    /**
     * Verifica si el item ha sido recibido parcialmente
     */
    public function esRecibidoParcial()
    {
        return $this->cantidad_recibida > 0 && $this->cantidad_recibida < $this->cantidad;
    }

    /**
     * Verifica si el item no ha sido recibido
     */
    public function esPendiente()
    {
        return $this->cantidad_recibida == 0;
    }

    /**
     * Retorna el porcentaje de recepción del item
     */
    public function getPorcentajeRecibido()
    {
        if ($this->cantidad == 0) {
            return 0;
        }
        return round(($this->cantidad_recibida / $this->cantidad) * 100, 2);
    }

    /**
     * Retorna la cantidad pendiente de recibir
     */
    public function getCantidadPendiente()
    {
        return max(0, $this->cantidad - $this->cantidad_recibida);
    }
}
