<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo PlantillaChecklistItem
 *
 * Items individuales que componen una plantilla de checklist
 *
 * @property int $id
 * @property int $plantilla_id ID de la plantilla padre
 * @property string $descripcion Descripción del item a verificar
 * @property int $orden Orden de visualización
 * @property bool $obligatorio Indica si el item es obligatorio
 * @property int $created_at Timestamp de creación
 *
 * @property-read PlantillaChecklist $plantilla
 */
class PlantillaChecklistItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%plantilla_checklist_item}}';
    }

    public function rules(): array
    {
        return [
            [['plantilla_id', 'descripcion'], 'required'],
            [['plantilla_id', 'orden'], 'integer'],
            ['plantilla_id', 'exist', 'skipOnError' => true, 'targetClass' => PlantillaChecklist::class, 'targetAttribute' => ['plantilla_id' => 'id']],
            [['descripcion'], 'string', 'max' => 255],
            [['orden'], 'default', 'value' => 0],
            [['obligatorio'], 'boolean'],
            [['obligatorio'], 'default', 'value' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'plantilla_id' => 'Plantilla',
            'descripcion' => 'Descripción del Item',
            'orden' => 'Orden',
            'obligatorio' => 'Obligatorio',
            'created_at' => 'Creado',
        ];
    }

    // Relaciones
    public function getPlantilla()
    {
        return $this->hasOne(PlantillaChecklist::class, ['id' => 'plantilla_id']);
    }

    /**
     * Obtiene todos los items de una plantilla ordenados
     */
    public static function getItemsPorPlantilla($plantillaId): array
    {
        return self::find()
            ->where(['plantilla_id' => $plantillaId])
            ->orderBy(['orden' => SORT_ASC])
            ->all();
    }
}
