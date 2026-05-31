<?php
declare(strict_types=1);
namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\User;

/**
 * UserSearch. Modelo de búsqueda para listado de usuarios.
 *
 * @property string|null $username
 * @property string|null $email
 * @property int|null $rol_id
 * @property int|null $activo
 */
class UserSearch extends Model
{
    use SearchParamsSanitizerTrait;

    public ?string $username = null;
    public ?string $email = null;
    public ?int $rol_id = null;
    public ?int $activo = null;

    public function rules(): array
    {
        return [
            [['username', 'email'], 'safe'],
            [['rol_id', 'activo'], 'integer'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username' => 'Usuario',
            'email'    => 'Correo',
            'rol_id'   => 'Rol',
            'activo'   => 'Estado',
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = User::find()->with('rol')->where(['deleted_at' => null]);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 20],
            'sort'       => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        if (!$this->loadSanitized($params) || !$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['rol_id' => $this->rol_id])
              ->andFilterWhere(['activo' => $this->activo]);

        return $dataProvider;
    }
}
