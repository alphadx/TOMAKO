<?php

declare(strict_types=1);

namespace app\components\widgets;

use Yii;
use yii\base\Widget;
use yii\bootstrap5\Html;

/**
 * Widget que renderiza los flash messages de Yii como toasts de TOMAKO.
 *
 * Flash keys soportados: success, error, warning, info, danger.
 *
 * Uso en layout:
 * ```php
 * echo FlashMessages::widget();
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class FlashMessages extends Widget
{
    /**
     * Mapa de tipo flash → clase CSS del toast.
     *
     * @var array<string, string>
     */
    public array $tipoClase = [
        'success' => 'ts-toast-success',
        'error'   => 'ts-toast-error',
        'danger'  => 'ts-toast-error',
        'warning' => 'ts-toast-warning',
        'info'    => 'ts-toast-info',
    ];

    /**
     * Mapa de tipo flash → icono emoji.
     *
     * @var array<string, string>
     */
    public array $tipoIcono = [
        'success' => '✅',
        'error'   => '❌',
        'danger'  => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
    ];

    /** @inheritdoc */
    public function run(): string
    {
        $flashes = Yii::$app->session->getAllFlashes();

        if (empty($flashes)) {
            return '';
        }

        $html = '';

        foreach ($flashes as $tipo => $mensajes) {
            if (!is_array($mensajes)) {
                $mensajes = [$mensajes];
            }
            foreach ($mensajes as $mensaje) {
                $clase = $this->tipoClase[$tipo] ?? 'ts-toast-info';
                $icono = $this->tipoIcono[$tipo]  ?? 'ℹ️';
                $html .= $this->renderToast($icono, (string) $mensaje, $clase);
            }
        }

        return $html;
    }

    /**
     * Renderiza un único toast.
     *
     * @param string $icono   Emoji o carácter de icono.
     * @param string $mensaje Mensaje a mostrar.
     * @param string $clase   Clase CSS del toast.
     */
    protected function renderToast(string $icono, string $mensaje, string $clase): string
    {
        return '<div class="ts-toast ' . Html::encode($clase) . '" data-auto-close>' .
                   '<span style="font-size:1.1rem">' . $icono . '</span>' .
                   '<span style="flex:1;font-size:.875rem">' . Html::encode($mensaje) . '</span>' .
                   '<button class="ts-toast-close" aria-label="Cerrar">✕</button>' .
               '</div>';
    }
}
