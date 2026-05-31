<?php
declare(strict_types=1);
namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Permiso. Mapea la tabla {{%permiso}}.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $modulo
 * @property int|null $created_at
 */
class Permiso extends ActiveRecord
{
    public static function tableName(): string { return '{{%permiso}}'; }

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
            ['nombre', 'string', 'max' => 100],
            ['descripcion', 'string', 'max' => 255],
            ['modulo', 'string', 'max' => 60],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'nombre'      => 'Nombre',
            'descripcion' => 'Descripción',
            'modulo'      => 'Módulo',
            'created_at'  => 'Creado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) return false;
        if ($insert) $this->created_at = time();
        return true;
    }

    public function getRolPermisos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(RolPermiso::class, ['permiso_id' => 'id']);
    }

    public static function getPermisosAgrupadosPorModulo(): array
    {
        $permisos = static::find()->orderBy(['modulo' => SORT_ASC, 'nombre' => SORT_ASC])->all();
        $agrupados = [];
        foreach ($permisos as $permiso) {
            $agrupados[$permiso->modulo ?? 'general'][] = $permiso;
        }
        return $agrupados;
    }
}
