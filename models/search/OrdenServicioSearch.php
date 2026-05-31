<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\OrdenServicio;

/**
 * OrdenServicioSearch: modelo de búsqueda para órdenes de servicio.
 *
 * @property string|null $codigo
 * @property int|null    $cliente_id
 * @property int|null    $vehiculo_id
 * @property string|null $estado
 * @property string|null $prioridad
 * @property string|null $fecha_desde
 * @property string|null $fecha_hasta
 * @property string|null $buscar
 */
class OrdenServicioSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $codigo      = null;
    public ?int    $cliente_id  = null;
    public ?int    $vehiculo_id = null;
    public ?string $estado      = null;
    public ?string $prioridad   = null;
    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;
    public ?string $buscar      = null;

    public function rules(): array
    {
        return [
            [['codigo', 'estado', 'prioridad', 'fecha_desde', 'fecha_hasta', 'buscar'], 'safe'],
            [['cliente_id', 'vehiculo_id'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'codigo'      => 'Código',
            'cliente_id'  => 'Cliente',
            'vehiculo_id' => 'Vehículo',
            'estado'      => 'Estado',
            'prioridad'   => 'Prioridad',
            'fecha_desde' => 'Desde',
            'fecha_hasta' => 'Hasta',
            'buscar'      => 'Buscar',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = OrdenServicio::find()
            ->alias('os')
            ->joinWith(['cliente cl', 'vehiculo vh']);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => [
                'defaultOrder' => ['created_at' => SORT_DESC],
                'attributes'   => ['codigo', 'estado', 'prioridad', 'total', 'created_at'],
            ],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        if ($this->codigo) {
            $query->andWhere(['like', 'os.codigo', $this->codigo]);
        }
        if ($this->cliente_id) {
            $query->andWhere(['os.cliente_id' => $this->cliente_id]);
        }
        if ($this->vehiculo_id) {
            $query->andWhere(['os.vehiculo_id' => $this->vehiculo_id]);
        }
        if ($this->estado) {
            $query->andWhere(['os.estado' => $this->estado]);
        }
        if ($this->prioridad) {
            $query->andWhere(['os.prioridad' => $this->prioridad]);
        }
        if ($this->fecha_desde) {
            $query->andWhere(['>=', 'FROM_UNIXTIME(os.created_at, \'%Y-%m-%d\')', $this->fecha_desde]);
        }
        if ($this->fecha_hasta) {
            $query->andWhere(['<=', 'FROM_UNIXTIME(os.created_at, \'%Y-%m-%d\')', $this->fecha_hasta]);
        }
        if ($this->buscar !== null && $this->buscar !== '') {
            $query->andWhere(['or',
                ['like', 'os.codigo',    $this->buscar],
                ['like', 'cl.nombre',    $this->buscar],
                ['like', 'cl.apellido',  $this->buscar],
                ['like', 'vh.patente',   $this->buscar],
            ]);
        }

        return $dataProvider;
    }
}
