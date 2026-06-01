<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo InventoryItem. Mapea la tabla {{%inventory_item}}.
 *
 * @property int         $id
 * @property string      $sku
 * @property string      $nombre
 * @property string|null $descripcion
 * @property int         $categoria_id
 * @property float       $precio_unitario
 * @property int         $cantidad
 * @property int         $stock_minimo
 * @property int|null    $stock_maximo
 * @property string      $unidad
 * @property string|null $ubicacion
 * @property string|null $foto_path
 * @property string|null $qr_code
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Categoria                $categoria
 * @property-read InventoryMovement[]      $movimientos
 * @property-read InventoryItemImage[]     $imagenes
 * @property-read InventoryItemImage|null  $imagenDefault
 */
class InventoryItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%inventory_item}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    public function rules(): array
    {
        return [
            [['sku', 'nombre', 'categoria_id'], 'required'],
            ['sku',             'string',  'max' => 20],
            ['sku',             'unique'],
            ['nombre',          'string',  'max' => 150],
            ['descripcion',     'string'],
            ['categoria_id',    'integer'],
            ['categoria_id',    'exist', 'targetClass' => Categoria::class, 'targetAttribute' => 'id'],
            ['categoria_id',    'validarCategoriaInsumo'],
            ['precio_unitario', 'number', 'min' => 0],
            ['cantidad',        'integer', 'min' => 0],
            ['stock_minimo',    'integer', 'min' => 0],
            ['stock_maximo',    'integer', 'min' => 0],
            ['unidad',          'in', 'range' => ['unidad', 'litro', 'kg', 'metro']],
            ['unidad',          'default', 'value' => 'unidad'],
            ['ubicacion',       'string',  'max' => 100],
            ['foto_path',       'string',  'max' => 255],
            ['status',          'boolean'],
            ['status',          'default', 'value' => 1],
        ];
    }

    /**
     * Genera el codigo QR unico para este producto basado en SKU.
     */
    public function generarQrCode(): string
    {
        return $this->sku . '-' . strtoupper(Yii::$app->security->generateRandomString(8));
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if ($this->getIsNewRecord() && ($this->sku === null || $this->sku === '')) {
            $this->sku = self::generarSku();
            $this->qr_code = $this->generarQrCode();
        } elseif ($this->isAttributeChanged('sku', false)) {
            $this->addError('sku', 'El SKU no se puede modificar.');
            return false;
        }

        return true;
    }

    public function validarCategoriaInsumo(string $attribute): void
    {
        $categoria = Categoria::findOne($this->$attribute);
        if ($categoria !== null && $categoria->tipo === 'servicio') {
            $this->addError($attribute, 'La categoría debe ser de tipo insumo o ambos.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'sku'             => 'SKU',
            'nombre'          => 'Nombre',
            'descripcion'     => 'Descripción',
            'categoria_id'    => 'Categoría',
            'precio_unitario' => 'Precio Unitario',
            'cantidad'        => 'Stock Actual',
            'stock_minimo'    => 'Stock Mínimo',
            'stock_maximo'    => 'Stock Máximo',
            'unidad'          => 'Unidad',
            'ubicacion'       => 'Ubicación',
            'foto_path'       => 'Foto',
            'status'          => 'Estado',
            'created_at'      => 'Creado',
            'updated_at'      => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Genera el siguiente SKU en formato INS-NNNN.
     */
    public static function generarSku(): string
    {
        $ultimo = static::find()
            ->where(['like', 'sku', 'INS-'])
            ->orderBy(['id' => SORT_DESC])
            ->scalar();

        $siguiente = 1;
        if ($ultimo && preg_match('/INS-(\d+)$/', (string) $ultimo, $m)) {
            $siguiente = (int) $m[1] + 1;
        }
        return 'INS-' . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }

    public static function getEstadosList(): array
    {
        return ['1' => 'Activo', '0' => 'Inactivo'];
    }

    public static function getUnidadesList(): array
    {
        return ['unidad' => 'Unidad', 'litro' => 'Litro', 'kg' => 'Kilogramo', 'metro' => 'Metro'];
    }

    // ── Lógica de negocio ─────────────────────────────────────────────────────

    /**
     * Retorna el estado de stock: 'sin_stock' | 'bajo' | 'en_stock'.
     */
    public function getEstadoStock(): string
    {
        if ($this->cantidad <= 0) {
            return 'sin_stock';
        }
        if ($this->cantidad <= $this->stock_minimo) {
            return 'bajo';
        }
        return 'en_stock';
    }

    /**
     * Retorna la clase Bootstrap badge según el estado de stock.
     */
    public function getEstadoStockClass(): string
    {
        return match ($this->getEstadoStock()) {
            'sin_stock' => 'danger',
            'bajo'      => 'warning',
            default     => 'success',
        };
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    /**
     * Busca un item por su codigo QR.
     */
    public static function findByQrCode(string $qrCode): ?self
    {
        return static::find()->where(['qr_code' => $qrCode])->one();
    }

    /**
     * URL del producto para compartir/escanear QR.
     */
    public function getQrUrl(): string
    {
        return Yii::$app->urlManager->createUrl(['inventario/view', 'id' => $this->id]);
    }

    public function getCategoria(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getMovimientos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryMovement::class, ['item_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }

    /** Órdenes donde se utilizó este repuesto (HU-013) */
    /**
     * Imagenes del producto.
     */
    public function getImagenes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryItemImage::class, ['item_id' => 'id'])
            ->andWhere(['is_active' => 1])
            ->orderBy(['is_default' => SORT_DESC, 'created_at' => SORT_DESC]);
    }

    /**
     * Imagen predefinida del producto.
     */
    public function getImagenDefault(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryItemImage::class, ['item_id' => 'id'])
            ->andWhere(['is_default' => 1, 'is_active' => 1]);
    }

    public function getOrdenesServicio(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioRepuesto::class, ['repuesto_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }
}
