<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;
use app\models\Vehiculo;
use app\models\OrdenServicio;
use app\models\Cita;
use app\models\Pago;
use app\models\ClienteEtiqueta;

/**
 * Modelo Cliente. Mapea la tabla {{%cliente}}.
 * Incluye validación de RUT chileno y normalización de nombre/email.
 * Soporta identificación con RUN o PASAPORTE.
 *
 * @property int         $id
 * @property string      $nombre
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $direccion
 * @property string|null $rut
 * @property string|null $tipo_identificacion
 * @property string|null $identificacion_alternativa
 * @property string|null $cumpleanos
 * @property string|null $fuente
 * @property string|null $preferencias
 * @property int         $status
 * @property string|null $notas
 * @property int|null    $created_at
 * @property int|null    $updated_at
 */
class Cliente extends ActiveRecord
{
    // Compatibilidad con esquemas que aun no incluyen estas columnas en BD.
    public ?string $tipo_identificacion = 'RUN';
    public ?string $identificacion_alternativa = null;
    public ?string $cumpleanos = null;
    public ?string $fuente = null;
    public ?string $preferencias = null;

    public static function tableName(): string
    {
        return '{{%cliente}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['nombre', 'email'], 'required'],
            ['nombre', 'string', 'max' => 150],
            ['nombre', 'match', 'pattern' => '/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/u', 'message' => 'El nombre solo debe contener letras y espacios.'],
            ['email', 'email'],
            ['email', 'string', 'max' => 150],
            ['email', 'unique'],
            ['telefono', 'string', 'max' => 25],
            ['telefono', 'validateTelefono'],
            ['direccion', 'string'],
            ['rut', 'string', 'max' => 15],
            ['rut', 'validateRut'],
            ['tipo_identificacion', 'in', 'range' => ['RUN', 'PASAPORTE']],
            ['tipo_identificacion', 'default', 'value' => 'RUN'],
            ['identificacion_alternativa', 'string', 'max' => 50],
            ['identificacion_alternativa', 'validateIdentificacionAlternativa'],
            ['notas', 'string'],
            ['status', 'boolean'],
            ['status', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                         => 'ID',
            'nombre'                     => 'Nombre',
            'email'                      => 'Correo Electrónico',
            'telefono'                   => 'Teléfono',
            'direccion'                  => 'Dirección',
            'rut'                        => 'RUT',
            'tipo_identificacion'        => 'Tipo de Identificación',
            'identificacion_alternativa' => 'Número de Identificación',
            'cumpleanos'                 => 'Cumpleaños',
            'fuente'                     => 'Fuente de Contacto',
            'preferencias'               => 'Preferencias',
            'status'                     => 'Estado',
            'notas'                      => 'Notas',
            'created_at'                 => 'Creado',
            'updated_at'                 => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        // Normalizar email a minúsculas, con fallback cuando mbstring no está disponible.
        if ($this->email !== null) {
            $email = trim((string) $this->email);
            $this->email = function_exists('mb_strtolower')
                ? mb_strtolower($email, 'UTF-8')
                : strtolower($email);
        }

        // Normalizar nombre preservando acentos si mbstring está disponible.
        $nombre = trim((string) $this->nombre);
        $this->nombre = (function_exists('mb_convert_case') && function_exists('mb_strtolower'))
            ? mb_convert_case(mb_strtolower($nombre, 'UTF-8'), MB_CASE_TITLE, 'UTF-8')
            : ucwords(strtolower($nombre));

        if ($this->telefono !== null && $this->telefono !== '') {
            $this->telefono = trim(preg_replace('/\s+/', ' ', $this->telefono) ?? $this->telefono);
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /**
     * Valida formato internacional de teléfono: +XX X XXXX XXXX.
     */
    public function validateTelefono(string $attribute): void
    {
        if (empty($this->$attribute)) {
            return;
        }

        if (!preg_match('/^\+[0-9]{1,3}\s[0-9]\s[0-9]{4}\s[0-9]{4}$/', (string) $this->$attribute)) {
            $this->addError($attribute, 'Formato de teléfono inválido. Use: +XX X XXXX XXXX');
        }
    }

    /**
     * Valida el RUT chileno (formato: 12345678-9 o 12.345.678-9).
     * Si está vacío, no valida (campo opcional).
     * Solo valida si tipo_identificacion es RUN.
     */
    public function validateRut(string $attribute): void
    {
        // Solo validar RUT si el tipo de identificación es RUN
        if ($this->tipo_identificacion !== 'RUN') {
            return;
        }
        
        $rut = $this->$attribute;
        if (empty($rut)) {
            return;
        }

        // Limpiar formato: quitar puntos y dejar solo dígitos y guión-dv
        $rut = str_replace(['.', ' '], '', $rut);
        $rut = strtoupper($rut);

        if (!preg_match('/^(\d{1,8})-?([\dK])$/', $rut, $m)) {
            $this->addError($attribute, 'El RUT ingresado no es válido.');
            return;
        }

        $numero = (int) $m[1];
        $dv     = $m[2];

        if (!$this->calcularDvRut($numero, $dv)) {
            $this->addError($attribute, 'El dígito verificador del RUT no es correcto.');
        }
    }
    
    /**
     * Valida la identificación alternativa según el tipo seleccionado.
     */
    public function validateIdentificacionAlternativa(string $attribute): void
    {
        $identificacion = $this->$attribute;
        
        if (empty($identificacion)) {
            return;
        }
        
        if ($this->tipo_identificacion === 'PASAPORTE') {
            // Validación básica de pasaporte: alfanumérico, longitud variable
            if (!preg_match('/^[A-Z0-9]{6,20}$/i', $identificacion)) {
                $this->addError($attribute, 'El número de pasaporte debe ser alfanumérico (6-20 caracteres).');
            }
        } elseif ($this->tipo_identificacion === 'RUN') {
            // Para RUN, usar la validación existente en validateRut
            // Este método se llama automáticamente si se agrega a rules()
        }
    }

    /**
     * Calcula y verifica el dígito verificador del RUT chileno.
     */
    private function calcularDvRut(int $numero, string $dv): bool
    {
        $suma    = 0;
        $factor  = 2;
        $n       = $numero;

        while ($n > 0) {
            $suma   += ($n % 10) * $factor;
            $n       = (int) ($n / 10);
            $factor  = $factor === 7 ? 2 : $factor + 1;
        }

        $resto    = 11 - ($suma % 11);
        $dvCalc   = match ($resto) {
            11      => '0',
            10      => 'K',
            default => (string) $resto,
        };

        return $dvCalc === $dv;
    }

    // ── Relaciones (futuras) ──────────────────────────────────────────────────

    /** Vehículos del cliente. */
    public function getVehiculos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Vehiculo::class, ['cliente_id' => 'id']);
    }

    /** Órdenes del cliente. */
    public function getOrdenes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['cliente_id' => 'id']);
    }

    /** Citas del cliente. */
    public function getCitas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cita::class, ['cliente_id' => 'id']);
    }

    /** Pagos asociados a órdenes del cliente. */
    public function getPagos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Pago::class, ['orden_id' => 'id'])->via('ordenes');
    }

    /** Etiquetas del cliente. */
    public function getEtiquetas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Etiqueta::class, ['id' => 'etiqueta_id'])->via('clienteEtiquetas');
    }

    /** Relaciones cliente-etiqueta. */
    public function getClienteEtiquetas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ClienteEtiqueta::class, ['cliente_id' => 'id']);
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Lista de estados para dropdowns.
     *
     * @return array<string,string>
     */
    public static function getEstadosList(): array
    {
        return ['1' => 'Activo', '0' => 'Inactivo'];
    }

    /**
     * Lista de fuentes de contacto para dropdowns.
     *
     * @return array<string,string>
     */
    public static function getFuentesList(): array
    {
        return [
            'web' => 'Sitio Web',
            'google' => 'Google',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'referido' => 'Referido por Cliente',
            'telefono' => 'Llamada Telefónica',
            'walk-in' => 'Cliente en Local',
            'otro' => 'Otro',
        ];
    }
}
