<?php

declare(strict_types=1);

namespace app\components\helpers;

/**
 * Helper para formateo de valores de negocio: moneda, teléfono, patente, VIN, RUT.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class FormatHelper
{
    /** Moneda por defecto (ISO 4217) */
    public const MONEDA_DEFAULT = 'CLP';
    /** Separador de miles */
    public const SEP_MILES      = '.';
    /** Separador decimal */
    public const SEP_DECIMAL    = ',';
    /** Clave del parámetro para decimales de moneda */
    private const PARAM_DECIMALES = 'formato.moneda.decimales';
    /** Decimales por defecto si no está configurado */
    private const DECIMALES_DEFAULT = 0;

    // ─── Moneda ───────────────────────────────────────────────────────────────

    /**
     * Formatea un número como moneda CLP.
     * Ejemplo: 150000 → "$ 150.000"
     *
     * @param int|float|null $monto   Monto a formatear.
     * @param int|null       $decimales Cantidad de decimales (null usa parámetro del sistema).
     * @param string         $prefijo   Prefijo de moneda.
     */
    public static function moneda(int|float|null $monto, ?int $decimales = null, string $prefijo = '$ '): string
    {
        if ($monto === null) {
            return $prefijo . '0';
        }
        if ($decimales === null) {
            $decimales = \app\models\ParametroSistema::getValor(self::PARAM_DECIMALES, self::DECIMALES_DEFAULT);
        }
        return $prefijo . number_format((float) $monto, $decimales, self::SEP_DECIMAL, self::SEP_MILES);
    }

    /**
     * Formatea moneda sin prefijo, solo el número con separadores.
     */
    public static function numero(int|float|null $valor, ?int $decimales = null): string
    {
        if ($decimales === null) {
            $decimales = \app\models\ParametroSistema::getValor(self::PARAM_DECIMALES, self::DECIMALES_DEFAULT);
        }
        return number_format((float) ($valor ?? 0), $decimales, self::SEP_DECIMAL, self::SEP_MILES);
    }

    // ─── Teléfono ─────────────────────────────────────────────────────────────

    /**
     * Formatea un número de teléfono chileno.
     * Ejemplo: "56912345678" → "+56 9 1234 5678"
     *          "912345678"   → "+56 9 1234 5678"
     *
     * @param string|null $telefono  Número de teléfono (dígitos).
     * @return string
     */
    public static function telefono(?string $telefono): string
    {
        if (empty($telefono)) {
            return '';
        }

        // Eliminar caracteres no numéricos
        $digitos = preg_replace('/\D/', '', $telefono) ?? '';

        // Normalizar: quitar prefijo 56 o 0
        if (str_starts_with($digitos, '56') && strlen($digitos) === 11) {
            $digitos = substr($digitos, 2);
        } elseif (str_starts_with($digitos, '0')) {
            $digitos = ltrim($digitos, '0');
        }

        // Celular: 9 dígitos comenzando en 9
        if (strlen($digitos) === 9 && str_starts_with($digitos, '9')) {
            return sprintf('+56 %s %s %s', $digitos[0], substr($digitos, 1, 4), substr($digitos, 5));
        }

        // Fijo: 8 o 9 dígitos
        if (strlen($digitos) === 8) {
            return sprintf('+56 2 %s %s', substr($digitos, 0, 4), substr($digitos, 4));
        }

        return $telefono; // Retornar original si no coincide
    }

    /**
     * Valida si un teléfono chileno tiene formato válido.
     */
    public static function esValidoTelefono(?string $telefono): bool
    {
        if (empty($telefono)) {
            return false;
        }
        $digitos = preg_replace('/\D/', '', $telefono) ?? '';
        if (str_starts_with($digitos, '56')) {
            $digitos = substr($digitos, 2);
        }
        return preg_match('/^[29]\d{8}$/', $digitos) === 1;
    }

    // ─── Patente ──────────────────────────────────────────────────────────────

    /**
     * Normaliza una patente chilena a formato estándar mayúsculas sin guión.
     * Ejemplo: "ab-cd-12" → "ABCD12"
     *
     * @param string|null $patente  Patente a normalizar.
     */
    public static function patente(?string $patente): string
    {
        if (empty($patente)) {
            return '';
        }
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $patente) ?? '');
    }

    /**
     * Formatea patente con guión: "ABCD12" → "ABCD-12" o "AB1234" → "AB-1234".
     */
    public static function patenteFormateada(?string $patente): string
    {
        $limpia = self::patente($patente);

        if (strlen($limpia) === 6) {
            // Formato nuevo: LLDDDD → LL-DDDD (ej: AB1234)
            if (ctype_alpha(substr($limpia, 0, 2)) && ctype_digit(substr($limpia, 2))) {
                return substr($limpia, 0, 2) . '-' . substr($limpia, 2);
            }
            // Formato antiguo: LLLLDD → LLLL-DD (ej: ABCD12)
            if (ctype_alpha(substr($limpia, 0, 4)) && ctype_digit(substr($limpia, 4))) {
                return substr($limpia, 0, 4) . '-' . substr($limpia, 4);
            }
        }

        return $limpia;
    }

    /**
     * Valida si una patente chilena tiene formato válido.
     */
    public static function esValidaPatente(?string $patente): bool
    {
        if (empty($patente)) {
            return false;
        }
        $limpia = self::patente($patente);
        // Formato nuevo: 2 letras + 4 dígitos (ej: AB1234)
        if (preg_match('/^[A-Z]{2}\d{4}$/', $limpia)) {
            return true;
        }
        // Formato antiguo: 4 letras + 2 dígitos (ej: ABCD12)
        if (preg_match('/^[A-Z]{4}\d{2}$/', $limpia)) {
            return true;
        }
        return false;
    }

    // ─── RUT (Chile) ──────────────────────────────────────────────────────────

    /**
     * Formatea un RUT chileno: "12345678k" → "12.345.678-K".
     *
     * @param string|null $rut  RUT sin formato.
     */
    public static function rut(?string $rut): string
    {
        if (empty($rut)) {
            return '';
        }
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');
        if (strlen($limpio) < 2) {
            return $rut;
        }
        $cuerpo = substr($limpio, 0, -1);
        $dv     = substr($limpio, -1);
        $formateado = number_format((int) $cuerpo, 0, ',', '.');
        return $formateado . '-' . $dv;
    }

    /**
     * Valida dígito verificador de RUT chileno.
     */
    public static function esValidoRut(?string $rut): bool
    {
        if (empty($rut)) {
            return false;
        }
        $limpio = strtoupper(preg_replace('/[^0-9kK]/', '', $rut) ?? '');
        if (strlen($limpio) < 2) {
            return false;
        }
        $cuerpo = (int) substr($limpio, 0, -1);
        $dvIngresado = substr($limpio, -1);

        $suma   = 0;
        $factor = 2;
        $num    = $cuerpo;
        while ($num > 0) {
            $suma  += ($num % 10) * $factor;
            $num    = (int) ($num / 10);
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $dvCalculado = 11 - ($suma % 11);
        $dvReal = match ($dvCalculado) {
            11 => '0',
            10 => 'K',
            default => (string) $dvCalculado,
        };

        return $dvIngresado === $dvReal;
    }

    // ─── VIN ──────────────────────────────────────────────────────────────────

    /**
     * Valida un VIN (Vehicle Identification Number) estándar ISO 3779.
     * 17 caracteres alfanuméricos (sin I, O, Q).
     */
    public static function esValidoVin(?string $vin): bool
    {
        if (empty($vin)) {
            return false;
        }
        return preg_match('/^[A-HJ-NPR-Z0-9]{17}$/i', $vin) === 1;
    }

    /**
     * Normaliza un VIN a mayúsculas sin espacios.
     */
    public static function vin(?string $vin): string
    {
        if (empty($vin)) {
            return '';
        }
        return strtoupper(preg_replace('/\s+/', '', $vin) ?? '');
    }

    // ─── Códigos internos ─────────────────────────────────────────────────────

    /**
     * Genera código de Orden de Servicio: JOB-YYYYMMDD-XXXX.
     *
     * @param int $secuencia  Número secuencial (ej: 42).
     * @param int $timestamp  Timestamp de la fecha (default: now).
     */
    public static function codigoJob(int $secuencia, int $timestamp = 0): string
    {
        $ts   = $timestamp ?: time();
        $fecha = date('Ymd', $ts);
        return sprintf('JOB-%s-%04d', $fecha, $secuencia);
    }
}
