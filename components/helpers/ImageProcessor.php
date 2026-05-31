<?php
declare(strict_types=1);

namespace app\components;

use Yii;

/**
 * Procesador de imágenes para compresión y generación de thumbnails
 * 
 * Soporta la HU-004: Adjuntar Fotos y Documentos a Órdenes
 */
class ImageProcessor
{
    /**
     * Tamaño máximo para thumbnails (cuadrado)
     */
    public const THUMBNAIL_SIZE = 300;
    
    /**
     * Calidad de compresión JPEG (0-100)
     */
    public const JPEG_QUALITY = 85;
    
    /**
     * Tamaño máximo de archivo en bytes (2MB)
     */
    public const MAX_FILE_SIZE = 2097152;

    /**
     * Genera un thumbnail para una imagen
     * 
     * @param string $rutaImagen Ruta completa de la imagen original
     * @param int $ordenServicioId ID de la orden (para directorio)
     * @return string|null Ruta relativa del thumbnail o null si falla
     */
    public static function generarThumbnail(string $rutaImagen, int $ordenServicioId): ?string
    {
        if (!file_exists($rutaImagen)) {
            return null;
        }
        
        // Obtener información de la imagen
        $info = getimagesize($rutaImagen);
        if ($info === false) {
            return null;
        }
        
        $tipo = $info[2];
        
        // Solo procesar imágenes soportadas
        if (!in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            return null;
        }
        
        // Crear directorio de thumbnails
        $directorioThumbnails = Yii::getAlias(UploadHelper::DIRECTORIO_BASE) . '/' . $ordenServicioId . '/thumbnails';
        if (!file_exists($directorioThumbnails)) {
            mkdir($directorioThumbnails, 0755, true);
        }
        
        // Generar nombre del thumbnail
        $nombreOriginal = pathinfo($rutaImagen, PATHINFO_FILENAME);
        $nombreThumbnail = 'thumb_' . $nombreOriginal . '.jpg';
        $rutaThumbnail = $directorioThumbnails . '/' . $nombreThumbnail;
        $rutaRelativa = 'uploads/ordenes/' . $ordenServicioId . '/thumbnails/' . $nombreThumbnail;
        
        // Crear recurso de imagen desde el origen
        $imagenOriginal = self::crearRecursoImagen($rutaImagen, $tipo);
        if ($imagenOriginal === false) {
            return null;
        }
        
        // Obtener dimensiones originales
        $anchoOriginal = imagesx($imagenOriginal);
        $altoOriginal = imagesy($imagenOriginal);
        
        // Calcular dimensiones para thumbnail (manteniendo aspect ratio)
        $ratio = min(self::THUMBNAIL_SIZE / $anchoOriginal, self::THUMBNAIL_SIZE / $altoOriginal);
        $nuevoAncho = (int)($anchoOriginal * $ratio);
        $nuevoAlto = (int)($altoOriginal * $ratio);
        
        // Crear imagen redimensionada
        $imagenThumbnail = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        
        // Preservar transparencia para PNG
        if ($tipo === IMAGETYPE_PNG) {
            imagealphablending($imagenThumbnail, false);
            imagesavealpha($imagenThumbnail, true);
        }
        
        // Redimensionar
        imagecopyresampled(
            $imagenThumbnail,
            $imagenOriginal,
            0, 0, 0, 0,
            $nuevoAncho, $nuevoAlto,
            $anchoOriginal, $altoOriginal
        );
        
        // Guardar thumbnail como JPEG
        $resultado = imagejpeg($imagenThumbnail, $rutaThumbnail, self::JPEG_QUALITY);
        
        // Liberar memoria
        imagedestroy($imagenOriginal);
        imagedestroy($imagenThumbnail);
        
        if (!$resultado) {
            return null;
        }
        
        return $rutaRelativa;
    }

    /**
     * Comprime una imagen si excede el tamaño máximo
     * 
     * @param string $rutaImagen Ruta completa de la imagen
     * @return bool True si se comprimió exitosamente
     */
    public static function comprimirImagen(string $rutaImagen): bool
    {
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        $tamañoArchivo = filesize($rutaImagen);
        
        // Si ya está bajo el límite, no hacer nada
        if ($tamañoArchivo <= self::MAX_FILE_SIZE) {
            return true;
        }
        
        // Obtener información de la imagen
        $info = getimagesize($rutaImagen);
        if ($info === false) {
            return false;
        }
        
        $tipo = $info[2];
        
        // Solo comprimir formatos soportados
        if (!in_array($tipo, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP])) {
            return false;
        }
        
        // Crear recurso de imagen
        $imagen = self::crearRecursoImagen($rutaImagen, $tipo);
        if ($imagen === false) {
            return false;
        }
        
        // Intentar con diferentes calidades hasta cumplir el límite
        $calidad = self::JPEG_QUALITY;
        $tempFile = tempnam(sys_get_temp_dir(), 'img_compress_');
        
        while ($calidad >= 50) {
            imagejpeg($imagen, $tempFile, $calidad);
            
            if (filesize($tempFile) <= self::MAX_FILE_SIZE) {
                // Copiar archivo comprimido sobre el original
                copy($tempFile, $rutaImagen);
                imagedestroy($imagen);
                unlink($tempFile);
                return true;
            }
            
            $calidad -= 5;
        }
        
        imagedestroy($imagen);
        unlink($tempFile);
        
        return false;
    }

    /**
     * Crea un recurso de imagen según el tipo
     * 
     * @param string $ruta Ruta de la imagen
     * @param int $tipo Tipo de imagen (constante IMAGETYPE_*)
     * @return resource|false Recurso de imagen o false si falla
     */
    private static function crearRecursoImagen(string $ruta, int $tipo)
    {
        return match ($tipo) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => imagecreatefromwebp($ruta),
            IMAGETYPE_GIF => imagecreatefromgif($ruta),
            default => false,
        };
    }

    /**
     * Convierte una imagen HEIC a JPEG
     * 
     * @param string $rutaHeic Ruta de la imagen HEIC
     * @return string|null Ruta de la imagen JPEG convertida
     */
    public static function convertirHeicAJpeg(string $rutaHeic): ?string
    {
        // Nota: La conversión HEIC requiere la librería heic2any o similar
        // Esta es una implementación básica que podría necesitar extensión adicional
        
        if (!file_exists($rutaHeic)) {
            return null;
        }
        
        // Verificar si hay una herramienta disponible
        if (!self::tieneSoporteHeic()) {
            return null;
        }
        
        $rutaJpeg = preg_replace('/\.heic$/i', '.jpg', $rutaHeic);
        
        // Usar comando externo si está disponible (heif-convert)
        $comando = sprintf('heif-convert %s %s 2>&1', escapeshellarg($rutaHeic), escapeshellarg($rutaJpeg));
        exec($comando, $output, $retorno);
        
        if ($retorno !== 0 || !file_exists($rutaJpeg)) {
            return null;
        }
        
        return $rutaJpeg;
    }

    /**
     * Verifica si el sistema tiene soporte para HEIC
     */
    public static function tieneSoporteHeic(): bool
    {
        // Verificar si existe heif-convert o similar
        exec('which heif-convert', $output, $retorno);
        return $retorno === 0;
    }

    /**
     * Obtiene las dimensiones de una imagen
     * 
     * @param string $rutaImagen Ruta de la imagen
     * @return array|null ['ancho' => int, 'alto' => int] o null si falla
     */
    public static function obtenerDimensiones(string $rutaImagen): ?array
    {
        $info = getimagesize($rutaImagen);
        if ($info === false) {
            return null;
        }
        
        return [
            'ancho' => $info[0],
            'alto' => $info[1],
        ];
    }

    /**
     * Valida si un archivo es una imagen válida
     * 
     * @param string $rutaImagen Ruta de la imagen
     * @return bool True si es una imagen válida
     */
    public static function validarImagen(string $rutaImagen): bool
    {
        if (!file_exists($rutaImagen)) {
            return false;
        }
        
        $info = @getimagesize($rutaImagen);
        return $info !== false;
    }
}
