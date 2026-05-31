<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo OrdenServicioRepuesto. Relación N:M entre órdenes y repuestos (HU-013)
 *
 * @property int         $id
 * @property int         $orden_id
 * @property int         $repuesto_id
 * @property int         $cantidad
 * @property float       $precio_unitario_aplicado
 * @property float       $subtotal
 * @property string|null $nota
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read OrdenServicio   $orden
 * @property-read InventoryItem   $repuesto
 */
class OrdenServicioRepuesto extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%orden_servicio_repuesto}}';
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
            [['orden_id', 'repuesto_id', 'precio_unitario_aplicado'], 'required'],
            [['orden_id', 'repuesto_id', 'cantidad'], 'integer', 'min' => 1],
            [['precio_unitario_aplicado', 'subtotal'], 'number', 'min' => 0],
            ['nota', 'string', 'max' => 500],
            ['cantidad', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                       => 'ID',
            'orden_id'                 => 'Orden',
            'repuesto_id'              => 'Repuesto',
            'cantidad'                 => 'Cantidad',
            'precio_unitario_aplicado' => 'Precio Unitario',
            'subtotal'                 => 'Subtotal',
            'nota'                     => 'Nota',
            'created_at'               => 'Creado',
            'updated_at'               => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        
        // Calcular subtotal automáticamente
        $this->subtotal = (float) $this->cantidad * (float) $this->precio_unitario_aplicado;
        
        return true;
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        // Si es nuevo registro, establecer timestamps
        if ($this->getIsNewRecord()) {
            $now = time();
            $this->created_at = $now;
            $this->updated_at = $now;
        } else {
            $this->updated_at = time();
        }

        return true;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        
        // Actualizar stock en tiempo real cuando se agrega/actualiza un repuesto
        if ($insert || array_key_exists('cantidad', $changedAttributes)) {
            $this->actualizarStockEnTiempoReal($insert, $changedAttributes);
        }
    }

    /**
     * Actualiza el stock del inventario cuando se agrega o modifica un repuesto en la orden.
     * (HU-011: Control de Stock en Tiempo Real)
     */
    private function actualizarStockEnTiempoReal(bool $insert, array $changedAttributes): void
    {
        $repuesto = $this->repuesto;
        if (!$repuesto) {
            return;
        }

        $service = new \app\components\services\InventarioService();
        
        try {
            if ($insert) {
                // Nueva inserción: restar cantidad del stock
                $service->registrarSalida(
                    $repuesto->id,
                    $this->cantidad,
                    'Orden #' . $this->orden_id . ' - ' . ($this->nota ?? 'Servicio'),
                    (int) \Yii::$app->user->id
                );
            } elseif (array_key_exists('cantidad', $changedAttributes)) {
                // Actualización: ajustar diferencia
                $cantidadAnterior = (int) ($changedAttributes['cantidad'] ?? 0);
                $cantidadNueva = (int) $this->cantidad;
                $diferencia = $cantidadNueva - $cantidadAnterior;
                
                if ($diferencia > 0) {
                    // Se aumentó la cantidad: restar más del stock
                    $service->registrarSalida(
                        $repuesto->id,
                        $diferencia,
                        'Orden #' . $this->orden_id . ' - Ajuste cantidad',
                        (int) \Yii::$app->user->id
                    );
                } elseif ($diferencia < 0) {
                    // Se disminuyó la cantidad: devolver al stock
                    $service->registrarEntrada(
                        $repuesto->id,
                        abs($diferencia),
                        'Orden #' . $this->orden_id . ' - Devolución',
                        (int) \Yii::$app->user->id
                    );
                }
            }
        } catch (\Throwable $e) {
            // Registrar error pero no fallar la operación principal
            \Yii::error('Error actualizando stock en tiempo real: ' . $e->getMessage(), 'app.inventario');
        }
    }

    public function afterDelete(): void
    {
        parent::afterDelete();
        
        // Devolver stock cuando se elimina un repuesto de la orden
        $repuesto = $this->repuesto;
        if ($repuesto) {
            try {
                $service = new \app\components\services\InventarioService();
                $service->registrarEntrada(
                    $repuesto->id,
                    $this->cantidad,
                    'Orden #' . $this->orden_id . ' - Eliminación',
                    (int) \Yii::$app->user->id
                );
            } catch (\Throwable $e) {
                \Yii::error('Error devolviendo stock al eliminar repuesto: ' . $e->getMessage(), 'app.inventario');
            }
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getOrden(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }

    public function getRepuesto(): \yii\db\ActiveQuery
    {
        return $this->hasOne(InventoryItem::class, ['id' => 'repuesto_id']);
    }
}
