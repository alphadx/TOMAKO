<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Catalogo de metodos de pago.
 *
 * @property int $id
 * @property string $codigo
 * @property string $nombre
 * @property int $activo
 */
class MetodoPago extends ActiveRecord
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
        return '{{%metodo_pago}}';
    }

    public function rules(): array
    {
        return [
            [['codigo', 'nombre'], 'required'],
            [['activo'], 'boolean'],
            [['codigo'], 'string', 'max' => 30],
            [['nombre'], 'string', 'max' => 80],
            [['codigo'], 'unique'],
        ];
    }

    public static function getActivosDropdown(): array
    {
        $rows = static::find()
            ->where(['activo' => 1])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->id] = $row->nombre;
        }

        return $map;
    }
}
