<?php
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo RolPermiso. Tabla pivote {{%rol_permiso}}.
 *
 * @property int $rol_id
 * @property int $permiso_id
 */
class RolPermiso extends ActiveRecord
{
    public static function tableName(): string { return '{{%rol_permiso}}'; }

    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    public function rules(): array
    {
        return [
            [['rol_id', 'permiso_id'], 'required'],
            [['rol_id', 'permiso_id'], 'integer'],
        ];
    }

    public function getRol(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Rol::class, ['id' => 'rol_id']);
    }

    public function getPermiso(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Permiso::class, ['id' => 'permiso_id']);
    }
}
