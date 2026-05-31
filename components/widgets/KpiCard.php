<?php

declare(strict_types=1);

namespace app\components\widgets;

use yii\base\Widget;
use yii\bootstrap5\Html;

/**
 * Widget KpiCard: tarjeta de indicador clave de rendimiento para el dashboard.
 *
 * Uso:
 * ```php
 * echo KpiCard::widget([
 *     'titulo'  => 'Citas hoy',
 *     'valor'   => 12,
 *     'icono'   => '📅',
 *     'tipo'    => 'primary',
 *     'url'     => ['/cita/index'],
 * ]);
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class KpiCard extends Widget
{
    /** Título descriptivo del KPI. */
    public string $titulo = '';

    /** Valor numérico o string a mostrar en grande. */
    public int|string $valor = 0;

    /** Emoji o carácter de icono representativo. */
    public string $icono = '📊';

    /**
     * Tipo visual: primary | success | warning | danger | info.
     */
    public string $tipo = 'primary';

    /** URL de enlace (opcional). Si se proporciona, el card es clickeable. */
    public ?array $url = null;

    /** Subtítulo o descripción adicional (opcional). */
    public ?string $subtitulo = null;

    /**
     * Mapa tipo → clases CSS del icono background.
     *
     * @var array<string, string>
     */
    private array $iconoBg = [
        'primary' => 'background:hsl(213,90%,90%)',
        'success' => 'background:hsl(145,60%,88%)',
        'warning' => 'background:hsl(38,92%,88%)',
        'danger'  => 'background:hsl(354,70%,88%)',
        'info'    => 'background:hsl(200,70%,88%)',
    ];

    /** @inheritdoc */
    public function run(): string
    {
        $claseCard = 'ts-kpi-card' . match($this->tipo) {
            'success' => ' ts-kpi-success',
            'warning' => ' ts-kpi-warning',
            'danger'  => ' ts-kpi-danger',
            'info'    => ' ts-kpi-info',
            default   => '',
        };

        $bgIcono = $this->iconoBg[$this->tipo] ?? $this->iconoBg['primary'];

        $inner  = '<div class="ts-kpi-icon" style="' . $bgIcono . '">' . $this->icono . '</div>';
        $inner .= '<div>';
        $inner .= '<div class="ts-kpi-value">' . Html::encode((string) $this->valor) . '</div>';
        $inner .= '<div class="ts-kpi-label">' . Html::encode($this->titulo) . '</div>';
        if ($this->subtitulo !== null) {
            $inner .= '<div style="font-size:.75rem;color:var(--ts-muted);margin-top:.2rem">' .
                          Html::encode($this->subtitulo) .
                      '</div>';
        }
        $inner .= '</div>';

        if ($this->url !== null) {
            return Html::a('<div class="' . $claseCard . '">' . $inner . '</div>',
                $this->url,
                ['style' => 'text-decoration:none;color:inherit;display:block']
            );
        }

        return '<div class="' . $claseCard . '">' . $inner . '</div>';
    }
}
