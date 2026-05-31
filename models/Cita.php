<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Cita. Mapea la tabla {{%cita}}.
 * Soporta citas con vehículo registrado o con patente temporal.
 * El cliente siempre debe estar registrado (se puede usar "Alta rápida" en el formulario).
 *
 * @property int         $id
 * @property int|null    $cliente_id
 * @property int|null    $vehiculo_id
 * @property string|null $patente_temporal
 * @property string      $fecha
 * @property string      $hora_inicio
 * @property string      $hora_fin
 * @property string      $estado
 * @property string|null $notas
 * @property int|null    $orden_servicio_id
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Cliente|null       $cliente
 * @property-read Vehiculo|null      $vehiculo
 * @property-read Servicio[]         $servicios
 * @property-read OrdenServicio|null $orden
 */
class Cita extends ActiveRecord
{
    /** @var string[] */
    public const ESTADOS = [
        'pendiente',
        'confirmada',
        'en_progreso',
        'completada',
        'cancelada',
        'no_show',
    ];

    public static function tableName(): string
    {
        return '{{%cita}}';
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
            [['fecha', 'hora_inicio', 'hora_fin'], 'required'],
            [['cliente_id', 'vehiculo_id', 'orden_servicio_id'], 'integer'],
            ['cliente_id',  'exist', 'targetClass' => Cliente::class,  'targetAttribute' => 'id', 'skipOnEmpty' => true],
            ['vehiculo_id', 'exist', 'targetClass' => Vehiculo::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true],
            ['fecha',       'date', 'format' => 'php:Y-m-d'],
            [['hora_inicio', 'hora_fin'], 'match', 'pattern' => '/^\d{2}:\d{2}(:\d{2})?$/'],
            ['estado', 'in', 'range' => self::ESTADOS],
            ['estado', 'default', 'value' => 'pendiente'],
            ['notas', 'string'],
            // Campo temporal para patente de vehículo no registrado
            [['patente_temporal'], 'string', 'max' => 50],
            // Validación: se requiere cliente_id
            [['cliente_id'], 'required', 'message' => 'Debe seleccionar un cliente. Use "Alta rápida" si no está registrado.'],
            // Validaciones cruzadas: se requiere vehiculo_id O patente temporal
            [['vehiculo_id', 'patente_temporal'], 'validateVehiculoOPatente'],
            ['hora_fin',   'validateHoras'],
            ['hora_inicio','validateSinSolapamiento'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                        => 'ID',
            'cliente_id'                => 'Cliente',
            'vehiculo_id'               => 'Vehículo',
            'patente_temporal'          => 'Patente (Temporal)',
            'fecha'                     => 'Fecha',
            'hora_inicio'               => 'Hora Inicio',
            'hora_fin'                  => 'Hora Fin',
            'estado'                    => 'Estado',
            'notas'                     => 'Notas',
            'orden_servicio_id'         => 'Orden de Servicio',
            'created_at'                => 'Creado',
            'updated_at'                => 'Actualizado',
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

    // ── Validadores personalizados ────────────────────────────────────────────

    /** Valida que hora_inicio < hora_fin. */
    public function validateHoras(string $attribute): void
    {
        if ($this->hora_inicio && $this->hora_fin) {
            if (strtotime($this->hora_inicio) >= strtotime($this->hora_fin)) {
                $this->addError($attribute, 'La hora de fin debe ser posterior a la hora de inicio.');
            }
        }
    }

    /**
     * Valida que se proporcione cliente_id O identificación temporal.
     */
    public function validateClienteOIdentificacion(string $attribute): void
    {
        if (!$this->cliente_id && empty($this->cliente_identificacion_temp)) {
            $this->addError($attribute, 'Debe seleccionar un cliente o proporcionar identificación del cliente (RUN/PASAPORTE).');
        }
    }

    /**
     * Valida que se proporcione vehiculo_id O patente temporal.
     */
    public function validateVehiculoOPatente(string $attribute): void
    {
        if (!$this->vehiculo_id && empty($this->patente_temporal)) {
            $this->addError($attribute, 'Debe seleccionar un vehículo o proporcionar la patente.');
        }
        
        // Si hay patente temporal, validar formato chileno
        if (!empty($this->patente_temporal)) {
            $patente = strtoupper(str_replace('-', '', $this->patente_temporal));
            $esAntigua = (bool) preg_match('/^[A-Z]{2}\d{4}$/', $patente);
            $esNueva   = (bool) preg_match('/^[A-Z]{4}\d{2}$/', $patente);
            
            if (!$esAntigua && !$esNueva) {
                $this->addError($attribute, 'Patente inválida. Formatos: AB-1234 (antiguo) o ABCD-12 (nuevo).');
            }
        }
    }

    /** Valida que no exista solapamiento de horario en la misma fecha. */
    public function validateSinSolapamiento(string $attribute): void
    {
        if (!$this->fecha || !$this->hora_inicio || !$this->hora_fin) {
            return;
        }
        $query = static::find()
            ->where(['fecha' => $this->fecha])
            ->andWhere(['not', ['estado' => ['cancelada', 'no_show']]])
            ->andWhere(['<', 'hora_inicio', $this->hora_fin])
            ->andWhere(['>', 'hora_fin',    $this->hora_inicio]);

        if (!$this->isNewRecord) {
            $query->andWhere(['not', ['id' => $this->id]]);
        }

        if ($query->exists()) {
            $this->addError($attribute, 'Ya existe una cita en ese horario. Por favor seleccione otro horario.');
        }
    }

    // ── Métodos de negocio ────────────────────────────────────────────────────

    /** Retorna la clase Bootstrap según el estado. */
    public function getEstadoBadgeClass(): string
    {
        return match ($this->estado) {
            'pendiente'   => 'bg-warning text-dark',
            'confirmada'  => 'bg-primary',
            'en_progreso' => 'bg-info text-dark',
            'completada'  => 'bg-success',
            'cancelada'   => 'bg-secondary',
            'no_show'     => 'bg-danger',
            default       => 'bg-light text-dark',
        };
    }

    /** Retorna etiqueta legible del estado. */
    public function getEstadoLabel(): string
    {
        return self::getEstadosList()[$this->estado] ?? $this->estado;
    }

    public static function getEstadosList(): array
    {
        return [
            'pendiente'   => 'Pendiente',
            'confirmada'  => 'Confirmada',
            'en_progreso' => 'En Progreso',
            'completada'  => 'Completada',
            'cancelada'   => 'Cancelada',
            'no_show'     => 'No Show',
        ];
    }

    /**
     * Determina si puede transicionar al estado indicado.
     */
    public function puedeTransicionarA(string $nuevoEstado): bool
    {
        $transiciones = [
            'pendiente' => ['confirmada', 'cancelada'],
            'confirmada' => ['en_progreso', 'cancelada', 'no_show'],
            'en_progreso' => ['completada'],
        ];

        return in_array($nuevoEstado, $transiciones[$this->estado] ?? [], true);
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    public function getVehiculo(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Vehiculo::class, ['id' => 'vehiculo_id']);
    }

    public function getServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Servicio::class, ['id' => 'servicio_id'])
            ->viaTable('{{%cita_servicio}}', ['cita_id' => 'id']);
    }

    public function getCitaServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CitaServicio::class, ['cita_id' => 'id']);
    }

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    // ── Métodos de cálculo ────────────────────────────────────────────────────

    /**
     * Calcula el tiempo aproximado total en minutos,
     * sumando las duraciones estimadas de los servicios asociados.
     */
    public function getTiempoAproximadoTotal(): int
    {
        $total = 0;
        foreach ($this->servicios as $servicio) {
            $total += (int) ($servicio->duracion_estimada ?? 0);
        }
        return $total;
    }

    /**
     * Retorna el tiempo aproximado total formateado como "Xh Ym".
     */
    public function getTiempoAproximadoFormateado(): string
    {
        $minutos = $this->getTiempoAproximadoTotal();
        if ($minutos < 60) {
            return "{$minutos}m";
        }
        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;
        return $resto > 0 ? "{$horas}h {$resto}m" : "{$horas}h";
    }
}
