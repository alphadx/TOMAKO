<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;
use app\models\User;

/**
 * Modelo Seguimiento. Mapea la tabla {{%seguimiento}}.
 * 
 * Representa un seguimiento post-servicio para verificar la satisfacción del cliente.
 *
 * @property int         $id
 * @property int         $orden_servicio_id
 * @property int         $cliente_id
 * @property string      $tipo
 * @property string      $estado
 * @property int|null    $fecha_programada
 * @property int|null    $fecha_realizacion
 * @property int|null    $realizado_por
 * @property string|null $resultado
 * @property int|null    $satisfaccion
 * @property string|null $observaciones
 * @property float|null  $nps_score
 * @property boolean     $recomendariamos
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read OrdenServicio $ordenServicio
 * @property-read Cliente       $cliente
 * @property-read Usuario       $realizadoPor
 */
class Seguimiento extends ActiveRecord
{
    /** @var string[] Tipos de seguimiento */
    public const TIPOS = [
        'llamada' => 'Llamada telefónica',
        'email' => 'Email',
        'encuesta' => 'Encuesta',
        'whatsapp' => 'WhatsApp',
    ];

    /** @var string[] Estados del seguimiento */
    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'completado' => 'Completado',
        'omitido' => 'Omitido',
        'fallido' => 'Fallido',
    ];

    /** @var int Días por defecto para programar seguimiento después de entrega */
    public const DIAS_SEGUIMIENTO_DEFAULT = 3;

    public static function tableName(): string
    {
        return '{{%seguimiento}}';
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
            [['orden_servicio_id', 'cliente_id'], 'required'],
            [['orden_servicio_id', 'cliente_id', 'realizado_por', 'fecha_programada', 'fecha_realizacion'], 'integer'],
            [['tipo'], 'in', 'range' => array_keys(self::TIPOS)],
            [['tipo'], 'default', 'value' => 'llamada'],
            [['estado'], 'in', 'range' => array_keys(self::ESTADOS)],
            [['estado'], 'default', 'value' => 'pendiente'],
            [['satisfaccion'], 'integer', 'min' => 1, 'max' => 5],
            [['nps_score'], 'number', 'min' => 0, 'max' => 10],
            [['recomendariamos'], 'boolean'],
            [['resultado', 'observaciones'], 'string'],
            [['fecha_programada'], 'required', 'when' => fn($model) => $model->estado === 'pendiente'],
            
            // Verificar existencia de relaciones
            ['orden_servicio_id', 'exist', 'targetClass' => OrdenServicio::class, 'targetAttribute' => 'id'],
            ['cliente_id', 'exist', 'targetClass' => Cliente::class, 'targetAttribute' => 'id'],
            ['realizado_por', 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_servicio_id' => 'Orden de Servicio',
            'cliente_id' => 'Cliente',
            'tipo' => 'Tipo de Seguimiento',
            'estado' => 'Estado',
            'fecha_programada' => 'Fecha Programada',
            'fecha_realizacion' => 'Fecha de Realización',
            'realizado_por' => 'Realizado Por',
            'resultado' => 'Resultado',
            'satisfaccion' => 'Nivel de Satisfacción',
            'observaciones' => 'Observaciones',
            'nps_score' => 'NPS Score',
            'recomendariamos' => '¿Nos Recomendaría?',
            'created_at' => 'Creado En',
            'updated_at' => 'Actualizado En',
        ];
    }

    public function scenarios(): array
    {
        $scenarios = parent::scenarios();
        $scenarios['registro'] = ['resultado', 'satisfaccion', 'observaciones', 'nps_score', 'recomendariamos', 'fecha_realizacion', 'estado'];
        $scenarios['programar'] = ['orden_servicio_id', 'cliente_id', 'tipo', 'fecha_programada'];
        return $scenarios;
    }

    /**
     * Relación con OrdenServicio
     */
    public function getOrdenServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_servicio_id']);
    }

    /**
     * Relación con Cliente
     */
    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    /**
     * Relación con Usuario que realizó el seguimiento
     */
    public function getRealizadoPor(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'realizado_por']);
    }

    /**
     * Obtiene la etiqueta del tipo
     */
    public function getTipoLabel(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    /**
     * Obtiene la etiqueta del estado
     */
    public function getEstadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    /**
     * Verifica si el seguimiento está pendiente
     */
    public function isPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    /**
     * Verifica si el seguimiento está completado
     */
    public function isCompletado(): bool
    {
        return $this->estado === 'completado';
    }

    /**
     * Marca el seguimiento como completado
     */
    public function completar(string $resultado, ?int $satisfaccion = null, ?string $observaciones = null): bool
    {
        $this->estado = 'completado';
        $this->resultado = $resultado;
        $this->satisfaccion = $satisfaccion;
        $this->observaciones = $observaciones;
        $this->fecha_realizacion = time();
        
        return $this->save(false);
    }

    /**
     * Calcula automáticamente el NPS score basado en satisfacción
     */
    public function calcularNPS(): void
    {
        if ($this->satisfaccion !== null) {
            // NPS: 1-6 = Detractor (0-6), 7-8 = Neutro (7-8), 9-10 = Promotor (9-10)
            // Mapeamos 1-5 estrellas a 0-10
            $this->nps_score = ($this->satisfaccion - 1) * 2.5;
        }
    }

    /**
     * Busca seguimientos pendientes para una fecha específica
     */
    public static function findPendientesParaFecha(int $timestamp): \yii\db\ActiveQuery
    {
        $inicioDia = strtotime('midnight', $timestamp);
        $finDia = strtotime('tomorrow', $timestamp);
        
        return self::find()
            ->where(['seguimiento.estado' => 'pendiente'])
            ->andWhere(['>=', 'seguimiento.fecha_programada', $inicioDia])
            ->andWhere(['<', 'seguimiento.fecha_programada', $finDia]);
    }

    /**
     * Busca seguimientos pendientes de un cliente
     */
    public static function findPendientesPorCliente(int $clienteId): \yii\db\ActiveQuery
    {
        return self::find()
            ->where(['cliente_id' => $clienteId, 'estado' => 'pendiente']);
    }

    /**
     * Crea un seguimiento automático para una orden entregada
     */
    public static function crearParaOrden(OrdenServicio $orden, int $diasDespues = null): self
    {
        $dias = $diasDespues ?? self::DIAS_SEGUIMIENTO_DEFAULT;
        $fechaProgramada = strtotime("+{$dias} days", $orden->closed_at ?? time());
        
        $seguimiento = new self();
        $seguimiento->orden_servicio_id = $orden->id;
        $seguimiento->cliente_id = $orden->cliente_id;
        $seguimiento->tipo = 'llamada';
        $seguimiento->estado = 'pendiente';
        $seguimiento->fecha_programada = $fechaProgramada;
        
        return $seguimiento;
    }

    /**
     * Retorna estadísticas de satisfacción para un período
     */
    public static function getEstadisticasPeriodo(int $inicio, int $fin): array
    {
        $query = self::find()
            ->where(['estado' => 'completado'])
            ->andWhere(['>=', 'fecha_realizacion', $inicio])
            ->andWhere(['<=', 'fecha_realizacion', $fin]);

        $total = $query->count();
        $promedioSatisfaccion = $query->average('satisfaccion') ?? 0;
        $promedioNPS = $query->average('nps_score') ?? 0;
        
        $recomendados = (clone $query)->andWhere(['recomendariamos' => true])->count();
        $noRecomendados = (clone $query)->andWhere(['recomendariamos' => false])->count();
        
        return [
            'total_seguimientos' => $total,
            'satisfaccion_promedio' => round($promedioSatisfaccion, 2),
            'nps_promedio' => round($promedioNPS, 2),
            'recomendados' => $recomendados,
            'no_recomendados' => $noRecomendados,
            'tasa_recomendacion' => $total > 0 ? round(($recomendados / $total) * 100, 2) : 0,
        ];
    }
}
