<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Marca para el sistema de gestión de talleres.
 *
 * @property int $id
 * @property string $nombre
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Modelo[] $modelos
 */
class Marca extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%marca}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['nombre'], 'required'],
            [['nombre'], 'string', 'max' => 60],
            [['nombre'], 'trim'],
            [['nombre'], 'unique'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'nombre' => 'Marca',
            'created_at' => 'Creado en',
            'updated_at' => 'Actualizado en',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            // Normalizar nombre: mayúsculas y trim
            $this->nombre = strtoupper(trim($this->nombre));
            
            if ($insert) {
                $this->created_at = time();
            }
            $this->updated_at = time();
            return true;
        }
        return false;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getModelos()
    {
        return $this->hasMany(Modelo::class, ['marca_id' => 'id']);
    }

    /**
     * Busca o crea una marca por nombre.
     *
     * @param string $nombre
     * @return Marca
     */
    public static function buscarOCrear(string $nombre): Marca
    {
        $nombre = strtoupper(trim($nombre));
        
        $marca = self::findOne(['nombre' => $nombre]);
        if ($marca === null) {
            $marca = new self();
            $marca->nombre = $nombre;
            $marca->save(false);
        }
        
        return $marca;
    }
}
