<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo ServicioRentabilidad. Mapea {{%servicio_rentabilidad}}.
 * Almacena los cálculos de rentabilidad por servicio y período.
 *
 * @property int         $id
 * @property int         $servicio_id
 * @property string      $periodo        Formato YYYY-MM
 * @property int         $total_ordenes  Cantidad de órdenes con este servicio
 * @property float       $ingreso_total  Suma de ventas del servicio
 * @property float       $costo_servicio Costo directo del servicio
 * @property float       $costo_repuestos Costo de repuestos utilizados
 * @property float       $costo_mano_obra Costo de mano de obra técnica
 * @property float       $overhead       Costos indirectos asignados
 * @property float       $costo_total    Suma de todos los costos
 * @property float       $utilidad_bruta Ingreso - Costo Total
 * @property float       $margen_porcentaje (Utilidad / Ingreso) * 100
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Servicio $servicio
 */
class ServicioRentabilidad extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    public static function tableName(): string
    {
        return '{{%servicio_rentabilidad}}';
    }

    public function rules(): array
    {
        return [
            [['servicio_id', 'periodo'], 'required'],
            [['servicio_id', 'total_ordenes'], 'integer', 'min' => 0],
            ['periodo', 'string', 'max' => 7], // YYYY-MM
            ['periodo', 'match', 'pattern' => '/^\d{4}-\d{2}$/', 'message' => 'El periodo debe tener formato YYYY-MM'],
            [['ingreso_total', 'costo_servicio', 'costo_repuestos', 'costo_mano_obra', 'overhead', 'costo_total', 'utilidad_bruta', 'margen_porcentaje'], 'number'],
            [['ingreso_total', 'costo_servicio', 'costo_repuestos', 'costo_mano_obra', 'overhead', 'costo_total', 'utilidad_bruta', 'margen_porcentaje'], 'default', 'value' => 0],
            [['servicio_id', 'periodo'], 'unique', 'targetAttribute' => ['servicio_id', 'periodo']],
            ['servicio_id', 'exist', 'skipOnError' => true, 'targetClass' => Servicio::class, 'targetAttribute' => ['servicio_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                => 'ID',
            'servicio_id'       => 'Servicio',
            'periodo'           => 'Período',
            'total_ordenes'     => 'Total Órdenes',
            'ingreso_total'     => 'Ingreso Total ($)',
            'costo_servicio'    => 'Costo Servicio ($)',
            'costo_repuestos'   => 'Costo Repuestos ($)',
            'costo_mano_obra'   => 'Costo Mano de Obra ($)',
            'overhead'          => 'Overhead ($)',
            'costo_total'       => 'Costo Total ($)',
            'utilidad_bruta'    => 'Utilidad Bruta ($)',
            'margen_porcentaje' => 'Margen (%)',
            'created_at'        => 'Creado',
            'updated_at'        => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Calcular costo total y utilidad si no están establecidos
        if ($this->costo_total == 0) {
            $this->costo_total = (float)$this->costo_servicio + 
                                (float)$this->costo_repuestos + 
                                (float)$this->costo_mano_obra + 
                                (float)$this->overhead;
        }

        if ($this->utilidad_bruta == 0) {
            $this->utilidad_bruta = (float)$this->ingreso_total - $this->costo_total;
        }

        // Calcular margen porcentual
        if ((float)$this->ingreso_total > 0) {
            $this->margen_porcentaje = round((($this->utilidad_bruta / (float)$this->ingreso_total) * 100), 2);
        } else {
            $this->margen_porcentaje = 0;
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getServicio(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Retorna los períodos disponibles ordenados descendente.
     */
    public static function getPeriodosDisponibles(): array
    {
        return static::find()
            ->select('periodo')
            ->distinct()
            ->orderBy(['periodo' => SORT_DESC])
            ->column();
    }

    /**
     * Retorna el último período con datos.
     */
    public static function getUltimoPeriodo(): ?string
    {
        return static::find()
            ->select('periodo')
            ->orderBy(['periodo' => SORT_DESC])
            ->scalar() ?: null;
    }

    /**
     * Clasifica el margen como Alto/Medio/Bajo.
     */
    public function getClasificacionMargen(): string
    {
        if ($this->margen_porcentaje >= 30) {
            return 'Alto';
        } elseif ($this->margen_porcentaje >= 15) {
            return 'Medio';
        } else {
            return 'Bajo';
        }
    }

    /**
     * Retorna la clase CSS según clasificación de margen.
     */
    public function getClasificacionClass(): string
    {
        return match ($this->getClasificacionMargen()) {
            'Alto' => 'success',
            'Medio' => 'warning',
            default => 'danger',
        };
    }
}
