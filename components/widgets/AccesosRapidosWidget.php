<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;
use yii\bootstrap5\Html;
use yii\helpers\Url;

class AccesosRapidosWidget extends Widget
{
    /** @var array<int,array{icon:string,title:string,description:string,url:array}> */
    public array $accesos = [];

    public function run(): string
    {
        $html = '<div class="ts-panel">';
        $html .= '<div class="ts-panel-header"><h2>Accesos rapidos</h2></div>';

        if ($this->accesos === []) {
            $html .= '<p class="text-muted mb-0">No hay accesos disponibles para su rol.</p>';
            $html .= '</div>';
            return $html;
        }

        $html .= '<div class="row g-2">';
        foreach ($this->accesos as $acceso) {
            $html .= '<div class="col-12">';
            $html .= '<a class="ts-quick-link" href="' . Html::encode(Url::to($acceso['url'])) . '">';
            $html .= '<div class="ts-quick-link-icon">' . Html::encode($acceso['icon']) . '</div>';
            $html .= '<div>';
            $html .= '<h3>' . Html::encode($acceso['title']) . '</h3>';
            $html .= '<p>' . Html::encode($acceso['description']) . '</p>';
            $html .= '</div>';
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}
