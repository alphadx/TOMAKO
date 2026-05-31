<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Cita;
use app\models\Cliente;

/**
 * CitaSearch: modelo de búsqueda para el listado de citas.
 *
 * @property string|null $fecha_desde
 * @property string|null $fecha_hasta
 * @property string|null $estado
 * @property int|null    $cliente_id
 * @property string|null $buscar
 */
class CitaSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;
    public array|string|null $estado = null;
    public ?int    $cliente_id  = null;
    public ?string $buscar      = null;
    public int $ver_canceladas = 0;

    public function rules(): array
    {
        return [
            [['fecha_desde', 'fecha_hasta', 'estado', 'buscar'], 'safe'],
            [['cliente_id', 'ver_canceladas'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'fecha_desde' => 'Desde',
            'fecha_hasta' => 'Hasta',
            'estado'      => 'Estado',
            'cliente_id'  => 'Cliente',
            'buscar'      => 'Buscar',
            'ver_canceladas' => 'Ver canceladas/no-show',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Cita::find()
            ->alias('ci')
            ->joinWith(['cliente cl', 'vehiculo vh']);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => [
                'defaultOrder' => ['fecha' => SORT_ASC, 'hora_inicio' => SORT_ASC],
                'attributes'   => ['fecha', 'hora_inicio', 'estado'],
            ],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        if ($this->fecha_desde) {
            $query->andWhere(['>=', 'ci.fecha', $this->fecha_desde]);
        }
        if ($this->fecha_hasta) {
            $query->andWhere(['<=', 'ci.fecha', $this->fecha_hasta]);
        }
        if ($this->estado) {
            $estados = is_array($this->estado) ? array_filter($this->estado) : [$this->estado];
            if (!empty($estados)) {
                $query->andWhere(['in', 'ci.estado', $estados]);
            }
        }
        if ($this->cliente_id) {
            $query->andWhere(['ci.cliente_id' => $this->cliente_id]);
        }
        if ($this->buscar !== null && $this->buscar !== '') {
            $query->andWhere(['or',
                ['like', 'cl.nombre',    $this->buscar],
                ['like', 'cl.apellido',  $this->buscar],
                ['like', 'vh.patente',   $this->buscar],
            ]);
        }

        if ((int) $this->ver_canceladas !== 1) {
            $query->andWhere(['not', ['ci.estado' => ['cancelada', 'no_show']]]);
        }

        return $dataProvider;
    }
}
