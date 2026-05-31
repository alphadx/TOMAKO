<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Pago;

/**
 * PagoSearch: modelo de búsqueda para pagos.
 *
 * @property int|null    $orden_id
 * @property string|null $metodo_pago
 * @property string|null $estado
 * @property string|null $fecha_desde
 * @property string|null $fecha_hasta
 * @property string|null $buscar
 */
class PagoSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?int    $orden_id    = null;
    public ?string $metodo_pago = null;
    public ?string $estado      = null;
    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;
    public ?string $buscar      = null;

    public function rules(): array
    {
        return [
            [['metodo_pago', 'estado', 'fecha_desde', 'fecha_hasta', 'buscar'], 'safe'],
            [['orden_id'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'orden_id'    => 'Orden',
            'metodo_pago' => 'Método de Pago',
            'estado'      => 'Estado',
            'fecha_desde' => 'Desde',
            'fecha_hasta' => 'Hasta',
            'buscar'      => 'Buscar',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = Pago::find()
            ->alias('p')
            ->joinWith(['orden os', 'orden.cliente cl']);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['created_at' => SORT_DESC]],
        ]);

        $this->loadSanitized($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->orden_id !== null) {
            $query->andWhere(['p.orden_id' => $this->orden_id]);
        }

        if ($this->metodo_pago !== null && $this->metodo_pago !== '') {
            $query->andWhere(['p.metodo_pago' => $this->metodo_pago]);
        }

        if ($this->estado !== null && $this->estado !== '') {
            $query->andWhere(['p.estado' => $this->estado]);
        }

        if ($this->fecha_desde !== null && $this->fecha_desde !== '') {
            $query->andWhere(['>=', 'p.created_at', strtotime($this->fecha_desde)]);
        }

        if ($this->fecha_hasta !== null && $this->fecha_hasta !== '') {
            $query->andWhere(['<=', 'p.created_at', strtotime($this->fecha_hasta . ' 23:59:59')]);
        }

        if ($this->buscar !== null && $this->buscar !== '') {
            $query->andWhere([
                'or',
                ['like', 'os.codigo', $this->buscar],
                ['like', 'cl.nombre', $this->buscar],
                ['like', 'cl.apellido', $this->buscar],
                ['like', 'p.referencia', $this->buscar],
            ]);
        }

        return $dataProvider;
    }
}
