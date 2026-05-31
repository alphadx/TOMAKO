<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo OrdenServicio. Mapea la tabla {{%orden_servicio}}.
 *
 * @property int         $id
 * @property string      $codigo
 * @property int         $cliente_id
 * @property int         $vehiculo_id
 * @property int|null    $cita_id
 * @property string      $estado
 * @property string      $prioridad
 * @property float       $total
 * @property string|null $notas_generales
 * @property int|null    $opened_at
 * @property int|null    $closed_at
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Cliente               $cliente
 * @property-read Vehiculo              $vehiculo
 * @property-read Cita|null             $cita
 * @property-read OrdenServicioDetalle[] $detalles
 * @property-read AsignacionOrden[]     $asignaciones
 * @property-read OrdenNota[]           $notas
 * @property-read OrdenEstadoLog[]      $estadoLogs
 */
class OrdenServicio extends ActiveRecord
{
    /** @var string[] */
    public const ESTADOS = [
        'abierto',
        'en_progreso',
        'esperando_repuestos',
        'listo_para_entrega',
        'entregada',
        'cancelada',
    ];

    /** Constantes de estado para uso en consultas */
    public const ESTADO_ABIERTO = 'abierto';
    public const ESTADO_EN_PROGRESO = 'en_progreso';
    public const ESTADO_ESPERANDO_REPUESTOS = 'esperando_repuestos';
    public const ESTADO_LISTO_PARA_ENTREGA = 'listo_para_entrega';
    public const ESTADO_ENTREGADA = 'entregada';
    public const ESTADO_CANCELADA = 'cancelada';

    /** @var string[] */
    public const PRIORIDADES = ['baja', 'normal', 'alta', 'urgente'];

    /** Transiciones válidas entre estados. */
    private const TRANSICIONES = [
        'abierto'              => ['en_progreso', 'cancelada'],
        'en_progreso'          => ['esperando_repuestos', 'listo_para_entrega', 'cancelada'],
        'esperando_repuestos'  => ['en_progreso', 'cancelada'],
        'listo_para_entrega'   => ['entregada', 'cancelada'],
        'entregada'            => [],
        'cancelada'            => [],
    ];

    public static function tableName(): string
    {
        return '{{%orden_servicio}}';
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
            [['cliente_id', 'vehiculo_id'], 'required'],
            [['cliente_id', 'vehiculo_id', 'cita_id'], 'integer'],
            ['cliente_id',  'exist', 'targetClass' => Cliente::class,  'targetAttribute' => 'id'],
            ['vehiculo_id', 'exist', 'targetClass' => Vehiculo::class, 'targetAttribute' => 'id'],
            ['estado',    'in', 'range' => self::ESTADOS],
            ['estado',    'default', 'value' => 'abierto'],
            ['prioridad', 'in', 'range' => self::PRIORIDADES],
            ['prioridad', 'default', 'value' => 'normal'],
            ['total',     'number', 'min' => 0],
            ['total',     'default', 'value' => 0.00],
            ['notas_generales', 'string'],
            ['codigo',    'string', 'max' => 20],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'              => 'ID',
            'codigo'          => 'Código',
            'cliente_id'      => 'Cliente',
            'vehiculo_id'     => 'Vehículo',
            'cita_id'         => 'Cita',
            'estado'          => 'Estado',
            'prioridad'       => 'Prioridad',
            'total'           => 'Total',
            'notas_generales' => 'Notas Generales',
            'opened_at'       => 'Abierta',
            'closed_at'       => 'Cerrada',
            'created_at'      => 'Creado',
            'updated_at'      => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $now = time();
        if ($insert) {
            if (empty($this->codigo)) {
                $this->codigo = self::generarCodigo();
            }
            $this->created_at = $now;
            $this->opened_at  = $now;
        }
        $this->updated_at = $now;
        if ($this->estado === 'entregada' && $this->closed_at === null) {
            $this->closed_at = $now;
        }
        return true;
    }

    // ── Métodos de negocio ────────────────────────────────────────────────────

    /** Indica si la transición al nuevo estado es válida. */
    public function puedeTransicionar(string $nuevoEstado): bool
    {
        return in_array($nuevoEstado, self::TRANSICIONES[$this->estado] ?? [], true);
    }

    /** Recalcula el total sumando subtotales de los detalles. */
    public function calcularTotal(): void
    {
        $suma = (float) OrdenServicioDetalle::find()
            ->select('SUM(subtotal)')
            ->where(['orden_id' => $this->id])
            ->scalar();
        $this->total = $suma;
        $this->save(false, ['total', 'updated_at']);
    }

    /**
     * Suma la duración estimada total de la orden en minutos.
     */
    public function getDuracionTotalMinutos(): int
    {
        $total = 0;
        foreach ($this->detalles as $detalle) {
            $duracion = (int) ($detalle->servicio->duracion_estimada ?? 0);
            $total += $duracion * (int) $detalle->cantidad;
        }

        return $total;
    }

    /**
     * Retorna la duración total en formato legible.
     */
    public function getDuracionTotalLabel(): string
    {
        $minutos = $this->getDuracionTotalMinutos();
        if ($minutos <= 0) {
            return '—';
        }

        if ($minutos < 60) {
            return $minutos . ' minutos';
        }

        $horas = intdiv($minutos, 60);
        $resto = $minutos % 60;

        if ($resto === 0) {
            return $horas . ' horas';
        }

        return $horas . ' horas ' . $resto . ' minutos';
    }

    /** Clase Bootstrap según estado. */
    public function getEstadoBadgeClass(): string
    {
        return match ($this->estado) {
            'abierto'              => 'bg-secondary',
            'en_progreso'          => 'bg-info text-dark',
            'esperando_repuestos'  => 'bg-warning text-dark',
            'listo_para_entrega'   => 'bg-primary',
            'entregada'            => 'bg-success',
            'cancelada'            => 'bg-dark',
            default                => 'bg-light text-dark',
        };
    }

    /** Clase Bootstrap según prioridad. */
    public function getPrioridadBadgeClass(): string
    {
        return match ($this->prioridad) {
            'baja'    => 'bg-light text-dark',
            'normal'  => 'bg-secondary',
            'alta'    => 'bg-warning text-dark',
            'urgente' => 'bg-danger',
            default   => 'bg-secondary',
        };
    }

    /** Badge HTML según prioridad. */
    public function getPrioridadBadge(): string
    {
        $class = $this->getPrioridadBadgeClass();
        $label = match ($this->prioridad) {
            'baja'    => 'Baja',
            'normal'  => 'Normal',
            'alta'    => 'Alta',
            'urgente' => 'Urgente',
            default   => ucfirst($this->prioridad ?? ''),
        };
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }

    public static function getEstadosList(): array
    {
        return [
            'abierto'              => 'Abierto',
            'en_progreso'          => 'En Progreso',
            'esperando_repuestos'  => 'Esperando Repuestos',
            'listo_para_entrega'   => 'Listo para Entrega',
            'entregada'            => 'Entregada',
            'cancelada'            => 'Cancelada',
        ];
    }

    public static function getPrioridadesList(): array
    {
        return [
            'baja'    => 'Baja',
            'normal'  => 'Normal',
            'alta'    => 'Alta',
            'urgente' => 'Urgente',
        ];
    }

    /** Genera un código único en formato JOB-NNN. */
    public static function generarCodigo(): string
    {
        $ultimo = static::find()->orderBy(['id' => SORT_DESC])->one();
        $num    = $ultimo ? ((int) $ultimo->id + 1) : 1;
        return sprintf('JOB-%03d', $num);
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

    public function getCita(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cita::class, ['id' => 'cita_id']);
    }

    public function getDetalles(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioDetalle::class, ['orden_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    public function getAsignaciones(): \yii\db\ActiveQuery
    {
        return $this->hasMany(AsignacionOrden::class, ['orden_id' => 'id']);
    }

    public function getNotas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenNota::class, ['orden_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    public function getEstadoLogs(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenEstadoLog::class, ['orden_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    /** Repuestos utilizados en esta orden (HU-013) */
    public function getRepuestos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioRepuesto::class, ['orden_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    /** Calcular total de repuestos utilizados */
    public function getTotalRepuestos(): float
    {
        $suma = (float) OrdenServicioRepuesto::find()
            ->select('SUM(subtotal)')
            ->where(['orden_id' => $this->id])
            ->scalar();
        return $suma ?: 0.0;
    }

    /** Items del checklist de ingreso/entrega (HU-008) */
    public function getChecklistItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ChecklistItem::class, ['orden_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    /** Verifica si todos los items del checklist están completados */
    public function getChecklistCompletado(): bool
    {
        $items = $this->checklistItems;
        if (empty($items)) {
            return false;
        }
        foreach ($items as $item) {
            if (!$item->completado) {
                return false;
            }
        }
        return true;
    }

    /** Obtiene el porcentaje de completion del checklist */
    public function getChecklistPorcentaje(): int
    {
        $items = $this->checklistItems;
        if (empty($items)) {
            return 0;
        }
        $completados = 0;
        foreach ($items as $item) {
            if ($item->completado) {
                $completados++;
            }
        }
        return (int) round(($completados / count($items)) * 100);
    }

    /** Archivos adjuntos (fotos y documentos) - HU-004 */
    public function getArchivos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrdenServicioArchivo::class, ['orden_servicio_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }
}
