<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Modelo InventoryItemImage. Mapea la tabla {{%inventory_item_imagen}}.
 *
 * @property int         $id
 * @property int         $item_id
 * @property string      $filename
 * @property string      $filepath
 * @property int         $is_default
 * @property int         $is_active
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read InventoryItem $item
 */
class InventoryItemImage extends ActiveRecord
{
    /** @var UploadedFile|null Archivo subido (transitorio) */
    public $imageFile;

    /** Extensiones permitidas */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'bmp'];
    /** Tamano maximo en bytes (5MB) */
    private const MAX_SIZE = 5 * 1024 * 1024;

    public static function tableName(): string
    {
        return '{{%inventory_item_imagen}}';
    }

    public function rules(): array
    {
        return [
            [['item_id'], 'required'],
            [['item_id', 'is_default', 'is_active'], 'integer'],
            [['filename'], 'string', 'max' => 255],
            [['filepath'], 'string', 'max' => 500],
            [['imageFile'], 'file', 'skipOnEmpty' => true, 'extensions' => self::ALLOWED_EXTENSIONS, 'maxSize' => self::MAX_SIZE],
            [['item_id'], 'exist', 'targetClass' => InventoryItem::class, 'targetAttribute' => 'id'],
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

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'item_id'    => 'Item',
            'filename'   => 'Nombre Archivo',
            'filepath'   => 'Ruta',
            'is_default' => 'Predefinida',
            'is_active'  => 'Activa',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
            'imageFile'  => 'Imagen',
        ];
    }

    // ── Relaciones ─────────────────────────────────────────────────────

    public function getItem(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'item_id']);
    }

    // ── Logica de archivos ─────────────────────────────────────────────

    /**
     * Directorio de subida para imagenes de inventario.
     */
    public static function getUploadDir(): string
    {
        return Yii::getAlias('@webroot') . '/uploads/inventario/imagenes/';
    }

    /**
     * URL base para acceder a las imagenes.
     */
    public static function getUploadUrl(): string
    {
        return Yii::getAlias('@web') . '/uploads/inventario/imagenes/';
    }

    /**
     * URL completa de la imagen.
     */
    public function getUrl(): string
    {
        return self::getUploadUrl() . $this->filepath;
    }

    /**
     * Procesa y guarda el archivo subido.
     */
    public function upload(): bool
    {
        if ($this->imageFile === null) {
            return false;
        }

        $dir = self::getUploadDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        // Generar nombre unico: item_id + timestamp + extension
        $ext = strtolower($this->imageFile->extension);
        $this->filename = $this->imageFile->baseName . '.' . $ext;
        $this->filepath = 'item_' . $this->item_id . '_' . time() . '_' . Yii::$app->security->generateRandomString(6) . '.' . $ext;

        $fullPath = $dir . $this->filepath;

        if ($this->imageFile->saveAs($fullPath)) {
            // Intentar convertir HEIC a JPEG si es necesario
            if ($ext === 'heic') {
                $this->convertHeicToJpeg($fullPath);
            }
            return true;
        }

        return false;
    }

    /**
     * Intenta convertir HEIC a JPEG (requiere imagick o similar).
     */
    private function convertHeicToJpeg(string $path): void
    {
        try {
            if (class_exists('Imagick')) {
                $imagick = new \Imagick($path);
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(85);
                $jpegPath = str_replace('.heic', '.jpg', $path);
                $imagick->writeImage($jpegPath);
                $imagick->clear();
                unlink($path);
                $this->filepath = basename($jpegPath);
                $this->filename = str_replace('.heic', '.jpg', $this->filename);
            }
        } catch (\Throwable $e) {
            Yii::warning("Error convirtiendo HEIC: " . $e->getMessage());
        }
    }

    /**
     * Elimina el archivo fisico.
     */
    public function deleteFile(): void
    {
        $path = self::getUploadDir() . $this->filepath;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Marca esta imagen como predefinida y desmarca las demas del mismo item.
     */
    public function setAsDefault(): void
    {
        // Desmarcar todas las imagenes del mismo item
        static::updateAll(['is_default' => 0], ['item_id' => $this->item_id]);
        // Marcar esta como predefinida
        $this->is_default = 1;
        $this->save(false, ['is_default', 'updated_at']);
    }

    /**
     * Da de baja la imagen (soft delete).
     */
    public function deactivate(): bool
    {
        $this->is_active = 0;
        return $this->save(false, ['is_active', 'updated_at']);
    }

    /**
     * Obtiene la imagen predefinida de un item.
     * Si no hay ninguna marcada explícitamente como predefinida,
     * retorna la primera imagen activa (fallback automático).
     */
    public static function getDefaultForItem(int $itemId): ?self
    {
        // Buscar imagen marcada explícitamente como predefinida
        $default = static::find()
            ->where(['item_id' => $itemId, 'is_default' => 1, 'is_active' => 1])
            ->one();

        if ($default !== null) {
            return $default;
        }

        // Fallback: primera imagen activa ordenada por fecha de creación
        return static::find()
            ->where(['item_id' => $itemId, 'is_active' => 1])
            ->orderBy(['created_at' => SORT_ASC])
            ->one();
    }

    /**
     * Obtiene todas las imagenes activas de un item, ordenadas con la predefinida primero.
     */
    public static function getActiveForItem(int $itemId): array
    {
        return static::find()
            ->where(['item_id' => $itemId, 'is_active' => 1])
            ->orderBy(['is_default' => SORT_DESC, 'created_at' => SORT_DESC])
            ->all();
    }
}