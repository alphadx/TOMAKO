<?php

declare(strict_types=1);

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\AuditLog;

/**
 * SearchModel para AuditLog - Filtros avanzados de auditoría
 *
 * Proporciona interfaz fluida para construir filtros complejos sobre la tabla audit_log.
 *
 * Uso:
 * ```php
 * $search = new AuditLogSearch();
 * $search->usuario_id = Yii::$app->user->id;
 * $search->entidad = 'Cliente';
 * $search->fecha_desde = '2026-05-01';
 * $search->fecha_hasta = '2026-05-10';
 *
 * $dataProvider = $search->search(Yii::$app->request->queryParams);
 * ```
 *
 * @property int|null    $id
 * @property int|null    $usuario_id
 * @property string|null $accion
 * @property string|null $modulo
 * @property string|null $entidad
 * @property int|null    $registro_id
 * @property string|null $ip_address
 * @property string|null $fecha_desde
 * @property string|null $fecha_hasta
 * @property string|null $buscar_valor
 */
class AuditLogSearch extends AuditLog
{
    /**
     * Filtros de rango de fechas
     */
    public ?string $fecha_desde = null;
    public ?string $fecha_hasta = null;

    /**
     * Búsqueda de texto en datos_previos y datos_nuevos
     */
    public ?string $buscar_valor = null;

    public function rules(): array
    {
        return [
            [['id', 'usuario_id', 'registro_id'], 'integer'],
            [['accion', 'modulo', 'entidad', 'ip_address', 'fecha_desde', 'fecha_hasta', 'buscar_valor'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'fecha_desde' => 'Desde',
            'fecha_hasta' => 'Hasta',
            'buscar_valor' => 'Buscar en datos',
        ]);
    }

    /**
     * Construir un DataProvider basado en los parámetros de búsqueda.
     *
     * @param array $params Parámetros de la búsqueda
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
    {
        $query = AuditLog::find();

        // Cargar atributos desde los parámetros
        if (!($this->load($params) && $this->validate())) {
            return new ActiveDataProvider([
                'query' => $query->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 50],
            ]);
        }

        // Aplicar filtros
        $query->andFilterWhere(['=', 'id', $this->id]);
        $query->andFilterWhere(['=', 'usuario_id', $this->usuario_id]);
        $query->andFilterWhere(['=', 'accion', $this->accion]);
        $query->andFilterWhere(['like', 'modulo', $this->modulo]);
        $query->andFilterWhere(['=', 'entidad', $this->entidad]);
        $query->andFilterWhere(['=', 'registro_id', $this->registro_id]);
        $query->andFilterWhere(['like', 'ip_address', $this->ip_address]);

        // Filtro de rango de fechas
        if ($this->fecha_desde) {
            $query->andWhere(['>=', 'created_at', $this->fecha_desde . ' 00:00:00']);
        }
        if ($this->fecha_hasta) {
            $query->andWhere(['<=', 'created_at', $this->fecha_hasta . ' 23:59:59']);
        }

        // Búsqueda de texto en JSON
        if ($this->buscar_valor) {
            $searchValue = '%' . $this->buscar_valor . '%';
            $query->andWhere([
                'or',
                ['like', 'datos_previos', $searchValue],
                ['like', 'datos_nuevos', $searchValue],
            ]);
        }

        // Ordenar por fecha descendente
        $query->orderBy(['created_at' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 50,
            ],
            'sort' => [
                'defaultOrder' => [
                    'created_at' => SORT_DESC,
                ],
                'attributes' => [
                    'id',
                    'usuario_id',
                    'accion',
                    'modulo',
                    'entidad',
                    'created_at',
                ],
            ],
        ]);
    }

    /**
     * Método fluida para filtrar por usuario
     */
    public function byUsuario(?int $usuarioId): self
    {
        $this->usuario_id = $usuarioId;
        return $this;
    }

    /**
     * Método fluida para filtrar por acción
     */
    public function byAccion(string $accion): self
    {
        $this->accion = $accion;
        return $this;
    }

    /**
     * Método fluida para filtrar por entidad
     */
    public function byEntidad(string $entidad): self
    {
        $this->entidad = $entidad;
        return $this;
    }

    /**
     * Método fluida para filtrar por módulo
     */
    public function byModulo(string $modulo): self
    {
        $this->modulo = $modulo;
        return $this;
    }

    /**
     * Método fluida para filtrar por rango de fechas
     */
    public function byDateRange(string $desde, string $hasta): self
    {
        $this->fecha_desde = $desde;
        $this->fecha_hasta = $hasta;
        return $this;
    }

    /**
     * Método fluida para búsqueda de texto
     */
    public function byValue(string $valor): self
    {
        $this->buscar_valor = $valor;
        return $this;
    }

    /**
     * Obtener el query builder para operaciones avanzadas
     */
    public function getQuery(): \yii\db\ActiveQuery
    {
        $query = AuditLog::find();

        if ($this->usuario_id !== null) {
            $query->andWhere(['usuario_id' => $this->usuario_id]);
        }
        if ($this->accion) {
            $query->andWhere(['accion' => $this->accion]);
        }
        if ($this->entidad) {
            $query->andWhere(['entidad' => $this->entidad]);
        }
        if ($this->registro_id !== null) {
            $query->andWhere(['registro_id' => $this->registro_id]);
        }
        if ($this->fecha_desde) {
            $query->andWhere(['>=', 'created_at', $this->fecha_desde . ' 00:00:00']);
        }
        if ($this->fecha_hasta) {
            $query->andWhere(['<=', 'created_at', $this->fecha_hasta . ' 23:59:59']);
        }
        if ($this->buscar_valor) {
            $searchValue = '%' . $this->buscar_valor . '%';
            $query->andWhere([
                'or',
                ['like', 'datos_previos', $searchValue],
                ['like', 'datos_nuevos', $searchValue],
            ]);
        }

        return $query->orderBy(['created_at' => SORT_DESC]);
    }
}
