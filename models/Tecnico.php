<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Tecnico. Mapea la tabla {{%tecnico}}.
 * Incluye validación de RUT chileno (mismo algoritmo que Cliente).
 *
 * @property int         $id
 * @property string      $nombre
 * @property string      $apellido
 * @property string|null $rut
 * @property string|null $email
 * @property string|null $telefono
 * @property int|null    $especialidad_id
 * @property float       $costo_hora
 * @property string|null $foto_path
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Especialidad    $especialidad
 * @property-read Certificacion[] $certificaciones
 */
class Tecnico extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%tecnico}}';
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
            [['nombre', 'apellido'], 'required'],
            ['nombre',          'string',  'max' => 100],
            ['apellido',        'string',  'max' => 100],
            ['rut',             'string',  'max' => 15],
            ['rut',             'validateRut'],
            ['rut',             'unique', 'skipOnEmpty' => true, 'message' => 'El RUT ya está registrado para otro técnico.'],
            ['email',           'email'],
            ['email',           'string',  'max' => 150],
            ['email',           'unique'],
            ['telefono',        'string',  'max' => 25],
            ['especialidad_id', 'integer'],
            ['especialidad_id', 'exist', 'targetClass' => Especialidad::class,
                'targetAttribute' => 'id', 'skipOnEmpty' => true],
            ['costo_hora',      'number', 'min' => 0],
            ['foto_path',       'string', 'max' => 255],
            ['status',          'boolean'],
            ['status',          'default', 'value' => 1],
        ];
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if (!empty($this->rut)) {
            $this->rut = $this->normalizarRut((string) $this->rut);
        }

        return true;
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'nombre'          => 'Nombre',
            'apellido'        => 'Apellido',
            'rut'             => 'RUT',
            'email'           => 'Correo Electrónico',
            'telefono'        => 'Teléfono',
            'especialidad_id' => 'Especialidad',
            'costo_hora'      => 'Costo por Hora',
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
        if ($this->email !== null) {
            $this->email = strtolower(trim($this->email));
        }
        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /**
     * Valida el RUT chileno (igual que en Cliente).
     */
    public function validateRut(string $attribute): void
    {
        $rut = $this->$attribute;
        if (empty($rut)) {
            return;
        }
        $rut = str_replace(['.', ' '], '', $rut);
        $rut = strtoupper($rut);
        if (!preg_match('/^(\d{1,8})-?([\dK])$/', $rut, $m)) {
            $this->addError($attribute, 'El RUT ingresado no es válido.');
            return;
        }
        if (!$this->calcularDvRut((int) $m[1], $m[2])) {
            $this->addError($attribute, 'El dígito verificador del RUT no es correcto.');
        }
    }

    private function calcularDvRut(int $numero, string $dv): bool
    {
        $suma   = 0;
        $factor = 2;
        $n      = $numero;
        while ($n > 0) {
            $suma  += ($n % 10) * $factor;
            $n      = (int) ($n / 10);
            $factor = $factor === 7 ? 2 : $factor + 1;
        }
        $resto  = 11 - ($suma % 11);
        $dvCalc = match ($resto) {
            11      => '0',
            10      => 'K',
            default => (string) $resto,
        };
        return $dvCalc === $dv;
    }

    private function normalizarRut(string $rut): string
    {
        $rut = str_replace(['.', ' '], '', $rut);
        $rut = strtoupper(trim($rut));

        if (preg_match('/^(\d{1,8})-?([\dK])$/', $rut, $m)) {
            return $m[1] . '-' . $m[2];
        }

        return $rut;
    }

    // ── Métodos de negocio ────────────────────────────────────────────────────

    public function getFullName(): string
    {
        return trim($this->nombre . ' ' . $this->apellido);
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getEspecialidad(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Especialidad::class, ['id' => 'especialidad_id']);
    }

    public function getCertificaciones(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Certificacion::class, ['tecnico_id' => 'id'])
            ->orderBy(['fecha_emision' => SORT_DESC]);
    }

    public static function getEstadosList(): array
    {
        return ['1' => 'Activo', '0' => 'Inactivo'];
    }
}
