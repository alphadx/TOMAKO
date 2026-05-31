<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;
use yii\bootstrap5\Html;

/**
 * Widget StatusBadge: renderiza un badge semántico según el estado de un registro.
 *
 * Uso:
 * ```php
 * echo StatusBadge::widget(['estado' => 'activo']);
 * echo StatusBadge::widget(['estado' => $model->estado, 'etiqueta' => 'En Proceso']);
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class StatusBadge extends Widget
{
    /**
     * Clave de estado (activo, inactivo, pendiente, en_proceso, completado, cancelado, pagado).
     */
    public string $estado = '';

    /**
     * Etiqueta personalizada. Si no se especifica, se usa el estado formateado.
     */
    public ?string $etiqueta = null;

    /**
     * Mapa de estado → clase CSS del badge.
     *
     * @var array<string, string>
     */
    public array $clases = [
        'activo'     => 'ts-badge-activo',
        'inactivo'   => 'ts-badge-inactivo',
        '1'          => 'ts-badge-activo',
        '0'          => 'ts-badge-inactivo',
        'pendiente'  => 'ts-badge-pendiente',
        'en_proceso' => 'ts-badge-en_proceso',
        'completado' => 'ts-badge-completado',
        'cancelado'  => 'ts-badge-cancelado',
        'pagado'     => 'ts-badge-pagado',
        'admin'      => 'ts-badge-admin',
        'administrador' => 'ts-badge-admin',
        'operador'   => 'ts-badge-operador',
        'mecanico'   => 'ts-badge-mecanico',
    ];

    /** @inheritdoc */
    public function run(): string
    {
        $clave  = strtolower((string) $this->estado);
        $clase  = $this->clases[$clave] ?? 'ts-badge-inactivo';
        $texto  = $this->etiqueta ?? ucfirst(str_replace('_', ' ', $clave));

        return '<span class="ts-badge ' . Html::encode($clase) . '">' .
                   Html::encode($texto) .
               '</span>';
    }
}
