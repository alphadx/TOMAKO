<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo para la tabla cotizacion_jwt.
 * 
 * Esta clase representa los registros de validación de cotizaciones JWT.
 * 
 * @property int $id
 * @property string $hash
 * @property string $jwt
 * @property string $raiz_url
 * @property int $usado
 * @property int $created_at
 * @property int|null $expires_at
 * 
 * @author ID3.CL
 * @since 1.0.0
 */
class CotizacionJwt extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%cotizacion_jwt}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['hash', 'jwt', 'raiz_url'], 'required'],
            [['hash'], 'string', 'max' => 12],
            [['raiz_url'], 'string', 'max' => 255],
            [['usado'], 'default', 'value' => 0],
            [['usado', 'created_at', 'expires_at'], 'integer'],
            [['jwt'], 'string'],
            [['hash'], 'unique'],
            [['hash'], 'match', 'pattern' => '/^[a-f0-9]{12}$/i', 'message' => 'El hash debe ser un SHA256 válido.'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'hash' => 'Hash',
            'jwt' => 'JWT',
            'raiz_url' => 'URL Raíz',
            'usado' => 'Usado',
            'created_at' => 'Fecha de Creación',
            'expires_at' => 'Fecha de Expiración',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function beforeSave($insert): bool
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->created_at = time();
            }
            return true;
        }
        return false;
    }

    /**
     * Verifica si el registro ha expirado.
     * 
     * @return bool True si ha expirado, false en caso contrario.
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }
        return $this->expires_at < time();
    }

    /**
     * Genera un hash único para el JWT.
     * 
     * @param string $jwt El JWT completo.
     * @param string $raizUrl La URL raíz.
     * @return string El hash generado.
     */
    public static function generateHash(string $jwt, string $raizUrl): string
    {
        return substr(hash('sha256', $jwt . $raizUrl . time() . random_bytes(16)), 0, 12);
    }

    /**
     * Busca un registro por hash y verifica su validez.
     * 
     * @param string $hash El hash a buscar.
     * @return self|null El modelo encontrado o null si no existe o es inválido.
     */
    public static function findByHash(string $hash): ?self
    {
        $record = self::findOne(['hash' => $hash]);
        
        if ($record === null) {
            return null;
        }

        if ($record->isExpired()) {
            return null;
        }

        return $record;
    }

    /**
     * Marca el registro como usado.
     * 
     * @return bool True si se actualizó correctamente.
     */
    public function markAsUsed(): bool
    {
        $this->usado = 1;
        return $this->save(false);
    }

    /**
     * Genera la URL de validación completa.
     * 
     * @return string La URL completa de validación.
     */
    public function getValidacionUrl(): string
    {
        return rtrim($this->raiz_url, '/') . '/cotizacion/validacion?qr=' . $this->hash;
    }
}
