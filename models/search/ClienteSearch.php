<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ArrayDataProvider;
use yii\data\ActiveDataProvider;
use yii\data\DataProviderInterface;
use app\models\Cliente;

/**
 * ClienteSearch: modelo de búsqueda para el listado de clientes.
 * Incluye filtros avanzados por segmentación (HU-006).
 *
 * @property string|null $nombre
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $rut
 * @property int|null    $status
 * @property string|null $etiqueta_ids
 * @property string|null $fuente
 */
class ClienteSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $nombre       = null;
    public ?string $email        = null;
    public ?string $telefono     = null;
    public ?string $rut          = null;
    public ?int    $status       = null;
    public ?string $etiqueta_ids = null;
    public ?string $fuente       = null;

    public function rules(): array
    {
        return [
            [['nombre', 'email', 'telefono', 'rut', 'etiqueta_ids', 'fuente'], 'safe'],
            [['status'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'nombre'       => 'Nombre',
            'email'        => 'Correo',
            'telefono'     => 'Teléfono',
            'rut'          => 'RUT',
            'status'       => 'Estado',
            'etiqueta_ids' => 'Etiquetas',
            'fuente'       => 'Fuente de Contacto',
        ];
    }

    public function search(array $params): DataProviderInterface
    {
        $query = Cliente::find();

        try {
            $dataProvider = new ActiveDataProvider([
                'query'      => $query,
                'pagination' => ['pageSize' => 20],
                'sort'       => ['defaultOrder' => ['created_at' => SORT_DESC]],
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

        $query->andFilterWhere(['like', 'nombre',   $this->nombre])
              ->andFilterWhere(['like', 'email',    $this->email])
              ->andFilterWhere(['like', 'telefono', $this->telefono])
              ->andFilterWhere(['like', 'rut',      $this->rut])
              ->andFilterWhere(['status' => $this->status])
              ->andFilterWhere(['fuente' => $this->fuente]);

        // Filtro por etiquetas (múltiples IDs separados por coma)
        if (!empty($this->etiqueta_ids)) {
            $etiquetaIdArray = array_map('intval', explode(',', $this->etiqueta_ids));
            $query->innerJoin('cliente_etiqueta', 'cliente_etiqueta.cliente_id = cliente.id')
                  ->andWhere(['cliente_etiqueta.etiqueta_id' => $etiquetaIdArray]);
        }

        return $dataProvider;
    }
}
