<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Etiqueta. Mapea la tabla {{%etiqueta}}.
 * Representa una etiqueta para segmentación de clientes.
 *
 * @property int         $id
 * @property string      $nombre
 * @property string      $color
 * @property string|null $descripcion
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 */
class Etiqueta extends ActiveRecord
{
    public const COLOR_PRIMARY = 'primary';
    public const COLOR_SUCCESS = 'success';
    public const COLOR_INFO = 'info';
    public const COLOR_WARNING = 'warning';
    public const COLOR_DANGER = 'danger';
    public const COLOR_SECONDARY = 'secondary';
    public const COLOR_DARK = 'dark';
    public const COLOR_LIGHT = 'light';

    public static function tableName(): string
    {
        return '{{%etiqueta}}';
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
            [['nombre', 'color'], 'required'],
            ['nombre', 'string', 'max' => 50],
            ['nombre', 'unique'],
            ['color', 'string', 'max' => 20],
            ['color', 'in', 'range' => array_keys(self::getColoresList())],
            ['descripcion', 'string'],
            ['status', 'boolean'],
            ['status', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'nombre'      => 'Nombre',
            'color'       => 'Color',
            'descripcion' => 'Descripción',
            'status'      => 'Estado',
            'created_at'  => 'Creado',
            'updated_at'  => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->nombre = trim($this->nombre);
        
        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /**
     * Obtiene las etiquetas asociadas a clientes.
     */
    public function getClienteEtiquetas(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ClienteEtiqueta::class, ['etiqueta_id' => 'id']);
    }

    /**
     * Obtiene los clientes asociados a esta etiqueta.
     */
    public function getClientes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Cliente::class, ['id' => 'cliente_id'])->via('clienteEtiquetas');
    }

    /**
     * Lista de colores disponibles para etiquetas.
     *
     * @return array<string,string>
     */
    public static function getColoresList(): array
    {
        return [
            self::COLOR_PRIMARY   => 'Azul',
            self::COLOR_SUCCESS   => 'Verde',
            self::COLOR_INFO      => 'Celeste',
            self::COLOR_WARNING   => 'Amarillo',
            self::COLOR_DANGER    => 'Rojo',
            self::COLOR_SECONDARY => 'Gris',
            self::COLOR_DARK      => 'Negro',
            self::COLOR_LIGHT     => 'Blanco',
        ];
    }

    /**
     * Retorna la clase CSS para el badge según el color.
     */
    public function getBadgeClass(): string
    {
        return 'badge bg-' . $this->color;
    }

    /**
     * Cuenta cuántos clientes tienen esta etiqueta.
     */
    public function getCountClientes(): int
    {
        return $this->getClienteEtiquetas()->count();
    }
}
