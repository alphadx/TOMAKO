<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * This is the model class for table "orden_compra".
 *
 * @property int $id
 * @property string $numero_orden Número de orden de compra
 * @property int $proveedor_id Proveedor asociado
 * @property string $fecha_emision Fecha de emisión de la orden
 * @property string|null $fecha_entrega_esperada Fecha esperada de entrega
 * @property string|null $fecha_entrega_real Fecha real de entrega
 * @property string $estado Estado de la orden (borrador, enviada, recibida_parcial, recibida_completo, cancelada)
 * @property decimal $total_monto Monto total de la orden
 * @property string|null $observaciones Observaciones adicionales
 * @property int|null $created_by Usuario que creó la orden
 * @property int|null $updated_by Usuario que actualizó la orden
 * @property string|null $created_at Fecha de creación
 * @property string|null $updated_at Fecha de actualización
 *
 * @property Proveedor $proveedor
 * @property OrdenCompraItem[] $items
 * @property Usuario $createdBy
 * @property Usuario $updatedBy
 */
class OrdenCompra extends ActiveRecord
{
    // Estados de la orden de compra
    const ESTADO_BORRADOR = 'borrador';
    const ESTADO_ENVIADA = 'enviada';
    const ESTADO_RECIBIDA_PARCIAL = 'recibida_parcial';
    const ESTADO_RECIBIDA_COMPLETO = 'recibida_completo';
    const ESTADO_CANCELADA = 'cancelada';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%orden_compra}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            'audit' => [
                'class' => AuditBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['proveedor_id', 'fecha_emision', 'estado'], 'required'],
            [['proveedor_id', 'created_by', 'updated_by'], 'integer'],
            [['total_monto'], 'number', 'scale' => 2],
            [['fecha_emision', 'fecha_entrega_esperada', 'fecha_entrega_real'], 'safe'],
            [['estado'], 'string', 'max' => 20],
            [['numero_orden'], 'string', 'max' => 50],
            [['observaciones'], 'string'],
            [['numero_orden'], 'unique'],
            [['proveedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proveedor::class, 'targetAttribute' => ['proveedor_id' => 'id']],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['updated_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'numero_orden' => 'Número de Orden',
            'proveedor_id' => 'Proveedor',
            'fecha_emision' => 'Fecha de Emisión',
            'fecha_entrega_esperada' => 'Fecha Entrega Esperada',
            'fecha_entrega_real' => 'Fecha Entrega Real',
            'estado' => 'Estado',
            'total_monto' => 'Monto Total',
            'observaciones' => 'Observaciones',
            'created_by' => 'Creado Por',
            'updated_by' => 'Actualizado Por',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
        ];
    }

    /**
     * Relación con proveedor
     */
    public function getProveedor()
    {
        return $this->hasOne(Proveedor::class, ['id' => 'proveedor_id']);
    }

    /**
     * Relación con items de la orden
     */
    public function getItems()
    {
        return $this->hasMany(OrdenCompraItem::class, ['orden_compra_id' => 'id']);
    }

    /**
     * Relación con usuario creador
     */
    public function getCreatedBy()
    {
        return $this->hasOne(Usuario::class, ['id' => 'created_by']);
    }

    /**
     * Relación con usuario actualizador
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(Usuario::class, ['id' => 'updated_by']);
    }

    /**
     * Calcula el monto total de la orden basado en los items
     */
    public function calcularTotal()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->cantidad * $item->precio_unitario;
        }
        $this->total_monto = $total;
        return $total;
    }

    /**
     * Verifica si todos los items han sido recibidos completamente
     */
    public function verificarRecepcionCompleta()
    {
        foreach ($this->items as $item) {
            if ($item->cantidad_recibida < $item->cantidad) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verifica si al menos un item ha sido recibido parcialmente
     */
    public function verificarRecepcionParcial()
    {
        foreach ($this->items as $item) {
            if ($item->cantidad_recibida > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Actualiza el estado de la orden según la recepción
     */
    public function actualizarEstadoPorRecepcion()
    {
        if ($this->estado === self::ESTADO_CANCELADA) {
            return;
        }

        if ($this->verificarRecepcionCompleta()) {
            $this->estado = self::ESTADO_RECIBIDA_COMPLETO;
        } elseif ($this->verificarRecepcionParcial()) {
            $this->estado = self::ESTADO_RECIBIDA_PARCIAL;
        }
        
        $this->save(false);
    }

    /**
     * Retorna las opciones de estado para dropdown
     */
    public static function getEstadosOpciones()
    {
        return [
            self::ESTADO_BORRADOR => 'Borrador',
            self::ESTADO_ENVIADA => 'Enviada',
            self::ESTADO_RECIBIDA_PARCIAL => 'Recibida Parcial',
            self::ESTADO_RECIBIDA_COMPLETO => 'Recibida Completo',
            self::ESTADO_CANCELADA => 'Cancelada',
        ];
    }

    /**
     * Genera un número de orden consecutivo
     */
    public static function generarNumeroOrden()
    {
        $year = date('Y');
        $lastOrden = self::find()
            ->where(['LIKE', 'numero_orden', "OC-$year-"])
            ->orderBy(['id' => SORT_DESC])
            ->one();
        
        if ($lastOrden) {
            $lastNumber = (int) substr($lastOrden->numero_orden, -6);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('OC-%s-%06d', $year, $newNumber);
    }

    /**
     * Retorna órdenes de compra activas (no canceladas)
     */
    public static function findActivas()
    {
        return self::find()->where(['!=', 'estado', self::ESTADO_CANCELADA]);
    }

    /**
     * Retorna órdenes de compra pendientes de recepción
     */
    public static function findPendientesRecepcion()
    {
        return self::find()->where(['in', 'estado', [
            self::ESTADO_BORRADOR,
            self::ESTADO_ENVIADA,
            self::ESTADO_RECIBIDA_PARCIAL
        ]]);
    }
}
