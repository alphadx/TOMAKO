<?php

declare(strict_types=1);

namespace app\components\behaviors;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

/**
 * Behavior de borrado lógico (soft delete): en lugar de eliminar físicamente
 * un registro, marca el campo `activo = 0` y registra `deleted_at`.
 *
 * Requisitos del modelo:
 * - Columna `activo` (tinyint 1, default 1).
 * - Columna `deleted_at` (int unsigned, nullable).
 *
 * Uso en el modelo:
 * ```php
 * public function behaviors(): array
 * {
 *     return [
 *         'softDelete' => [
 *             'class' => SoftDeleteBehavior::class,
 *         ],
 *     ];
 * }
 * ```
 *
 * Para listar solo registros activos, agregar en defaultScope o en find():
 * ```php
 * public static function find(): ActiveQuery
 * {
 *     return parent::find()->andWhere(['activo' => 1]);
 * }
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class SoftDeleteBehavior extends Behavior
{
    /**
     * Campo que indica si el registro está activo.
     */
    public string $campoActivo = 'activo';

    /**
     * Campo de timestamp de eliminación lógica.
     */
    public string $campoDeletedAt = 'deleted_at';

    /**
     * Valor del campo activo cuando el registro está activo.
     */
    public int $valorActivo = 1;

    /**
     * Valor del campo activo cuando el registro está eliminado.
     */
    public int $valorEliminado = 0;

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        return [
            BaseActiveRecord::EVENT_BEFORE_DELETE => 'antesDeEliminar',
        ];
    }

    /**
     * Intercepta el evento DELETE, ejecuta borrado lógico y cancela el DELETE físico.
     *
     * @param \yii\base\ModelEvent $event
     */
    public function antesDeEliminar(\yii\base\ModelEvent $event): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        // Verificar que el modelo tiene los campos necesarios
        if (!$owner->hasAttribute($this->campoActivo)) {
            return; // Campo no existe, dejar que ocurra el DELETE físico
        }

        // Marcar como eliminado
        $atributos = [$this->campoActivo => $this->valorEliminado];

        if ($owner->hasAttribute($this->campoDeletedAt)) {
            $atributos[$this->campoDeletedAt] = time();
        }

        $owner->setAttributes($atributos, false);
        $owner->save(false);

        // Cancelar el DELETE físico
        $event->isValid = false;
    }

    /**
     * Restaura un registro eliminado lógicamente.
     *
     * @return bool  True si la restauración fue exitosa.
     */
    public function restaurar(): bool
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;

        if (!$owner->hasAttribute($this->campoActivo)) {
            return false;
        }

        $atributos = [$this->campoActivo => $this->valorActivo];

        if ($owner->hasAttribute($this->campoDeletedAt)) {
            $atributos[$this->campoDeletedAt] = null;
        }

        $owner->setAttributes($atributos, false);
        return $owner->save(false);
    }

    /**
     * Verifica si el registro está eliminado lógicamente.
     */
    public function estaEliminado(): bool
    {
        return (int) $this->owner->getAttribute($this->campoActivo) === $this->valorEliminado;
    }
}
