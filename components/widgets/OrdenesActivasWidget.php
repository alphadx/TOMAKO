<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;

class OrdenesActivasWidget extends Widget
{
    /** @var array<string,int> */
    public array $ordenes = [];

    public function run(): string
    {
        $labels = [
            'abierto' => 'Abiertas',
            'en_progreso' => 'En progreso',
            'esperando_repuestos' => 'Esperando repuestos',
            'listo_para_entrega' => 'Listas para entrega',
        ];

        $html = '<div class="ts-panel">';
        $html .= '<div class="ts-panel-header">';
        $html .= '<h2>Trabajos activos por estado</h2>';
        $html .= Html::a('Ver ordenes', Url::to(['/orden/index']), ['class' => 'btn btn-sm btn-outline-secondary']);
        $html .= '</div>';

        $html .= '<ul class="ts-list-clean">';
        foreach ($labels as $estado => $label) {
            $valor = (int) ($this->ordenes[$estado] ?? 0);
            $html .= '<li>';
            $html .= '<span>' . Html::encode($label) . '</span>';
            $html .= '<span class="badge bg-primary">' . Html::encode((string) $valor) . '</span>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
