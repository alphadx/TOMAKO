<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Model OrdenServicioArchivo
 * 
 * Representa archivos adjuntos (fotos y documentos) a una orden de servicio.
 * Soporta la HU-004: Adjuntar Fotos y Documentos a Órdenes
 *
 * @property int $id
 * @property int $orden_servicio_id
 * @property string $tipo 'foto' o 'documento'
 * @property string $ruta_archivo
 * @property string|null $ruta_thumbnail
 * @property string $nombre_original
 * @property string $mime_type
 * @property int $tamaño_bytes
 * @property string|null $descripcion
 * @property int|null $uploaded_by
 * @property int $created_at
 * @property int|null $updated_at
 *
 * @property-read OrdenServicio $ordenServicio
 * @property-read User $uploadedBy
 */
class OrdenServicioArchivo extends ActiveRecord
{
    /** Tipos de archivo permitidos */
    public const TIPO_FOTO = 'foto';
    public const TIPO_DOCUMENTO = 'documento';
    
    /** Tamaño máximo en bytes (2MB) */
    public const TAMAÑO_MAXIMO = 2097152;
    
    /** Extensiones permitidas */
    public const EXTENSIONES_PERMITIDAS = [
        self::TIPO_FOTO => ['jpg', 'jpeg', 'png', 'heic', 'webp'],
        self::TIPO_DOCUMENTO => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
    ];
    
    /** MIME types permitidos */
    public const MIME_TYPES_PERMITIDOS = [
        self::TIPO_FOTO => ['image/jpeg', 'image/png', 'image/heic', 'image/webp'],
        self::TIPO_DOCUMENTO => ['application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain'],
    ];

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
        return '{{%orden_servicio_archivo}}';
    }

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'tipo', 'ruta_archivo', 'nombre_original', 'mime_type', 'tamaño_bytes'], 'required'],
            [['orden_servicio_id', 'uploaded_by'], 'integer'],
            [['orden_servicio_id'], 'exist', 'targetClass' => OrdenServicio::class, 'targetAttribute' => 'id'],
            [['uploaded_by'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['tipo'], 'in', 'range' => [self::TIPO_FOTO, self::TIPO_DOCUMENTO]],
            [['ruta_archivo', 'ruta_thumbnail'], 'string', 'max' => 500],
            [['nombre_original'], 'string', 'max' => 255],
            [['mime_type'], 'string', 'max' => 100],
            [['descripcion'], 'string', 'max' => 500],
            [['tamaño_bytes'], 'integer', 'min' => 0],
            [['created_at', 'updated_at'], 'default', 'value' => null],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'tipo' => 'Tipo de Archivo',
            'ruta_archivo' => 'Ruta del Archivo',
            'ruta_thumbnail' => 'Thumbnail',
            'nombre_original' => 'Nombre Original',
            'mime_type' => 'Tipo MIME',
            'tamaño_bytes' => 'Tamaño (bytes)',
            'descripcion' => 'Descripción',
            'uploaded_by' => 'Subido por',
            'created_at' => 'Fecha de Subida',
            'updated_at' => 'Última Actualización',
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
            if ($this->uploaded_by === null && !Yii::$app->user->isGuest) {
                $this->uploaded_by = Yii::$app->user->id;
            }
        }
        $this->updated_at = $now;
        
        return true;
    }

    // Relaciones
    
    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    public function getUploadedBy(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'uploaded_by']);
    }

    // Métodos utilitarios
    
    /**
     * Verifica si el archivo es una imagen
     */
    public function getEsImagen(): bool
    {
        return $this->tipo === self::TIPO_FOTO;
    }

    /**
     * Obtiene el tamaño formateado en KB o MB
     */
    public function getTamañoFormateado(): string
    {
        if ($this->tamaño_bytes < 1024) {
            return $this->tamaño_bytes . ' B';
        } elseif ($this->tamaño_bytes < 1048576) {
            return round($this->tamaño_bytes / 1024, 2) . ' KB';
        } else {
            return round($this->tamaño_bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Obtiene la URL completa del archivo
     */
    public function getUrl(): string
    {
        return Yii::getAlias('@web') . '/' . $this->ruta_archivo;
    }

    /**
     * Obtiene la URL del thumbnail (si existe)
     */
    public function getThumbnailUrl(): ?string
    {
        if ($this->ruta_thumbnail === null) {
            return null;
        }
        return Yii::getAlias('@web') . '/' . $this->ruta_thumbnail;
    }

    /**
     * Valida si un MIME type es permitido para el tipo dado
     */
    public static function validarMimeType(string $mimeType, string $tipo): bool
    {
        return in_array($mimeType, self::MIME_TYPES_PERMITIDOS[$tipo] ?? [], true);
    }

    /**
     * Obtiene la extensión desde el nombre original
     */
    public function getExtension(): string
    {
        $pathInfo = pathinfo($this->nombre_original);
        return strtolower($pathInfo['extension'] ?? '');
    }

    /**
     * Valida si la extensión es permitida para el tipo dado
     */
    public static function validarExtension(string $extension, string $tipo): bool
    {
        return in_array(strtolower($extension), self::EXTENSIONES_PERMITIDAS[$tipo] ?? [], true);
    }

    /**
     * Elimina el archivo físico del sistema
     */
    public function eliminarArchivo(): bool
    {
        $basePath = Yii::getAlias('@webroot');
        
        // Eliminar archivo principal
        $archivoPath = $basePath . '/' . $this->ruta_archivo;
        if (file_exists($archivoPath)) {
            unlink($archivoPath);
        }
        
        // Eliminar thumbnail si existe
        if ($this->ruta_thumbnail !== null) {
            $thumbnailPath = $basePath . '/' . $this->ruta_thumbnail;
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }
        
        return true;
    }

    /**
     * Before delete - elimina los archivos físicos
     */
    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }
        
        $this->eliminarArchivo();
        
        return true;
    }
}
