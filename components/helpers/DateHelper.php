<?php

declare(strict_types=1);

namespace app\components\helpers;

use Yii;

/**
 * Helper para formateo y cálculo de fechas según el locale y timezone del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class DateHelper
{
    /** Formato de fecha corta (ej: 01/05/2026) */
    public const FORMATO_FECHA       = 'd/m/Y';
    /** Formato de fecha-hora (ej: 01/05/2026 14:30) */
    public const FORMATO_FECHA_HORA  = 'd/m/Y H:i';
    /** Formato de hora (ej: 14:30) */
    public const FORMATO_HORA        = 'H:i';
    /** Formato ISO 8601 para interoperabilidad */
    public const FORMATO_ISO         = 'Y-m-d';

    /**
     * Retorna el timezone configurado en ParametroSistema o el del sistema.
     */
    public static function getTimezone(): string
    {
        try {
            $tz = \app\models\ParametroSistema::getValor('timezone');
            return $tz ?: date_default_timezone_get();
        } catch (\Throwable) {
            return date_default_timezone_get();
        }
    }

    /**
     * Formatea un timestamp Unix o string de fecha al formato local del taller.
     *
     * @param int|string|null $fecha     Timestamp Unix, string fecha o null.
     * @param string          $formato   Formato de salida (constantes de esta clase).
     * @return string  Fecha formateada o cadena vacía si es null/inválida.
     */
    public static function formatear(int|string|null $fecha, string $formato = self::FORMATO_FECHA): string
    {
        if ($fecha === null || $fecha === '' || $fecha === 0) {
            return '';
        }

        $tz = new \DateTimeZone(self::getTimezone());

        if (is_int($fecha)) {
            $dt = new \DateTime('@' . $fecha);
            $dt->setTimezone($tz);
        } else {
            try {
                $dt = new \DateTime($fecha, $tz);
            } catch (\Throwable) {
                return '';
            }
        }

        return $dt->format($formato);
    }

    /**
     * Formatea un timestamp Unix a fecha corta (d/m/Y).
     */
    public static function fecha(int|string|null $timestamp): string
    {
        return self::formatear($timestamp, self::FORMATO_FECHA);
    }

    /**
     * Formatea un timestamp Unix a fecha y hora (d/m/Y H:i).
     */
    public static function fechaHora(int|string|null $timestamp): string
    {
        return self::formatear($timestamp, self::FORMATO_FECHA_HORA);
    }

    /**
     * Retorna la fecha/hora actual como timestamp Unix.
     */
    public static function ahora(): int
    {
        return time();
    }

    /**
     * Retorna diferencia legible entre ahora y un timestamp pasado.
     * Ejemplo: "hace 3 días", "hace 2 horas".
     *
     * @param int $timestamp  Timestamp Unix pasado.
     * @return string
     */
    public static function tiempoTranscurrido(int $timestamp): string
    {
        $diff = time() - $timestamp;

        return match (true) {
            $diff < 60     => 'hace un momento',
            $diff < 3600   => 'hace ' . floor($diff / 60)   . ' min',
            $diff < 86400  => 'hace ' . floor($diff / 3600)  . ' h',
            $diff < 604800 => 'hace ' . floor($diff / 86400) . ' días',
            default        => self::fecha($timestamp),
        };
    }

    /**
     * Convierte una fecha en formato d/m/Y a Y-m-d para almacenamiento.
     *
     * @param string $fecha  Fecha en formato d/m/Y.
     * @return string|null   Fecha en formato Y-m-d o null si inválida.
     */
    public static function aFormatoDb(string $fecha): ?string
    {
        $dt = \DateTime::createFromFormat(self::FORMATO_FECHA, $fecha);
        return $dt !== false ? $dt->format(self::FORMATO_ISO) : null;
    }

    /**
     * Convierte una fecha Y-m-d a formato de visualización d/m/Y.
     *
     * @param string|null $fecha  Fecha en formato Y-m-d.
     * @return string
     */
    public static function deFormatoDb(?string $fecha): string
    {
        if (empty($fecha)) {
            return '';
        }
        $dt = \DateTime::createFromFormat(self::FORMATO_ISO, $fecha);
        return $dt !== false ? $dt->format(self::FORMATO_FECHA) : $fecha;
    }

    /**
     * Verifica si una cadena es una fecha válida en el formato indicado.
     *
     * @param string $fecha    Cadena a validar.
     * @param string $formato  Formato esperado.
     */
    public static function esValida(string $fecha, string $formato = self::FORMATO_FECHA): bool
    {
        $dt = \DateTime::createFromFormat($formato, $fecha);
        return $dt !== false && $dt->format($formato) === $fecha;
    }
}
