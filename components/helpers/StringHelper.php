<?php

declare(strict_types=1);

namespace app\components\helpers;

/**
 * Helper para normalización, sanitización y manipulación de cadenas de texto.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class StringHelper
{
    /**
     * Genera un slug URL-friendly a partir de una cadena.
     * Ejemplo: "Taller Smart Pro!" → "taller-smart-pro"
     *
     * @param string $texto     Texto de entrada.
     * @param string $separador Separador de palabras (default: '-').
     */
    public static function slug(string $texto, string $separador = '-'): string
    {
        // Transliterar caracteres acentuados
        $texto = self::quitarAcentos($texto);
        // Convertir a minúsculas
        $texto = strtolower($texto);
        // Reemplazar espacios y caracteres no alfanuméricos por separador
        $texto = preg_replace('/[^a-z0-9]+/', $separador, $texto) ?? '';
        // Eliminar separadores al inicio y final
        return trim($texto, $separador);
    }

    /**
     * Elimina acentos y caracteres diacríticos del español.
     * Ejemplo: "José García" → "Jose Garcia"
     *
     * @param string $texto  Texto con posibles caracteres acentuados.
     */
    public static function quitarAcentos(string $texto): string
    {
        $buscar     = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ü','Ü','à','è','ì','ò','ù'];
        $reemplazar = ['a','e','i','o','u','A','E','I','O','U','n','N','u','U','a','e','i','o','u'];
        return str_replace($buscar, $reemplazar, $texto);
    }

    /**
     * Trunca una cadena a la longitud máxima indicada, añadiendo sufijo.
     * Ejemplo: truncar("Hola Mundo", 7) → "Hola..."
     *
     * @param string $texto   Texto de entrada.
     * @param int    $max     Longitud máxima (incluyendo sufijo).
     * @param string $sufijo  Sufijo a agregar cuando se trunca.
     */
    public static function truncar(string $texto, int $max = 100, string $sufijo = '...'): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        return mb_substr($texto, 0, $max - mb_strlen($sufijo)) . $sufijo;
    }

    /**
     * Sanitiza una cadena eliminando etiquetas HTML y caracteres peligrosos.
     * Apto para mostrar texto de usuario en HTML.
     *
     * @param string $texto  Texto a sanitizar.
     */
    public static function sanitizar(string $texto): string
    {
        return htmlspecialchars(strip_tags(trim($texto)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Normaliza una cadena para comparación: minúsculas, sin acentos, sin espacios dobles.
     * Útil para búsquedas insensibles a mayúsculas y acentos.
     *
     * @param string $texto  Texto a normalizar.
     */
    public static function normalizar(string $texto): string
    {
        $texto = self::quitarAcentos($texto);
        $texto = strtolower($texto);
        return (string) preg_replace('/\s+/', ' ', trim($texto));
    }

    /**
     * Verifica si una cadena está vacía (null, '', o solo espacios).
     *
     * @param string|null $texto  Texto a verificar.
     */
    public static function esVacio(?string $texto): bool
    {
        return $texto === null || trim($texto) === '';
    }

    /**
     * Convierte la primera letra de cada palabra a mayúsculas correctamente en UTF-8.
     * Ejemplo: "JUAN PÉREZ" → "Juan Pérez"
     *
     * @param string $texto  Texto en cualquier capitalizacion.
     */
    public static function nombrePropio(string $texto): string
    {
        return mb_convert_case(mb_strtolower($texto), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Extrae solo dígitos de una cadena.
     * Ejemplo: "(+56) 9 1234-5678" → "5691234567"
     *
     * @param string $texto  Cadena con mezcla de caracteres.
     */
    public static function soloDigitos(string $texto): string
    {
        return preg_replace('/\D/', '', $texto) ?? '';
    }

    /**
     * Genera un código alfanumérico aleatorio en mayúsculas.
     * Útil para tokens, códigos de referencia, etc.
     *
     * @param int $longitud  Longitud del código (default: 8).
     */
    public static function codigoAleatorio(int $longitud = 8): string
    {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $resultado  = '';
        $max        = strlen($caracteres) - 1;
        for ($i = 0; $i < $longitud; $i++) {
            $resultado .= $caracteres[random_int(0, $max)];
        }
        return $resultado;
    }

    /**
     * Enmascara parcialmente un string sensible (ej: email, tarjeta).
     * Ejemplo: mascarar("correo@test.com", 3, 4) → "cor***@test.com"
     *
     * @param string $texto           Texto a enmascarar.
     * @param int    $visibleInicio   Caracteres visibles al inicio.
     * @param int    $visibleFin      Caracteres visibles al final.
     * @param string $mascara         Carácter de máscara.
     */
    public static function enmascarar(string $texto, int $visibleInicio = 3, int $visibleFin = 0, string $mascara = '*'): string
    {
        $longitud = mb_strlen($texto);
        if ($longitud <= $visibleInicio + $visibleFin) {
            return str_repeat($mascara, $longitud);
        }
        $inicio  = mb_substr($texto, 0, $visibleInicio);
        $fin     = $visibleFin > 0 ? mb_substr($texto, -$visibleFin) : '';
        $ocultos = $longitud - $visibleInicio - $visibleFin;
        return $inicio . str_repeat($mascara, $ocultos) . $fin;
    }
}
