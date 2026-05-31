<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Tecnico;

/**
 * TecnicoSearch: modelo de búsqueda para el listado de técnicos.
 *
 * @property string|null $nombre
 * @property int|null    $especialidad_id
 * @property int|null    $status
 */
class TecnicoSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre          = null;
    public ?int    $especialidad_id = null;
    public ?int    $status          = null;

    public function rules(): array
    {
        return [
            [['nombre'], 'safe'],
            [['especialidad_id', 'status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre'          => 'Nombre',
            'especialidad_id' => 'Especialidad',
            'status'          => 'Estado',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Tecnico::find();

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['apellido' => SORT_ASC, 'nombre' => SORT_ASC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        if ($this->nombre !== null && $this->nombre !== '') {
            $query->andWhere(['or',
                ['like', 'nombre',   $this->nombre],
                ['like', 'apellido', $this->nombre],
            ]);
        }

        $query->andFilterWhere(['especialidad_id' => $this->especialidad_id])
              ->andFilterWhere(['status'           => $this->status]);

        return $dataProvider;
    }
}
