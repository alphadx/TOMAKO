<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\EvaluacionProveedor;

/**
 * EvaluacionProveedorSearch representa el modelo de búsqueda para evaluaciones de proveedores.
 */
class EvaluacionProveedorSearch extends EvaluacionProveedor
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'proveedor_id', 'orden_compra_id', 'periodo_mes', 'periodo_anio'], 'integer'],
            [['puntualidad', 'calidad_producto', 'atencion_servicio', 'precio_competitividad', 'flexibilidad'], 'integer'],
            [['puntaje_total', 'puntaje_promedio'], 'number'],
            [['fecha_evaluacion', 'comentarios'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
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
        $query = EvaluacionProveedor::find()
            ->joinWith(['proveedor', 'ordenCompra', 'evaluadoPor'])
            ->orderBy(['fecha_evaluacion' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['fecha_evaluacion' => SORT_DESC]
            ],
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'orden_compra_id' => $this->orden_compra_id,
            'fecha_evaluacion' => $this->fecha_evaluacion,
            'periodo_mes' => $this->periodo_mes,
            'periodo_anio' => $this->periodo_anio,
            'puntualidad' => $this->puntualidad,
            'calidad_producto' => $this->calidad_producto,
            'atencion_servicio' => $this->atencion_servicio,
            'precio_competitividad' => $this->precio_competitividad,
            'flexibilidad' => $this->flexibilidad,
            'puntaje_total' => $this->puntaje_total,
            'puntaje_promedio' => $this->puntaje_promedio,
            'evaluado_por' => $this->evaluado_por,
        ]);

        $query->andFilterWhere(['like', 'comentarios', $this->comentarios]);

        return $dataProvider;
    }

    /**
     * Search para reportes de evaluaciones por período
     */
    public function searchReporte($params)
    {
        $query = EvaluacionProveedor::find()
            ->select(['proveedor_id', 'AVG(puntaje_promedio) as puntaje_prom', 'COUNT(*) as cantidad_evaluaciones'])
            ->groupBy('proveedor_id')
            ->joinWith('proveedor');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => ['puntaje_prom' => SORT_DESC]
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtros por período
        if ($this->periodo_mes !== null && $this->periodo_anio !== null) {
            $query->andWhere(['periodo_mes' => $this->periodo_mes, 'periodo_anio' => $this->periodo_anio]);
        } elseif ($this->periodo_anio !== null) {
            $query->andWhere(['periodo_anio' => $this->periodo_anio]);
        }

        // Filtro por fecha específica
        if ($this->fecha_evaluacion !== null) {
            $query->andFilterWhere(['fecha_evaluacion' => $this->fecha_evaluacion]);
        }

        return $dataProvider;
    }
}
