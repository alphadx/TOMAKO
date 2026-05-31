<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Servicio. Mapea la tabla {{%servicio}}.
 * Registra automáticamente el historial de precios cuando cambia precio_base.
 *
 * @property int         $id
 * @property string      $codigo
 * @property string      $nombre
 * @property string|null $descripcion
 * @property float       $precio_base
 * @property int|null    $duracion_estimada  minutos
 * @property int         $categoria_id
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Categoria       $categoria
 * @property-read HistorialPrecio[] $historialPrecios
 */
class Servicio extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%servicio}}';
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
            [['codigo', 'nombre', 'categoria_id'], 'required'],
            ['codigo', 'string', 'max' => 20],
            ['codigo', 'unique'],
            ['nombre', 'string', 'max' => 150],
            ['nombre', 'unique'],
            ['descripcion', 'string', 'max' => 500],
            ['precio_base', 'number', 'min' => 0],
            ['precio_base', 'default', 'value' => 0.00],
            ['duracion_estimada', 'integer', 'min' => 0],
            ['categoria_id', 'integer'],
            ['categoria_id', 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
            ['status', 'boolean'],
            ['status', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                => 'ID',
            'codigo'            => 'Código',
            'nombre'            => 'Nombre',
            'descripcion'       => 'Descripción',
            'precio_base'       => 'Precio Base ($)',
            'duracion_estimada' => 'Duración Estimada (min)',
            'categoria_id'      => 'Categoría',
            'status'            => 'Estado',
            'created_at'        => 'Creado',
            'updated_at'        => 'Actualizado',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        // Registrar historial de precio si cambió
        if (!$insert && $this->isAttributeChanged('precio_base')) {
            $this->_precioAnterior = (float) $this->getOldAttribute('precio_base');
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /** @var float|null Precio anterior guardado antes del save para el historial. */
    private ?float $_precioAnterior = null;

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        if (!$insert && $this->_precioAnterior !== null) {
            $usuarioId = Yii::$app->has('user') && !Yii::$app->user->isGuest
                ? (int) Yii::$app->user->id
                : null;

            $historial = new HistorialPrecio();
            $historial->servicio_id     = $this->id;
            $historial->precio_anterior = $this->_precioAnterior;
            $historial->precio_nuevo    = (float) $this->precio_base;
            $historial->usuario_id      = $usuarioId;
            $historial->created_at      = time();
            $historial->save(false);

            $this->_precioAnterior = null;
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getCategoria(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    public function getHistorialPrecios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(HistorialPrecio::class, ['servicio_id' => 'id'])->orderBy(['created_at' => SORT_DESC]);
    }

    /**
     * HU-028: Plantillas de checklist asociadas a este servicio
     */
    public function getPlantillasChecklist(): \yii\db\ActiveQuery
    {
        return $this->hasMany(PlantillaChecklist::class, ['servicio_id' => 'id'])
            ->orderBy(['nombre' => SORT_ASC]);
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Genera el siguiente código de servicio (S-NNNN).
     */
    public static function generarCodigo(): string
    {
        $ultimo = static::find()->orderBy(['id' => SORT_DESC])->select('codigo')->scalar();
        if ($ultimo && preg_match('/S-(\d+)/', (string) $ultimo, $m)) {
            $siguiente = (int) $m[1] + 1;
        } else {
            $siguiente = 1;
        }
        return 'S-' . str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }
}
