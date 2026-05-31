<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Pago. Mapea la tabla {{%pago}}.
 *
 * @property int         $id
 * @property int         $orden_id
 * @property int|null    $cierre_caja_id
 * @property int|null    $usuario_id
 * @property float       $monto
 * @property int|null    $metodo_pago_id
 * @property string      $metodo_pago
 * @property string|null $referencia
 * @property string      $estado
 * @property string|null $notas
 * @property string|null $observaciones
 * @property string|null $anulado_motivo
 * @property int|null    $pagado_at
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read OrdenServicio $orden
 * @property-read CierreCaja|null $cierreCaja
 * @property-read User|null     $usuario
 * @property-read MetodoPago|null $metodoPago
 */
class Pago extends ActiveRecord
{
    public const METODOS = [
        'efectivo'         => 'Efectivo',
        'tarjeta_debito'   => 'Tarjeta de Débito',
        'tarjeta_credito'  => 'Tarjeta de Crédito',
        'transferencia'    => 'Transferencia',
        'otro'             => 'Otro',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'completado' => 'Completado',
        'pagado'    => 'Pagado',
        'anulado'   => 'Anulado',
    ];

    public static function tableName(): string
    {
        return '{{%pago}}';
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
            [['orden_id', 'monto'], 'required'],
            [['orden_id', 'cierre_caja_id', 'usuario_id', 'pagado_at', 'metodo_pago_id'], 'integer'],
            ['orden_id', 'exist', 'targetClass' => OrdenServicio::class, 'targetAttribute' => 'id'],
            ['cierre_caja_id', 'exist', 'targetClass' => CierreCaja::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true],
            ['metodo_pago_id', 'exist', 'targetClass' => MetodoPago::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true],
            ['monto', 'number', 'min' => 0.01],
            ['metodo_pago', 'in', 'range' => array_keys(self::METODOS)],
            ['metodo_pago', 'default', 'value' => 'efectivo'],
            ['estado', 'in', 'range' => array_keys(self::ESTADOS)],
            ['estado', 'default', 'value' => 'pendiente'],
            ['referencia', 'string', 'max' => 100],
            [['notas', 'observaciones'], 'string'],
            ['anulado_motivo', 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'orden_id'    => 'Orden',
            'cierre_caja_id' => 'Cierre de Caja',
            'usuario_id'  => 'Registrado por',
            'monto'       => 'Monto',
            'metodo_pago_id' => 'Metodo de Pago',
            'metodo_pago' => 'Método de Pago',
            'referencia'  => 'Referencia / Comprobante',
            'estado'      => 'Estado',
            'notas'       => 'Notas',
            'observaciones' => 'Observaciones',
            'anulado_motivo' => 'Motivo de Anulacion',
            'pagado_at'   => 'Fecha de Pago',
            'created_at'  => 'Creado',
            'updated_at'  => 'Actualizado',
        ];
    }

    // ── Relaciones ──────────────────────────────────────────────────────────

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }

    public function getCierreCaja(): \yii\db\ActiveQuery
    {
        return $this->hasOne(CierreCaja::class, ['id' => 'cierre_caja_id']);
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }

    public function getMetodoPago(): \yii\db\ActiveQuery
    {
        return $this->hasOne(MetodoPago::class, ['id' => 'metodo_pago_id']);
    }

    // ── Helpers de presentación ─────────────────────────────────────────────

    public function getMetodoPagoLabel(): string
    {
        if ($this->metodoPago !== null) {
            return $this->metodoPago->nombre;
        }
        return self::METODOS[$this->metodo_pago] ?? $this->metodo_pago;
    }

    public function getEstadoLabel(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }

    public function getEstadoBadgeClass(): string
    {
        return match ($this->estado) {
            'pagado'    => 'bg-success',
            'anulado'   => 'bg-danger',
            default     => 'bg-warning text-dark',
        };
    }
}
