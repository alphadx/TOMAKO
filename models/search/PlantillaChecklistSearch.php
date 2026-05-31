<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\PlantillaChecklist;

/**
 * PlantillaChecklistSearch representa el modelo de búsqueda para PlantillaChecklist.
 */
class PlantillaChecklistSearch extends PlantillaChecklist
{
    public function rules(): array
    {
        return [
            [['id', 'servicio_id'], 'integer'],
            [['nombre', 'descripcion'], 'safe'],
            [['activa'], 'boolean'],
        ];
    }

    public function scenarios(): array
    {
        return Model::scenarios();
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = PlantillaChecklist::find()
            ->joinWith(['servicio', 'items'])
            ->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 20,
            ],
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'servicio_id' => $this->servicio_id,
            'activa' => $this->activa,
        ]);

        $query->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'descripcion', $this->descripcion]);

        return $dataProvider;
    }
}
