<?php

declare(strict_types=1);

namespace app\components\widgets;

use app\models\InventoryItem;
use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;

class AlertasStockWidget extends Widget
{
    /** @var InventoryItem[] */
    public array $alertas = [];

    public function run(): string
    {
        $html = '<div class="ts-panel">';
        $html .= '<div class="ts-panel-header">';
        $html .= '<h2>Stock critico</h2>';
        $html .= Html::a('Inventario', Url::to(['/inventario/index']), ['class' => 'btn btn-sm btn-outline-secondary']);
        $html .= '</div>';

        if ($this->alertas === []) {
            $html .= '<p class="text-muted mb-0">No hay alertas de stock en este momento.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<ul class="ts-list-clean">';
        foreach ($this->alertas as $item) {
            $html .= '<li>';
            $html .= '<div>';
            $html .= '<strong>' . Html::encode($item->nombre) . '</strong>';
            $html .= '<div class="small text-muted">SKU: ' . Html::encode($item->sku) . '</div>';
            $html .= '</div>';
            $html .= '<div class="text-end">';
            $html .= '<span class="badge bg-danger">' . Html::encode((string) $item->cantidad) . '</span>';
            $html .= '<div class="small text-muted">min. ' . Html::encode((string) $item->stock_minimo) . '</div>';
            $html .= '</div>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}
