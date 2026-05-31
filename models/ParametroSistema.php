<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo ActiveRecord para la tabla parametro_sistema.
 * Almacena configuración clave-valor del taller con caché integrado.
 *
 * @property int    $id
 * @property string $clave
 * @property string|null $valor
 * @property string $tipo
 * @property string|null $descripcion
 * @property int    $editable
 * @property int|null $updated_at
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ParametroSistema extends ActiveRecord
{
    /** Prefijo de clave de caché para parámetros */
    private const CACHE_PREFIX = 'param_sistema_';
    /** TTL de caché para parámetros (5 minutos) */
    private const CACHE_TTL    = 300;

    /** @inheritdoc */
    public static function tableName(): string
    {
        return '{{%parametro_sistema}}';
    }

    public function behaviors(): array
    {
        return [
            'audit' => ['class' => AuditBehavior::class],
        ];
    }

    /** @inheritdoc */
    public function rules(): array
    {
        return [
            [['clave'], 'required'],
            [['clave'], 'string', 'max' => 100],
            [['valor'], 'string'],
            [['tipo'], 'string', 'max' => 20],
            [['descripcion'], 'string', 'max' => 255],
            [['editable'], 'integer'],
            [['updated_at'], 'integer'],
            [['clave'], 'unique'],
            [['tipo'], 'in', 'range' => ['string', 'integer', 'float', 'boolean', 'json']],
        ];
    }

    /** @inheritdoc */
    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'clave'       => Yii::t('app', 'Clave'),
            'valor'       => Yii::t('app', 'Valor'),
            'tipo'        => Yii::t('app', 'Tipo'),
            'descripcion' => Yii::t('app', 'Descripcion'),
            'editable'    => Yii::t('app', 'Editable'),
            'updated_at'  => Yii::t('app', 'Actualizado'),
        ];
    }

    /** @inheritdoc */
    public function beforeSave($insert): bool
    {
        $this->updated_at = time();
        return parent::beforeSave($insert);
    }

    /** @inheritdoc */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        Yii::$app->cache->delete(self::CACHE_PREFIX . $this->clave);
    }

    /**
     * Obtiene el valor de un parámetro por su clave, con caché.
     *
     * @param string $clave    Clave del parámetro.
     * @param mixed  $defecto  Valor por defecto si no existe.
     * @return mixed
     */
    public static function getValor(string $clave, mixed $defecto = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX . $clave;

        return Yii::$app->cache->getOrSet($cacheKey, function () use ($clave, $defecto): mixed {
            $param = static::findOne(['clave' => $clave]);
            if ($param === null) {
                return $defecto;
            }
            return self::castValor($param->valor, $param->tipo);
        }, self::CACHE_TTL);
    }

    /**
     * Establece el valor de un parámetro por su clave (crea o actualiza).
     *
     * @param string $clave  Clave del parámetro.
     * @param mixed  $valor  Nuevo valor.
     * @return bool
     */
    public static function setValor(string $clave, mixed $valor): bool
    {
        $param = static::findOne(['clave' => $clave]);
        if ($param === null) {
            $param = new static(['clave' => $clave, 'tipo' => 'string']);
        }
        $param->valor = (string) $valor;
        $guardado = $param->save();

        if ($guardado) {
            Yii::$app->cache->delete(self::CACHE_PREFIX . $clave);
        }
        return $guardado;
    }

    /**
     * Convierte el valor string al tipo de dato correspondiente.
     *
     * @param string|null $valor  Valor almacenado como string.
     * @param string      $tipo   Tipo de dato destino.
     * @return mixed
     */
    private static function castValor(?string $valor, string $tipo): mixed
    {
        if ($valor === null) {
            return null;
        }
        return match ($tipo) {
            'integer' => (int)   $valor,
            'float'   => (float) $valor,
            'boolean' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($valor, true),
            default   => $valor,
        };
    }
}
