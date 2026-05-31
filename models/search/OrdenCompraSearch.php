<?php

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\OrdenCompra;

/**
 * OrdenCompraSearch representa el modelo de búsqueda para la clase OrdenCompra.
 */
class OrdenCompraSearch extends OrdenCompra
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'proveedor_id', 'created_by', 'updated_by'], 'integer'],
            [['numero_orden', 'fecha_emision', 'fecha_entrega_esperada', 'fecha_entrega_real', 'estado', 'created_at', 'updated_at'], 'safe'],
            [['total_monto'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // Bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = OrdenCompra::find();

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        // load validation rules from Proveedor model
        $this->load($params);

        // check if the model is valid
        if (!$this->validate()) {
            // uncomment the below line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['like', 'numero_orden', $this->numero_orden])
            ->andFilterWhere(['=', 'id', $this->id])
            ->andFilterWhere(['=', 'proveedor_id', $this->proveedor_id])
            ->andFilterWhere(['=', 'estado', $this->estado])
            ->andFilterWhere(['=', 'total_monto', $this->total_monto])
            ->andFilterWhere(['like', 'fecha_emision', $this->fecha_emision])
            ->andFilterWhere(['like', 'fecha_entrega_esperada', $this->fecha_entrega_esperada])
            ->andFilterWhere(['like', 'fecha_entrega_real', $this->fecha_entrega_real]);

        return $dataProvider;
    }

    /**
     * Crea un proveedor de datos con filtros adicionales para el dashboard
     */
    public function searchDashboard($params)
    {
        $query = OrdenCompra::findActivas();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['fecha_emision' => SORT_DESC],
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtros específicos para dashboard
        $query->andFilterWhere(['like', 'numero_orden', $this->numero_orden])
            ->andFilterWhere(['=', 'proveedor_id', $this->proveedor_id])
            ->andFilterWhere(['in', 'estado', $this->estado ? (array)$this->estado : null]);

        return $dataProvider;
    }
}
