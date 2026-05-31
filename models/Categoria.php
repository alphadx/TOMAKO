<?php
declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use app\components\behaviors\AuditBehavior;

/**
 * Modelo Categoría. Mapea la tabla {{%categoria}}.
 * Soporta jerarquía (padre_id) y previene ciclos.
 *
 * @property int         $id
 * @property string      $nombre
 * @property string|null $descripcion
 * @property int|null    $padre_id
 * @property string      $tipo         ('servicio'|'insumo'|'ambos')
 * @property string|null $icono
 * @property string|null $color
 * @property int         $orden
 * @property int         $status
 * @property int|null    $created_at
 * @property int|null    $updated_at
 *
 * @property-read Categoria|null   $padre
 * @property-read Categoria[]      $hijos
 * @property-read Servicio[]       $servicios
 * @property-read InventoryItem[]  $inventoryItems
 */
class Categoria extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%categoria}}';
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
            [['nombre', 'tipo'], 'required'],
            ['nombre', 'string', 'max' => 100],
            ['nombre', 'unique'],
            ['descripcion', 'string'],
            ['padre_id', 'integer'],
            ['padre_id', 'exist', 'skipOnError' => true, 'targetClass' => self::class, 'targetAttribute' => ['padre_id' => 'id']],
            ['padre_id', 'validateNoCiclo'],
            ['tipo', 'in', 'range' => ['servicio', 'insumo', 'ambos']],
            ['tipo', 'default', 'value' => 'ambos'],
            ['icono', 'string', 'max' => 50],
            ['color', 'string', 'max' => 7],
            ['orden', 'integer'],
            ['orden', 'default', 'value' => 0],
            ['status', 'boolean'],
            ['status', 'default', 'value' => 1],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'nombre'      => 'Nombre',
            'descripcion' => 'Descripción',
            'padre_id'    => 'Categoría Padre',
            'tipo'        => 'Tipo',
            'icono'       => 'Ícono',
            'color'       => 'Color',
            'orden'       => 'Orden',
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
        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;
        return true;
    }

    /**
     * Valida que la categoría no sea ancestro de sí misma (previene ciclos).
     */
    public function validateNoCiclo(string $attribute): void
    {
        if (empty($this->$attribute) || $this->isNewRecord) {
            return;
        }
        if ((int) $this->$attribute === (int) $this->id) {
            $this->addError($attribute, 'Una categoría no puede ser su propia padre.');
            return;
        }
        // Recorremos hacia arriba desde el padre propuesto
        $padreId = (int) $this->$attribute;
        $visitados = [(int) $this->id];
        while ($padreId !== 0) {
            if (in_array($padreId, $visitados, true)) {
                $this->addError($attribute, 'La asignación crearía un ciclo en la jerarquía de categorías.');
                return;
            }
            $visitados[] = $padreId;
            $padre = static::findOne($padreId);
            $padreId = $padre ? (int) $padre->padre_id : 0;
        }
    }

    // ── Relaciones ────────────────────────────────────────────────────────────

    public function getPadre(): \yii\db\ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'padre_id']);
    }

    public function getHijos(): \yii\db\ActiveQuery
    {
        return $this->hasMany(self::class, ['padre_id' => 'id']);
    }

    public function getServicios(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Servicio::class, ['categoria_id' => 'id']);
    }

    public function getInventoryItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(InventoryItem::class, ['categoria_id' => 'id']);
    }

    // ── Métodos estáticos ─────────────────────────────────────────────────────

    /**
     * Retorna el árbol de categorías como array plano con indentación para dropdowns.
     *
     * @param string|null $tipo  Filtrar por tipo ('servicio','insumo','ambos') o null para todos.
     * @return array<int,string>
     */
    public static function getTree(?string $tipo = null): array
    {
        $query = static::find()->where(['status' => 1])->orderBy(['padre_id' => SORT_ASC, 'orden' => SORT_ASC, 'nombre' => SORT_ASC]);
        if ($tipo !== null) {
            $query->andWhere(['IN', 'tipo', [$tipo, 'ambos']]);
        }
        /** @var self[] $all */
        $all = $query->all();

        // Construir mapa id => modelo
        $map = [];
        foreach ($all as $cat) {
            $map[$cat->id] = $cat;
        }

        $resultado = [];
        static::construirArbol($all, $map, null, 0, $resultado);
        return $resultado;
    }

    /**
     * Construye recursivamente el árbol de categorías.
     *
     * @param self[]      $all
     * @param self[]      $map
     * @param int|null    $padreId
     * @param int         $nivel
     * @param array<int,string> $resultado
     */
    private static function construirArbol(array $all, array $map, ?int $padreId, int $nivel, array &$resultado): void
    {
        foreach ($all as $cat) {
            $catPadreId = $cat->padre_id === null ? null : (int) $cat->padre_id;
            if ($catPadreId === $padreId) {
                $prefijo = str_repeat('— ', $nivel);
                $resultado[$cat->id] = $prefijo . $cat->nombre;
                static::construirArbol($all, $map, (int) $cat->id, $nivel + 1, $resultado);
            }
        }
    }

    /**
     * Lista de categorías para dropdowns (todas, sin filtro de status).
     *
     * @return array<int,string>
     */
    public static function getCategoriasList(): array
    {
        return static::find()
            ->where(['status' => 1])
            ->orderBy('nombre')
            ->select(['nombre', 'id'])
            ->indexBy('id')
            ->column();
    }

    /**
     * Etiquetas para el campo tipo.
     *
     * @return array<string,string>
     */
    public static function getTiposList(): array
    {
        return [
            'servicio' => 'Servicio',
            'insumo'   => 'Insumo',
            'ambos'    => 'Ambos',
        ];
    }
}
