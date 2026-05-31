<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Especialidad. Mapea la tabla {{%especialidad}}.
 *
 * @property int         $id
 * @property string      $nombre
 * @property string|null $descripcion
 * @property int         $status
 * @property int|null    $created_at
 *
 * @property-read Tecnico[] $tecnicos
 */
class Especialidad extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%especialidad}}';
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
            [['nombre'], 'required'],
            ['nombre',      'string',  'max' => 100],
            ['nombre',      'unique'],
            ['descripcion', 'string'],
            ['status',      'boolean'],
            ['status',      'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'nombre'      => 'Nombre',
            'descripcion' => 'Descripción',
            'status'      => 'Estado',
            'created_at'  => 'Creado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($insert) {
            $this->created_at = time();
        }
        return true;
    }

    public function getTecnicos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Tecnico::class, ['especialidad_id' => 'id']);
    }

    /**
     * Lista de especialidades activas para dropdowns.
     *
     * @return array<int,string>
     */
    public static function getEspecialidadesList(): array
    {
        return static::find()
            ->where(['status' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }

    public static function getEstadosList(): array
    {
        return ['1' => 'Activo', '0' => 'Inactivo'];
    }
}
