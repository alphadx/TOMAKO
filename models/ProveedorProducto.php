<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * This is the model class for table "proveedor_producto".
 *
 * @property int $id
 * @property int $proveedor_id Proveedor que ofrece el producto
 * @property int $inventory_item_id Repuesto/inventario asociado
 * @property string|null $codigo_proveedor Código del producto en el catálogo del proveedor
 * @property float|null $precio_compra Precio de compra unitario
 * @property int|null $tiempo_entrega_dias Tiempo de entrega en días para este producto
 * @property int $stock_minimo_sugerido Stock mínimo sugerido
 * @property bool $activo Indica si el producto está activo
 * @property string|null $observaciones Observaciones adicionales
 * @property string|null $created_at Fecha de creación
 * @property string|null $updated_at Fecha de actualización
 *
 * @property Proveedor $proveedor
 * @property InventoryItem $inventoryItem
 */
class ProveedorProducto extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%proveedor_producto}}';
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
                'value' => new \yii\db\Expression('NOW()'),
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
            [['proveedor_id', 'inventory_item_id'], 'required'],
            [['proveedor_id', 'inventory_item_id', 'tiempo_entrega_dias', 'stock_minimo_sugerido'], 'integer'],
            [['activo'], 'boolean'],
            [['precio_compra'], 'number', 'scale' => 2],
            [['codigo_proveedor'], 'string', 'max' => 50],
            [['observaciones'], 'string'],
            [['proveedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proveedor::class, 'targetAttribute' => ['proveedor_id' => 'id']],
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
            'proveedor_id' => 'Proveedor',
            'inventory_item_id' => 'Repuesto/Inventario',
            'codigo_proveedor' => 'Código Proveedor',
            'precio_compra' => 'Precio Compra',
            'tiempo_entrega_dias' => 'Tiempo Entrega (días)',
            'stock_minimo_sugerido' => 'Stock Mínimo Sugerido',
            'activo' => 'Activo',
            'observaciones' => 'Observaciones',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
        ];
    }

    /**
     * Relación con proveedor
     */
    public function getProveedor()
    {
        return $this->hasOne(Proveedor::class, ['id' => 'proveedor_id']);
    }

    /**
     * Relación con item de inventario
     */
    public function getInventoryItem()
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'inventory_item_id']);
    }

    /**
     * Retorna productos activos de un proveedor
     */
    public static function getProductosPorProveedor($proveedorId)
    {
        return self::find()
            ->where(['proveedor_id' => $proveedorId, 'activo' => true])
            ->orderBy(['inventory_item_id' => SORT_ASC])
            ->all();
    }

    /**
     * Retorna proveedores para un producto específico
     */
    public static function getProveedoresPorProducto($inventoryItemId)
    {
        return self::find()
            ->where(['inventory_item_id' => $inventoryItemId, 'activo' => true])
            ->joinWith('proveedor')
            ->orderBy(['precio_compra' => SORT_ASC])
            ->all();
    }

    /**
     * Busca el mejor precio para un producto
     */
    public static function getMejorPrecio($inventoryItemId)
    {
        $mejor = self::find()
            ->where(['inventory_item_id' => $inventoryItemId, 'activo' => true])
            ->andWhere(['!=', 'precio_compra', null])
            ->orderBy(['precio_compra' => SORT_ASC])
            ->one();
        
        return $mejor ? $mejor->precio_compra : null;
    }
}
