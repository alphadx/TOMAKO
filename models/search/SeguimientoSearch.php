<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use app\models\Seguimiento;

/**
 * SeguimientoSearch: modelo de búsqueda para seguimientos post-servicio.
 *
 * @property int|null    $orden_servicio_id
 * @property int|null    $cliente_id
 * @property string|null $tipo
 * @property string|null $estado
 * @property int|null    $fecha_programada_desde
 * @property int|null    $fecha_programada_hasta
 * @property int|null    $realizado_por
 * @property int|null    $satisfaccion_min
 * @property int|null    $satisfaccion_max
 */
class SeguimientoSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?int $orden_servicio_id = null;
    public ?int $cliente_id = null;
    public ?string $tipo = null;
    public ?string $estado = null;
    public ?int $fecha_programada_desde = null;
    public ?int $fecha_programada_hasta = null;
    public ?int $realizado_por = null;
    public ?int $satisfaccion_min = null;
    public ?int $satisfaccion_max = null;

    public function rules(): array
    {
        return [
            [['orden_servicio_id', 'cliente_id', 'realizado_por'], 'integer'],
            [['tipo', 'estado'], 'safe'],
            [['fecha_programada_desde', 'fecha_programada_hasta'], 'integer'],
            [['satisfaccion_min', 'satisfaccion_max'], 'integer', 'min' => 1, 'max' => 5],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'orden_servicio_id' => 'Orden de Servicio',
            'cliente_id' => 'Cliente',
            'tipo' => 'Tipo',
            'estado' => 'Estado',
            'fecha_programada_desde' => 'Fecha Programada Desde',
            'fecha_programada_hasta' => 'Fecha Programada Hasta',
            'realizado_por' => 'Realizado Por',
            'satisfaccion_min' => 'Satisfacción Mínima',
            'satisfaccion_max' => 'Satisfacción Máxima',
        ];
    }

    public function search(array $params): DataProviderInterface
    {
        $query = Seguimiento::find()
            ->joinWith(['ordenServicio', 'cliente', 'realizadoPor']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
            'sort' => ['defaultOrder' => ['fecha_programada' => SORT_DESC]],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Filtros exactos
        if ($this->orden_servicio_id !== null) {
            $query->andFilterWhere(['seguimiento.orden_servicio_id' => $this->orden_servicio_id]);
        }

        if ($this->cliente_id !== null) {
            $query->andFilterWhere(['seguimiento.cliente_id' => $this->cliente_id]);
        }

        if ($this->realizado_por !== null) {
            $query->andFilterWhere(['seguimiento.realizado_por' => $this->realizado_por]);
        }

        // Filtros por rango
        if ($this->estado !== null && $this->estado !== '') {
            $query->andFilterWhere(['seguimiento.estado' => $this->estado]);
        }

        if ($this->tipo !== null && $this->tipo !== '') {
            $query->andFilterWhere(['seguimiento.tipo' => $this->tipo]);
        }

        // Filtros por fecha programada
        if ($this->fecha_programada_desde !== null) {
            $query->andFilterWhere(['>=', 'seguimiento.fecha_programada', $this->fecha_programada_desde]);
        }

        if ($this->fecha_programada_hasta !== null) {
            // Sumar un día para incluir la fecha completa
            $fechaHasta = strtotime('+1 day', $this->fecha_programada_hasta);
            $query->andFilterWhere(['<', 'seguimiento.fecha_programada', $fechaHasta]);
        }

        // Filtros por satisfacción
        if ($this->satisfaccion_min !== null) {
            $query->andFilterWhere(['>=', 'seguimiento.satisfaccion', $this->satisfaccion_min]);
        }

        if ($this->satisfaccion_max !== null) {
            $query->andFilterWhere(['<=', 'seguimiento.satisfaccion', $this->satisfaccion_max]);
        }

        return $dataProvider;
    }
}
