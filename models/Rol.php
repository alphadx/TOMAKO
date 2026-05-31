<?php
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Rol. Mapea la tabla {{%rol}}.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property int $activo
 * @property int|null $created_at
 * @property int|null $updated_at
 */
class Rol extends ActiveRecord
{
    public static function tableName(): string { return '{{%rol}}'; }

    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    public function rules(): array
    {
        return [
            [['nombre'], 'required'],
            ['nombre', 'string', 'max' => 60],
            ['descripcion', 'string', 'max' => 255],
            ['activo', 'integer'],
            ['activo', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'nombre'      => 'Nombre',
            'descripcion' => 'Descripción',
            'activo'      => 'Activo',
            'created_at'  => 'Creado',
            'updated_at'  => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;
        $now = time();
        if ($insert) $this->created_at = $now;
        $this->updated_at = $now;
        return true;
    }

    public function getUsuarios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(User::class, ['rol_id' => 'id']);
    }

    public function getRolPermisos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(RolPermiso::class, ['rol_id' => 'id']);
    }

    public function getPermisos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Permiso::class, ['id' => 'permiso_id'])
            ->via('rolPermisos');
    }

    public static function getRolesArray(): array
    {
        return static::find()
            ->where(['activo' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }
}
