<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Marca;

/**
 * MarcaSearch: modelo de búsqueda para el listado de marcas.
 *
 * @property string|null $nombre
 * @property int|null    $status
 */
class MarcaSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre = null;
    public ?int    $status = null;

    public function rules(): array
    {
        return [
            [['nombre'], 'safe'],
            [['status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre' => 'Nombre',
            'status' => 'Estado',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Marca::find();

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['nombre' => SORT_ASC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'nombre', $this->nombre])
              ->andFilterWhere(['status' => $this->status]);

        return $dataProvider;
    }
}
