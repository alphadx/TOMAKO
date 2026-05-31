<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * This is the model class for table "evaluacion_proveedor".
 *
 * @property int $id
 * @property int $proveedor_id Proveedor evaluado
 * @property int|null $orden_compra_id Orden de compra asociada
 * @property string $fecha_evaluacion Fecha de la evaluación
 * @property int|null $periodo_mes Mes de evaluación (1-12)
 * @property int|null $periodo_anio Año de evaluación
 * @property int|null $puntualidad Puntualidad en entregas (1-5)
 * @property int|null $calidad_producto Calidad de productos recibidos (1-5)
 * @property int|null $atencion_servicio Atención y servicio post-venta (1-5)
 * @property int|null $precio_competitividad Competitividad de precios (1-5)
 * @property int|null $flexibilidad Flexibilidad en pedidos urgentes (1-5)
 * @property float|null $puntaje_total Puntaje total calculado (0-25)
 * @property float|null $puntaje_promedio Puntaje promedio (0-5)
 * @property string|null $comentarios Comentarios adicionales
 * @property int|null $evaluado_por Usuario que realizó la evaluación
 * @property string|null $created_at Fecha de creación
 * @property string|null $updated_at Fecha de actualización
 *
 * @property Proveedor $proveedor
 * @property OrdenCompra $ordenCompra
 * @property Usuario $evaluadoPor
 */
class EvaluacionProveedor extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%evaluacion_proveedor}}';
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
                'value' => new \yii\db\Expression('NOW()'),
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
            [['proveedor_id', 'fecha_evaluacion'], 'required'],
            [['proveedor_id', 'orden_compra_id', 'periodo_mes', 'periodo_anio', 'evaluado_por'], 'integer'],
            [['puntualidad', 'calidad_producto', 'atencion_servicio', 'precio_competitividad', 'flexibilidad'], 'integer', 'min' => 1, 'max' => 5],
            [['puntaje_total', 'puntaje_promedio'], 'number', 'scale' => 2],
            [['fecha_evaluacion'], 'safe'],
            [['comentarios'], 'string'],
            [['proveedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proveedor::class, 'targetAttribute' => ['proveedor_id' => 'id']],
            [['orden_compra_id'], 'exist', 'skipOnError' => true, 'targetClass' => OrdenCompra::class, 'targetAttribute' => ['orden_compra_id' => 'id']],
            [['evaluado_por'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['evaluado_por' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'proveedor_id' => 'Proveedor',
            'orden_compra_id' => 'Orden de Compra',
            'fecha_evaluacion' => 'Fecha Evaluación',
            'periodo_mes' => 'Mes',
            'periodo_anio' => 'Año',
            'puntualidad' => 'Puntualidad',
            'calidad_producto' => 'Calidad Producto',
            'atencion_servicio' => 'Atención/Servicio',
            'precio_competitividad' => 'Precio Competitivo',
            'flexibilidad' => 'Flexibilidad',
            'puntaje_total' => 'Puntaje Total',
            'puntaje_promedio' => 'Puntaje Promedio',
            'comentarios' => 'Comentarios',
            'evaluado_por' => 'Evaluado Por',
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
     * Relación con orden de compra
     */
    public function getOrdenCompra()
    {
        return $this->hasOne(OrdenCompra::class, ['id' => 'orden_compra_id']);
    }

    /**
     * Relación con usuario evaluador
     */
    public function getEvaluadoPor()
    {
        return $this->hasOne(Usuario::class, ['id' => 'evaluado_por']);
    }

    /**
     * Before save: calcular puntajes automáticamente
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // Calcular puntaje total y promedio
            $metricas = [
                $this->puntualidad ?? 0,
                $this->calidad_producto ?? 0,
                $this->atencion_servicio ?? 0,
                $this->precio_competitividad ?? 0,
                $this->flexibilidad ?? 0,
            ];
            
            // Filtrar métricas válidas (mayores a 0)
            $metricasValidas = array_filter($metricas, fn($m) => $m > 0);
            
            if (!empty($metricasValidas)) {
                $this->puntaje_total = array_sum($metricasValidas);
                $this->puntaje_promedio = round($this->puntaje_total / count($metricasValidas), 2);
            }
            
            // Setear período si no está definido
            if ($this->periodo_mes === null) {
                $this->periodo_mes = (int) date('n', strtotime($this->fecha_evaluacion));
            }
            if ($this->periodo_anio === null) {
                $this->periodo_anio = (int) date('Y', strtotime($this->fecha_evaluacion));
            }
            
            return true;
        }
        return false;
    }

    /**
     * After save: actualizar calificación del proveedor
     */
    public function afterSave($insert, $changed)
    {
        parent::afterSave($insert, $changed);
        
        // Actualizar último puntaje del proveedor
        $this->actualizarPuntajeProveedor();
    }

    /**
     * After delete: recalcular puntaje del proveedor
     */
    public function afterDelete()
    {
        parent::afterDelete();
        $this->actualizarPuntajeProveedor();
    }

    /**
     * Actualiza el puntaje promedio del proveedor basado en todas sus evaluaciones
     */
    private function actualizarPuntajeProveedor()
    {
        $promedio = self::find()
            ->where(['proveedor_id' => $this->proveedor_id])
            ->average('puntaje_promedio');
        
        if ($promedio !== null) {
            $proveedor = Proveedor::findOne($this->proveedor_id);
            if ($proveedor) {
                $proveedor->ultimo_puntaje_evaluacion = round($promedio, 2);
                $proveedor->calificacion = round($promedio, 2);
                $proveedor->save(false);
            }
        }
    }

    /**
     * Retorna evaluaciones de un proveedor ordenadas por fecha
     */
    public static function getEvaluacionesPorProveedor($proveedorId)
    {
        return self::find()
            ->where(['proveedor_id' => $proveedorId])
            ->orderBy(['fecha_evaluacion' => SORT_DESC])
            ->all();
    }

    /**
     * Retorna evaluaciones de un período específico
     */
    public static function getEvaluacionesPorPeriodo($mes, $anio)
    {
        return self::find()
            ->where(['periodo_mes' => $mes, 'periodo_anio' => $anio])
            ->joinWith('proveedor')
            ->orderBy(['puntaje_promedio' => SORT_DESC])
            ->all();
    }

    /**
     * Retorna el ranking de proveedores por puntaje en un período
     */
    public static function getRankingProveedores($mes = null, $anio = null)
    {
        $query = self::find()
            ->select(['proveedor_id', 'AVG(puntaje_promedio) as puntaje_prom', 'COUNT(*) as cantidad_evaluaciones'])
            ->groupBy('proveedor_id');
        
        if ($mes !== null && $anio !== null) {
            $query->where(['periodo_mes' => $mes, 'periodo_anio' => $anio]);
        }
        
        return $query->orderBy(['puntaje_prom' => SORT_DESC])
            ->joinWith('proveedor')
            ->all();
    }

    /**
     * Crea una evaluación automática basada en una orden de compra
     */
    public static function crearEvaluacionDesdeOrden(OrdenCompra $orden)
    {
        $evaluacion = new self();
        $evaluacion->proveedor_id = $orden->proveedor_id;
        $evaluacion->orden_compra_id = $orden->id;
        $evaluacion->fecha_evaluacion = $orden->fecha_entrega_real ?? date('Y-m-d');
        
        // Calcular puntualidad basada en fecha esperada vs real
        if ($orden->fecha_entrega_esperada && $orden->fecha_entrega_real) {
            $diasRetraso = (strtotime($orden->fecha_entrega_real) - strtotime($orden->fecha_entrega_esperada)) / 86400;
            
            if ($diasRetraso <= 0) {
                $evaluacion->puntualidad = 5; // A tiempo o antes
            } elseif ($diasRetraso <= 2) {
                $evaluacion->puntualidad = 4; // 1-2 días de retraso
            } elseif ($diasRetraso <= 5) {
                $evaluacion->puntualidad = 3; // 3-5 días de retraso
            } else {
                $evaluacion->puntualidad = 2; // Más de 5 días de retraso
            }
        }
        
        return $evaluacion;
    }
}
