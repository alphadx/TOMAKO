<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Model ChecklistItem
 *
 * @property int $id
 * @property int $orden_id
 * @property string $item Descripción del item de verificación
 * @property bool $completado Estado de completación
 * @property int $created_at Timestamp de creación
 * @property int $updated_at Timestamp de última actualización
 *
 * @property-read OrdenServicio $orden
 */
class ChecklistItem extends ActiveRecord
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
        return '{{%checklist_item}}';
    }

    public function rules(): array
    {
        return [
            [['orden_id', 'item'], 'required'],
            [['orden_id'], 'integer'],
            ['orden_id', 'exist', 'targetClass' => OrdenServicio::class, 'targetAttribute' => 'id'],
            [['item'], 'string', 'max' => 255],
            [['completado'], 'boolean'],
            [['completado'], 'default', 'value' => false],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'orden_id' => 'Orden',
            'item' => 'Item de Verificación',
            'completado' => 'Completado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
        ];
    }

    // Relations
    public function getOrden()
    {
        return $this->hasOne(OrdenServicio::class, ['id' => 'orden_id']);
    }
}
