<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Vehiculo. Mapea la tabla {{%vehiculo}}.
 * Soporta validación de patente chilena (formato antiguo AB-1234 y nuevo ABCD-12).
 *
 * @property int         $id
 * @property string      $patente
 * @property string      $marca
 * @property string      $modelo
 * @property int|null    $marca_id
 * @property int|null    $modelo_id
 * @property int         $anio
 * @property string|null $vin
 * @property int         $cliente_id
 * @property int         $ultimo_km
 * @property string|null $foto_path
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 * @property int|null    $ultima_mantencion_at
 *
 * @property-read Cliente          $cliente
 * @property-read Marca            $marcaModel
 * @property-read Modelo           $modeloModel
 * @property-read Cita[]           $citas
 * @property-read OrdenServicio[]  $ordenes
 */
class Vehiculo extends ActiveRecord
{
    private static ?string $resolvedTableName = null;

    public static function tableName(): string
    {
        if (self::$resolvedTableName !== null) {
            return self::$resolvedTableName;
        }

        $default = '{{%vehiculo}}';
        $legacy  = '{{%vehiculos}}';

        try {
            $db = Yii::$app->get('db', false);
            if ($db === null) {
                self::$resolvedTableName = $default;
                return self::$resolvedTableName;
            }

            $schema  = $db->schema;
            $rawMain = $schema->getRawTableName($default);
            $rawAlt  = $schema->getRawTableName($legacy);

            if ($schema->getTableSchema($rawMain, true) !== null) {
                self::$resolvedTableName = $default;
                return self::$resolvedTableName;
            }
            if ($schema->getTableSchema($rawAlt, true) !== null) {
                self::$resolvedTableName = $legacy;
                return self::$resolvedTableName;
            }
        } catch (\Throwable) {
            // En bootstrap temprano o conexión caída: mantener nombre por defecto.
        }

        self::$resolvedTableName = $default;
        return self::$resolvedTableName;
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
            [['patente', 'marca', 'modelo', 'anio', 'cliente_id'], 'required'],
            ['patente',    'string',  'max' => 10],
            ['patente',    'unique'],
            ['patente',    'validatePatente'],
            ['marca',      'string',  'max' => 60],
            ['modelo',     'string',  'max' => 60],
            ['marca_id',   'integer', 'min' => 1],
            ['modelo_id',  'integer', 'min' => 1],
            ['marca_id',   'exist', 'skipOnError' => true, 'targetClass' => \app\models\Marca::class, 'targetAttribute' => 'id'],
            ['modelo_id',  'exist', 'skipOnError' => true, 'targetClass' => \app\models\Modelo::class, 'targetAttribute' => 'id'],
            ['anio',       'integer', 'min' => 1900, 'max' => (int) date('Y') + 1],
            ['vin',        'string',  'max' => 17],
            ['vin',        'match',   'pattern' => '/^[A-HJ-NPR-Z0-9]{17}$/i', 'skipOnEmpty' => true,
                'message' => 'El VIN debe tener 17 caracteres alfanuméricos (sin I, O ni Q).'],
            ['cliente_id', 'integer'],
            ['cliente_id', 'exist', 'targetClass' => Cliente::class, 'targetAttribute' => 'id'],
            ['ultimo_km',  'integer', 'min' => 0],
            ['foto_path',  'string',  'max' => 255],
            ['status',     'boolean'],
            ['status',     'default', 'value' => 1],
            ['ultima_mantencion_at', 'integer', 'min' => 0],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                   => 'ID',
            'patente'              => 'Patente',
            'marca'                => 'Marca',
            'modelo'               => 'Modelo',
            'marca_id'             => 'Marca',
            'modelo_id'            => 'Modelo',
            'anio'                 => 'Año',
            'vin'                  => 'VIN',
            'cliente_id'           => 'Propietario',
            'ultimo_km'            => 'Último KM',
            'foto_path'            => 'Foto',
            'status'               => 'Estado',
            'created_at'           => 'Creado',
            'updated_at'           => 'Actualizado',
            'ultima_mantencion_at' => 'Última Mantención',
        ];
    }

    /** Normaliza la patente a mayúsculas antes de validar. */
    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->patente !== null) {
            $this->patente = strtoupper(trim((string) $this->patente));
        }
        return true;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->patente !== null) {
            $this->patente = strtoupper(trim($this->patente));
        }
        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /**
     * Valida patente chilena.
     * Formato antiguo: AB-1234 o AB1234 (2 letras + 4 dígitos).
     * Formato nuevo:   ABCD-12 o ABCD12 (4 letras + 2 dígitos).
     */
    public function validatePatente(string $attribute): void
    {
        $patente = $this->$attribute;
        if (empty($patente)) {
            return;
        }
        // Eliminar guión para normalizar
        $limpia = str_replace('-', '', $patente);
        $esAntigua = (bool) preg_match('/^[A-Z]{2}\d{4}$/', $limpia);
        $esNueva   = (bool) preg_match('/^[A-Z]{4}\d{2}$/', $limpia);
        if (!$esAntigua && !$esNueva) {
            $this->addError($attribute, 'Patente inválida. Formatos: AB-1234 (antiguo) o ABCD-12 (nuevo).');
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getMarcaModel(): \yii\db\ActiveQuery
    {
        return $this->hasOne(\app\models\Marca::class, ['id' => 'marca_id']);
    }

    public function getModeloModel(): \yii\db\ActiveQuery
    {
        return $this->hasOne(\app\models\Modelo::class, ['id' => 'modelo_id']);
    }

    public function getCitas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cita::class, ['vehiculo_id' => 'id']);
    }

    public function getOrdenes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicio::class, ['vehiculo_id' => 'id']);
    }

    /**
     * Retorna la última cita registrada del vehículo.
     */
    public function getUltimaCita(): ?Cita
    {
        return $this->getCitas()
            ->andWhere(['not in', 'estado', ['cancelada', 'no_show']])
            ->andWhere(['<=', 'fecha', date('Y-m-d')])
            ->orderBy(['fecha' => SORT_DESC, 'hora_inicio' => SORT_DESC])
            ->one();
    }

    /**
     * Retorna la próxima cita programada del vehículo.
     */
    public function getProximaCita(): ?Cita
    {
        return $this->getCitas()
            ->andWhere(['not in', 'estado', ['cancelada', 'no_show', 'completada']])
            ->andWhere(['>=', 'fecha', date('Y-m-d')])
            ->orderBy(['fecha' => SORT_ASC, 'hora_inicio' => SORT_ASC])
            ->one();
    }

    /**
     * Retorna el color Bootstrap según el estado del vehículo.
     */
    public function getColorEstado(): string
    {
        return $this->status ? 'success' : 'secondary';
    }

    /**
     * Calcula los días transcurridos desde la última mantención (HU-007)
     * @return int|null Días desde la última mantención o null si no hay registro
     */
    public function getDiasDesdeUltimaMantencion(): ?int
    {
        if ($this->ultima_mantencion_at === null || $this->ultima_mantencion_at <= 0) {
            return null;
        }
        
        $now = time();
        $segundosPorDia = 86400;
        return (int) (($now - $this->ultima_mantencion_at) / $segundosPorDia);
    }

    /**
     * Verifica si el vehículo necesita mantención (HU-007)
     * @param int $diasIntervalo Intervalo de mantención en días (por defecto 90 días)
     * @return bool True si necesita mantención
     */
    public function necesitaMantencion(int $diasIntervalo = 90): bool
    {
        $diasTranscurridos = $this->getDiasDesdeUltimaMantencion();
        if ($diasTranscurridos === null) {
            return false; // No hay registro de mantención previa
        }
        return $diasTranscurridos >= $diasIntervalo;
    }

    /**
     * Obtiene los días restantes para la próxima mantención (HU-007)
     * @param int $diasIntervalo Intervalo de mantención en días (por defecto 90 días)
     * @return int|null Días restantes o null si no hay registro
     */
    public function getDiasRestantesMantencion(int $diasIntervalo = 90): ?int
    {
        $diasTranscurridos = $this->getDiasDesdeUltimaMantencion();
        if ($diasTranscurridos === null) {
            return null;
        }
        return $diasIntervalo - $diasTranscurridos;
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    public static function getEstadosList(): array
    {
        return ['1' => 'Activo', '0' => 'Inactivo'];
    }
}
