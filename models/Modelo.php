<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Modelo para el sistema de gestión de talleres.
 *
 * @property int $id
 * @property int $marca_id
 * @property string $nombre
 * @property int|null $created_at
 * @property int|null $updated_at
 *
 * @property Marca $marca
 */
class Modelo extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%modelo}}';
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
            [['marca_id', 'nombre'], 'required'],
            [['marca_id'], 'integer'],
            [['nombre'], 'string', 'max' => 60],
            [['nombre'], 'trim'],
            [['marca_id', 'nombre'], 'unique', 'targetAttribute' => ['marca_id', 'nombre']],
            [['created_at', 'updated_at'], 'integer'],
            [['marca_id'], 'exist', 'skipOnError' => true, 'targetClass' => Marca::class, 'targetAttribute' => ['marca_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'marca_id' => 'Marca',
            'nombre' => 'Modelo',
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
    public function getMarca()
    {
        return $this->hasOne(Marca::class, ['id' => 'marca_id']);
    }

    /**
     * Busca o crea un modelo por marca y nombre.
     *
     * @param int $marcaId
     * @param string $nombre
     * @return Modelo
     */
    public static function buscarOCrear(int $marcaId, string $nombre): Modelo
    {
        $nombre = strtoupper(trim($nombre));
        
        $modelo = self::findOne(['marca_id' => $marcaId, 'nombre' => $nombre]);
        if ($modelo === null) {
            $modelo = new self();
            $modelo->marca_id = $marcaId;
            $modelo->nombre = $nombre;
            $modelo->save(false);
        }
        
        return $modelo;
    }

    /**
     * Obtiene modelos para una marca específica.
     *
     * @param int $marcaId
     * @return array
     */
    public static function getModelosPorMarca(int $marcaId): array
    {
        return self::find()
            ->where(['marca_id' => $marcaId])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }
}
