<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo ClienteEtiqueta. Mapea la tabla {{%cliente_etiqueta}}.
 * Tabla intermedia para relación muchos-a-muchos entre Cliente y Etiqueta.
 *
 * @property int         $id
 * @property int         $cliente_id
 * @property int         $etiqueta_id
 * @property string|null $notas
 * @property int|null    $created_at
 */
class ClienteEtiqueta extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%cliente_etiqueta}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['cliente_id', 'etiqueta_id'], 'required'],
            [['cliente_id', 'etiqueta_id'], 'integer'],
            ['cliente_id', 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            ['etiqueta_id', 'exist', 'skipOnError' => true, 'targetClass' => Etiqueta::class, 'targetAttribute' => ['etiqueta_id' => 'id']],
            [['cliente_id', 'etiqueta_id'], 'unique', 'targetAttribute' => ['cliente_id', 'etiqueta_id'], 'message' => 'Esta etiqueta ya está asignada al cliente.'],
            ['notas', 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'           => 'ID',
            'cliente_id'   => 'Cliente',
            'etiqueta_id'  => 'Etiqueta',
            'notas'        => 'Notas',
            'created_at'   => 'Fecha Asignación',
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

    /**
     * Obtiene el cliente asociado.
     */
    public function getCliente(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    /**
     * Obtiene la etiqueta asociada.
     */
    public function getEtiqueta(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Etiqueta::class, ['id' => 'etiqueta_id']);
    }
}
