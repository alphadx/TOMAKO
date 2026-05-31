<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo PlantillaChecklist
 *
 * Plantillas de checklist predefinidas por tipo de servicio
 *
 * @property int $id
 * @property int $servicio_id ID del servicio asociado
 * @property string $nombre Nombre descriptivo de la plantilla
 * @property string|null $descripcion Descripción opcional
 * @property bool $activa Estado de la plantilla
 * @property int $created_at Timestamp de creación
 * @property int $updated_at Timestamp de última actualización
 *
 * @property-read Servicio $servicio
 * @property-read PlantillaChecklistItem[] $items
 */
class PlantillaChecklist extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%plantilla_checklist}}';
    }

    public function rules(): array
    {
        return [
            [['servicio_id', 'nombre'], 'required'],
            [['servicio_id'], 'integer'],
            ['servicio_id', 'exist', 'skipOnError' => true, 'targetClass' => Servicio::class, 'targetAttribute' => ['servicio_id' => 'id']],
            [['nombre'], 'string', 'max' => 150],
            [['descripcion'], 'string', 'max' => 500],
            [['activa'], 'boolean'],
            [['activa'], 'default', 'value' => true],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'servicio_id' => 'Servicio',
            'nombre' => 'Nombre de la Plantilla',
            'descripcion' => 'Descripción',
            'activa' => 'Activa',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    // Relaciones
    public function getServicio()
    {
        return $this->hasOne(Servicio::class, ['id' => 'servicio_id']);
    }

    public function getItems()
    {
        return $this->hasMany(PlantillaChecklistItem::class, ['plantilla_id' => 'id'])
            ->orderBy(['orden' => SORT_ASC]);
    }

    /**
     * Obtiene todas las plantillas activas para un servicio
     */
    public static function getPlantillasPorServicio($servicioId): array
    {
        return self::find()
            ->where(['servicio_id' => $servicioId, 'activa' => true])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();
    }

    /**
     * Crea items de checklist en una orden basados en esta plantilla
     */
    public function aplicarAOrden(OrdenServicio $orden): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $checklistItem = new ChecklistItem([
                'orden_id' => $orden->id,
                'item' => $item->descripcion,
                'completado' => false,
            ]);
            if ($checklistItem->save(false)) {
                $count++;
            }
        }
        return $count;
    }
}
