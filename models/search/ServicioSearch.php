<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Servicio;

/**
 * ServicioSearch: modelo de búsqueda para el listado de servicios.
 *
 * @property string|null $nombre
 * @property string|null $codigo
 * @property int|null    $categoria_id
 * @property int|null    $status
 */
class ServicioSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre       = null;
    public ?string $codigo       = null;
    public ?int    $categoria_id = null;
    public ?int    $status       = null;

    public function rules(): array
    {
        return [
            [['nombre', 'codigo'], 'safe'],
            [['categoria_id', 'status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre'       => 'Nombre',
            'codigo'       => 'Código',
            'categoria_id' => 'Categoría',
            'status'       => 'Estado',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Servicio::find()->with('categoria');

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'nombre', $this->nombre])
              ->andFilterWhere(['like', 'codigo', $this->codigo])
              ->andFilterWhere(['categoria_id' => $this->categoria_id])
              ->andFilterWhere(['status' => $this->status]);

        return $dataProvider;
    }
}
