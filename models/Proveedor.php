<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * This is the model class for table "proveedor".
 *
 * @property int $id
 * @property string $nombre Nombre o razón social del proveedor
 * @property string|null $rut RUT/NIF del proveedor
 * @property string|null $email Correo electrónico de contacto
 * @property string|null $telefono Teléfono de contacto
 * @property string|null $celular Celular de contacto
 * @property string|null $direccion Dirección fiscal/comercial
 * @property string|null $ciudad Ciudad
 * @property string|null $region Región/Estado
 * @property string|null $pais País
 * @property string|null $codigo_postal Código postal
 * @property string|null $sitio_web Sitio web
 * @property string|null $persona_contacto Nombre de persona de contacto
 * @property string|null $cargo_contacto Cargo de la persona de contacto
 * @property string|null $categoria Categoría del proveedor (Repuestos, Herramientas, Servicios, etc.)
 * @property int|null $tiempo_entrega_promedio Tiempo promedio de entrega en días
 * @property string|null $calificacion Calificación promedio (0-5)
 * @property bool $activo Indica si el proveedor está activo
 * @property string|null $observaciones Observaciones adicionales
 * @property string|null $created_at Fecha de creación
 * @property string|null $updated_at Fecha de actualización
 * @property int|null $created_by Usuario que creó el registro
 * @property int|null $updated_by Usuario que actualizó el registro
 *
 * @property \app\models\Usuario $createdBy
 * @property \app\models\Usuario $updatedBy
 */
class Proveedor extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%proveedor}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'timestamp' => [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
            'audit' => [
                'class' => AuditBehavior::class,
                'excludedAttributes' => ['created_at', 'updated_at'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre'], 'required'],
            [['tiempo_entrega_promedio', 'created_by', 'updated_by'], 'integer'],
            [['activo'], 'boolean'],
            [['calificacion'], 'number', 'max' => 5.00, 'min' => 0.00],
            [['nombre', 'persona_contacto', 'cargo_contacto', 'categoria'], 'string', 'max' => 150],
            [['rut', 'telefono', 'celular', 'codigo_postal'], 'string', 'max' => 20],
            [['email', 'sitio_web'], 'string', 'max' => 100],
            [['ciudad', 'region'], 'string', 'max' => 100],
            [['pais'], 'string', 'max' => 50],
            [['direccion'], 'string', 'max' => 200],
            [['observaciones'], 'string'],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['rut'], 'unique'],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['created_by' => 'id']],
            [['updated_by'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['updated_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre / Razón Social',
            'rut' => 'RUT / NIF',
            'email' => 'Email',
            'telefono' => 'Teléfono',
            'celular' => 'Celular',
            'direccion' => 'Dirección',
            'ciudad' => 'Ciudad',
            'region' => 'Región',
            'pais' => 'País',
            'codigo_postal' => 'Código Postal',
            'sitio_web' => 'Sitio Web',
            'persona_contacto' => 'Persona de Contacto',
            'cargo_contacto' => 'Cargo Contacto',
            'categoria' => 'Categoría',
            'tiempo_entrega_promedio' => 'Tiempo Entrega (días)',
            'calificacion' => 'Calificación',
            'activo' => 'Activo',
            'observaciones' => 'Observaciones',
            'created_at' => 'Fecha Creación',
            'updated_at' => 'Fecha Actualización',
            'created_by' => 'Creado Por',
            'updated_by' => 'Actualizado Por',
        ];
    }

    /**
     * Relación con usuario creador
     */
    public function getCreatedBy()
    {
        return $this->hasOne(Usuario::class, ['id' => 'created_by']);
    }

    /**
     * Relación con usuario actualizador
     */
    public function getUpdatedBy()
    {
        return $this->hasOne(Usuario::class, ['id' => 'updated_by']);
    }

    /**
     * Retorna proveedores activos ordenados por nombre
     */
    public static function getListaActivos()
    {
        return self::find()
            ->where(['activo' => true])
            ->orderBy(['nombre' => SORT_ASC])
            ->all();
    }

    /**
     * Retorna opciones para dropdown
     */
    public static function getListaParaDropdown()
    {
        $proveedores = self::getListaActivos();
        $lista = [];
        foreach ($proveedores as $prov) {
            $lista[$prov->id] = $prov->nombre;
        }
        return $lista;
    }

    /**
     * Calcula y actualiza la calificación promedio basada en evaluaciones
     * Este método puede ser extendido cuando se implemente el módulo de evaluación
     */
    public function actualizarCalificacion()
    {
        // Placeholder para futura implementación de evaluaciones de proveedores
        // Se conectará con órdenes de compra y recepción para calcular métricas
        return $this->calificacion;
    }
}
