<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use app\models\Etiqueta;

/**
 * EtiquetaSearch: modelo de búsqueda para el listado de etiquetas.
 */
class EtiquetaSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre = null;
    public ?string $color  = null;
    public ?int    $status = null;

    public function rules(): array
    {
        return [
            [['nombre', 'color'], 'safe'],
            [['status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre' => 'Nombre',
            'color'  => 'Color',
            'status' => 'Estado',
        ];
    }

    public function search(array $params): DataProviderInterface
    {
        $query = Etiqueta::find();

        try {
            $dataProvider = new ActiveDataProvider([
                'query'      => $query,
                'pagination' => ['pageSize' => 20],
                'sort'       => ['defaultOrder' => ['nombre' => SORT_ASC]],
            ]);
        } catch (\Throwable $exception) {
            return new ArrayDataProvider([
                'allModels'  => [],
                'pagination' => ['pageSize' => 20],
                'sort'       => false,
            ]);
        }

        if (!$this->loadSanitized($params) || !$this->validate()) {
            $query->andWhere(['status' => 1]);
            return $dataProvider;
        }

        if ($this->status === null || $this->status === '') {
            $query->andWhere(['status' => 1]);
        }

        $query->andFilterWhere(['like', 'nombre', $this->nombre])
              ->andFilterWhere(['color' => $this->color])
              ->andFilterWhere(['status' => $this->status]);

        return $dataProvider;
    }
}
