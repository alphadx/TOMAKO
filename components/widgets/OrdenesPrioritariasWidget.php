<?php

declare(strict_types=1);

namespace app\components\widgets;

use app\models\OrdenServicio;
use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;

class OrdenesPrioritariasWidget extends Widget
{
    /** @var OrdenServicio[] */
    public array $ordenes = [];

    public function run(): string
    {
        $html = '<div class="ts-panel">';
        $html .= '<div class="ts-panel-header">';
        $html .= '<h2>Órdenes prioritarias</h2>';
        $html .= Html::a('Ver órdenes', Url::to(['/orden/index']), ['class' => 'btn btn-sm btn-outline-secondary']);
        $html .= '</div>';

        if ($this->ordenes === []) {
            $html .= '<p class="text-muted mb-0">No hay órdenes prioritarias pendientes.</p>';
            $html .= '</div>';
            return $html;
        }

        $estados = OrdenServicio::getEstadosList();

        $html .= '<ul class="ts-list-clean">';
        foreach ($this->ordenes as $orden) {
            $codigo = (string) ($orden->codigo ?? 'Sin código');
            $cliente = (string) ($orden->cliente->nombre ?? 'Sin cliente');
            $patente = (string) ($orden->vehiculo->patente ?? 'Sin patente');
            $prioridad = ucfirst((string) ($orden->prioridad ?? 'normal'));
            $estado = (string) ($estados[$orden->estado] ?? $orden->estado);

            $html .= '<li>';
            $html .= '<div>';
            $html .= '<strong>' . Html::encode($codigo) . '</strong>';
            $html .= '<div class="small text-muted">' . Html::encode($cliente . ' · ' . $patente) . '</div>';
            $html .= '</div>';
            $html .= '<div class="text-end">';
            $html .= '<span class="badge ' . Html::encode($orden->getPrioridadBadgeClass()) . '">' . Html::encode($prioridad) . '</span>';
            $html .= '<div class="small text-muted mt-1">' . Html::encode($estado) . '</div>';
            $html .= '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
