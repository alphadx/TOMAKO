<?php

declare(strict_types=1);

namespace app\components\widgets;

use app\models\Cita;
use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;

class CitasHoyWidget extends Widget
{
    /** @var Cita[] */
    public array $citas = [];

    public function run(): string
    {
        $html = '<div class="ts-panel">';
        $html .= '<div class="ts-panel-header">';
        $html .= '<h2>Citas de hoy</h2>';
        $html .= Html::a('Ver agenda', Url::to(['/cita/index']), ['class' => 'btn btn-sm btn-outline-secondary']);
        $html .= '</div>';

        if ($this->citas === []) {
            $html .= '<p class="text-muted mb-0">No hay citas programadas para hoy.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<ul class="ts-list-clean">';
        foreach ($this->citas as $cita) {
            $cliente = (string) ($cita->cliente->nombre ?? 'Sin cliente');
            $patente = (string) ($cita->vehiculo->patente ?? 'Sin patente');
            $hora = substr((string) $cita->hora_inicio, 0, 5);

            $html .= '<li>';
            $html .= '<div>';
            $html .= '<strong>' . Html::encode($cliente) . '</strong>';
            $html .= '<div class="small text-muted">' . Html::encode($patente) . '</div>';
            $html .= '</div>';
            $html .= '<div class="text-end">';
            $html .= '<div class="fw-semibold">' . Html::encode($hora) . '</div>';
            $html .= Html::a('Detalle', Url::to(['/cita/view', 'id' => $cita->id]), ['class' => 'small']);
            $html .= '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
