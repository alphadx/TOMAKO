<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Categoria;

/**
 * CategoriaSearch: modelo de búsqueda para el listado de categorías.
 *
 * @property string|null $nombre
 * @property string|null $tipo
 * @property int|null    $status
 * @property int|null    $padre_id
 */
class CategoriaSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre   = null;
    public ?string $tipo     = null;
    public ?int    $status   = null;
    public ?int    $padre_id = null;

    public function rules(): array
    {
        return [
            [['nombre', 'tipo'], 'safe'],
            [['status', 'padre_id'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre'   => 'Nombre',
            'tipo'     => 'Tipo',
            'status'   => 'Estado',
            'padre_id' => 'Categoría Padre',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Categoria::find()->with('padre');

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['orden' => SORT_ASC, 'nombre' => SORT_ASC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'nombre', $this->nombre])
              ->andFilterWhere(['tipo'     => $this->tipo])
              ->andFilterWhere(['status'   => $this->status])
              ->andFilterWhere(['padre_id' => $this->padre_id]);

        return $dataProvider;
    }
}
