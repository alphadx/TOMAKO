<?php
declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Notificacion;

class NotificacionSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $tipo = null;
    public ?string $leida = null;
    public ?string $titulo = null;

    public function rules(): array
    {
        return [
            [['tipo', 'leida', 'titulo'], 'safe'],
        ];
    }

    public function search(array $params, int $usuarioId): ActiveDataProvider
    {
        $query = Notificacion::find()
            ->where(['usuario_id' => $usuarioId])
            ->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $this->loadSanitized($params);
        if (!$this->validate()) {
            return $dataProvider;
        }

        if ($this->tipo !== null && $this->tipo !== '') {
            $query->andWhere(['tipo' => $this->tipo]);
        }

        if ($this->leida !== null && $this->leida !== '') {
            $query->andWhere(['leida' => (int) $this->leida]);
        }

        if ($this->titulo !== null && $this->titulo !== '') {
            $query->andWhere(['like', 'titulo', $this->titulo]);
        }

        return $dataProvider;
    }
}
