<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Proveedor;

/**
 * ProveedorSearch representa el modelo de búsqueda para la clase Proveedor.
 */
class ProveedorSearch extends Proveedor
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'tiempo_entrega_promedio', 'created_by', 'updated_by'], 'integer'],
            [['activo'], 'boolean'],
            [['nombre', 'rut', 'email', 'telefono', 'celular', 'direccion', 'ciudad', 'region', 'pais', 'codigo_postal', 'sitio_web', 'persona_contacto', 'cargo_contacto', 'categoria', 'calificacion', 'observaciones', 'created_at', 'updated_at'], 'safe'],
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
     * Crea instancia de proveedor con filtros de búsqueda
     * @param array $params parámetros de búsqueda
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Proveedor::find();

        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['nombre' => SORT_ASC],
                'attributes' => ['nombre', 'categoria', 'calificacion', 'activo', 'created_at'],
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        // grid filtering conditions
        $query->andFilterWhere(['like', 'nombre', $this->nombre])
            ->andFilterWhere(['like', 'rut', $this->rut])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'telefono', $this->telefono])
            ->andFilterWhere(['like', 'celular', $this->celular])
            ->andFilterWhere(['like', 'direccion', $this->direccion])
            ->andFilterWhere(['like', 'ciudad', $this->ciudad])
            ->andFilterWhere(['like', 'region', $this->region])
            ->andFilterWhere(['like', 'pais', $this->pais])
            ->andFilterWhere(['like', 'codigo_postal', $this->codigo_postal])
            ->andFilterWhere(['like', 'sitio_web', $this->sitio_web])
            ->andFilterWhere(['like', 'persona_contacto', $this->persona_contacto])
            ->andFilterWhere(['like', 'cargo_contacto', $this->cargo_contacto])
            ->andFilterWhere(['like', 'categoria', $this->categoria])
            ->andFilterWhere(['like', 'observaciones', $this->observaciones]);

        $query->andFilterWhere(['=', 'id', $this->id])
            ->andFilterWhere(['=', 'tiempo_entrega_promedio', $this->tiempo_entrega_promedio])
            ->andFilterWhere(['=', 'activo', $this->activo])
            ->andFilterWhere(['=', 'calificacion', $this->calificacion])
            ->andFilterWhere(['=', 'created_by', $this->created_by])
            ->andFilterWhere(['=', 'updated_by', $this->updated_by]);

        // Filtro por fecha de creación (rango)
        if ($this->created_at) {
            $query->andFilterWhere(['like', 'created_at', $this->created_at]);
        }

        return $dataProvider;
    }
}
