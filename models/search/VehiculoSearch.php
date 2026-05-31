<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Vehiculo;

/**
 * VehiculoSearch: modelo de búsqueda para el listado de vehículos.
 *
 * @property string|null $patente
 * @property string|null $marca
 * @property string|null $modelo
 * @property string|null $vin
 * @property int|null    $cliente_id
 * @property int|null    $status
 * @property string|null $propietario
 */
class VehiculoSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $patente     = null;
    public ?string $marca       = null;
    public ?string $modelo      = null;
    public ?string $vin         = null;
    public ?int    $cliente_id  = null;
    public ?int    $status      = null;
    public ?string $propietario = null;

    public function rules(): array
    {
        return [
            [['patente', 'marca', 'modelo', 'vin', 'propietario'], 'safe'],
            [['cliente_id', 'status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'patente'     => 'Patente',
            'marca'       => 'Marca',
            'modelo'      => 'Modelo',
            'vin'         => 'VIN',
            'cliente_id'  => 'Propietario',
            'propietario' => 'Nombre Propietario',
            'status'      => 'Estado',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Vehiculo::find()
            ->alias('vh')
            ->joinWith(['cliente cl']);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes'   => ['patente', 'marca', 'modelo', 'created_at'],
            ],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'vh.patente', $this->patente])
              ->andFilterWhere(['like', 'vh.marca',   $this->marca])
              ->andFilterWhere(['like', 'vh.modelo',  $this->modelo])
              ->andFilterWhere(['like', 'vh.vin',     $this->vin])
              ->andFilterWhere(['vh.cliente_id'       => $this->cliente_id])
              ->andFilterWhere(['vh.status'           => $this->status]);

        if ($this->propietario !== null && $this->propietario !== '') {
            $query->andFilterWhere(['like', 'cl.nombre', $this->propietario]);
        }

        return $dataProvider;
    }
}
