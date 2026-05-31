<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Cierre de caja diario por usuario.
 *
 * @property int $id
 * @property int $usuario_id
 * @property string $fecha
 * @property float $monto_inicial
 * @property float|null $monto_esperado
 * @property float|null $monto_final
 * @property float|null $diferencia
 * @property string $estado
 * @property int|null $closed_at
 */
class CierreCaja extends ActiveRecord
{
    public function behaviors(): array
    {
        return [
            'audit' => [
                'class' => AuditBehavior::class,
            ],
        ];
    }

    public const ESTADO_ABIERTO = 'abierto';
    public const ESTADO_CERRADO = 'cerrado';

    public static function tableName(): string
    {
        return '{{%cierre_caja}}';
    }

    public function rules(): array
    {
        return [
            [['usuario_id', 'fecha', 'monto_inicial'], 'required'],
            [['usuario_id', 'closed_at'], 'integer'],
            [['monto_inicial', 'monto_esperado', 'monto_final', 'diferencia'], 'number'],
            [['fecha'], 'date', 'format' => 'php:Y-m-d'],
            [['estado'], 'in', 'range' => [self::ESTADO_ABIERTO, self::ESTADO_CERRADO]],
            [['estado'], 'default', 'value' => self::ESTADO_ABIERTO],
            [['usuario_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }

    public function getUsuario(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }
}
