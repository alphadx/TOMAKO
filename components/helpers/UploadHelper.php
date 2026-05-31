<?php
declare(strict_types=1);

namespace app\components;

use Yii;
use yii\web\UploadedFile;
use app\models\OrdenServicioArchivo;

/**
 * Helper para manejo de uploads de archivos en órdenes de servicio
 * 
 * Soporta la HU-004: Adjuntar Fotos y Documentos a Órdenes
 */
class UploadHelper
{
    /**
     * Directorio base para uploads de órdenes
     */
    public const DIRECTORIO_BASE = '@webroot/uploads/ordenes';
    
    /**
     * Sube un archivo para una orden de servicio
     * 
     * @param int $ordenServicioId ID de la orden
     * @param UploadedFile $archivo Archivo subido
     * @param string $tipo 'foto' o 'documento'
     * @param string|null $descripcion Descripción opcional
     * @return OrdenServicioArchivo|null Modelo guardado o null si falla
     */
    public static function subirArchivo(
        int $ordenServicioId,
        UploadedFile $archivo,
        string $tipo = OrdenServicioArchivo::TIPO_FOTO,
        ?string $descripcion = null
    ): ?OrdenServicioArchivo {
        // Validar MIME type
        if (!OrdenServicioArchivo::validarMimeType($archivo->tempName, $tipo)) {
            return null;
        }
        
        // Validar tamaño
        if ($archivo->size > OrdenServicioArchivo::TAMAÑO_MAXIMO) {
            return null;
        }
        
        // Crear directorio si no existe
        $directorio = self::obtenerDirectorio($ordenServicioId, $tipo);
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Generar nombre único
        $extension = strtolower($archivo->extension);
        $nombreUnico = uniqid('arch_', false) . '_' . time() . '.' . $extension;
        $rutaRelativa = 'uploads/ordenes/' . $ordenServicioId . '/' . $tipo . '/' . $nombreUnico;
        $rutaCompleta = Yii::getAlias(self::DIRECTORIO_BASE) . '/' . $ordenServicioId . '/' . $tipo . '/' . $nombreUnico;
        
        // Guardar archivo
        if (!$archivo->saveAs($rutaCompleta)) {
            return null;
        }
        
        // Crear thumbnail si es imagen
        $rutaThumbnail = null;
        if ($tipo === OrdenServicioArchivo::TIPO_FOTO) {
            $rutaThumbnail = ImageProcessor::generarThumbnail($rutaCompleta, $ordenServicioId);
        }
        
        // Crear modelo
        $modelo = new OrdenServicioArchivo();
        $modelo->orden_servicio_id = $ordenServicioId;
        $modelo->tipo = $tipo;
        $modelo->ruta_archivo = $rutaRelativa;
        $modelo->ruta_thumbnail = $rutaThumbnail;
        $modelo->nombre_original = $archivo->name;
        $modelo->mime_type = $archivo->tempName;
        $modelo->tamaño_bytes = $archivo->size;
        $modelo->descripcion = $descripcion;
        
        if (!$modelo->save()) {
            // Rollback: eliminar archivo si falla save
            unlink($rutaCompleta);
            if ($rutaThumbnail !== null) {
                $thumbnailPath = Yii::getAlias('@webroot') . '/' . $rutaThumbnail;
                if (file_exists($thumbnailPath)) {
                    unlink($thumbnailPath);
                }
            }
            return null;
        }
        
        return $modelo;
    }
    
    /**
     * Sube múltiples archivos para una orden
     * 
     * @param int $ordenServicioId ID de la orden
     * @param array $archivos Array de UploadedFile
     * @param string $tipo 'foto' o 'documento'
     * @return array Modelos guardados
     */
    public static function subirMultiples(
        int $ordenServicioId,
        array $archivos,
        string $tipo = OrdenServicioArchivo::TIPO_FOTO
    ): array {
        $modelosGuardados = [];
        
        foreach ($archivos as $archivo) {
            if ($archivo instanceof UploadedFile && $archivo->error === UPLOAD_ERR_OK) {
                $modelo = self::subirArchivo($ordenServicioId, $archivo, $tipo);
                if ($modelo !== null) {
                    $modelosGuardados[] = $modelo;
                }
            }
        }
        
        return $modelosGuardados;
    }
    
    /**
     * Obtiene el directorio para un tipo de archivo
     */
    private static function obtenerDirectorio(int $ordenServicioId, string $tipo): string
    {
        return Yii::getAlias(self::DIRECTORIO_BASE) . '/' . $ordenServicioId . '/' . $tipo;
    }
    
    /**
     * Elimina un archivo de una orden
     * 
     * @param int $archivoId ID del archivo
     * @return bool True si se eliminó correctamente
     */
    public static function eliminarArchivo(int $archivoId): bool
    {
        $modelo = OrdenServicioArchivo::findOne($archivoId);
        if ($modelo === null) {
            return false;
        }
        
        return $modelo->delete();
    }
    
    /**
     * Obtiene todos los archivos de una orden
     * 
     * @param int $ordenServicioId ID de la orden
     * @param string|null $tipo Filtrar por tipo ('foto' o 'documento')
     * @return OrdenServicioArchivo[]
     */
    public static function obtenerArchivosOrden(int $ordenServicioId, ?string $tipo = null): array
    {
        $query = OrdenServicioArchivo::find()
            ->where(['orden_servicio_id' => $ordenServicioId])
            ->orderBy(['created_at' => SORT_DESC]);
        
        if ($tipo !== null) {
            $query->andWhere(['tipo' => $tipo]);
        }
        
        return $query->all();
    }
    
    /**
     * Cuenta los archivos de una orden
     * 
     * @param int $ordenServicioId ID de la orden
     * @return array ['fotos' => int, 'documentos' => int]
     */
    public static function contarArchivosOrden(int $ordenServicioId): array
    {
        $fotos = OrdenServicioArchivo::find()
            ->where(['orden_servicio_id' => $ordenServicioId, 'tipo' => OrdenServicioArchivo::TIPO_FOTO])
            ->count();
        
        $documentos = OrdenServicioArchivo::find()
            ->where(['orden_servicio_id' => $ordenServicioId, 'tipo' => OrdenServicioArchivo::TIPO_DOCUMENTO])
            ->count();
        
        return [
            'fotos' => (int)$fotos,
            'documentos' => (int)$documentos,
        ];
    }
}
