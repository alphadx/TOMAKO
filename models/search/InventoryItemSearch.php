<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\InventoryItem;

/**
 * InventoryItemSearch: modelo de búsqueda para el listado de inventario.
 *
 * @property string|null $nombre
 * @property string|null $sku
 * @property int|null    $categoria_id
 * @property int|null    $status
 * @property string|null $estado_stock  sin_stock|bajo|en_stock
 */
class InventoryItemSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre       = null;
    public ?string $sku          = null;
    public ?int    $categoria_id = null;
    public ?int    $status       = null;
    public ?string $estado_stock = null;

    public function rules(): array
    {
        return [
            [['nombre', 'sku', 'estado_stock'], 'safe'],
            [['categoria_id', 'status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre'       => 'Nombre',
            'sku'          => 'SKU',
            'categoria_id' => 'Categoría',
            'status'       => 'Estado',
            'estado_stock' => 'Estado de Stock',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = InventoryItem::find();

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['nombre' => SORT_ASC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'nombre',       $this->nombre])
              ->andFilterWhere(['like', 'sku',           $this->sku])
              ->andFilterWhere(['categoria_id'           => $this->categoria_id])
              ->andFilterWhere(['status'                 => $this->status]);

        // Filtro virtual por estado de stock
        if ($this->estado_stock !== null && $this->estado_stock !== '') {
            switch ($this->estado_stock) {
                case 'sin_stock':
                    $query->andWhere(['<=', 'cantidad', 0]);
                    break;
                case 'bajo':
                    $query->andWhere(['>', 'cantidad', 0])
                          ->andWhere(['<=', new \yii\db\Expression('cantidad'), new \yii\db\Expression('stock_minimo')]);
                    break;
                case 'en_stock':
                    $query->andWhere(['>', 'cantidad', new \yii\db\Expression('stock_minimo')]);
                    break;
            }
        }

        return $dataProvider;
    }
}
