<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
        'css/tallersmart.css',
    ];
    public $js = [
        'js/notifications.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap5\BootstrapAsset',
        'yii\bootstrap5\BootstrapPluginAsset',
    ];
    
    /**
     * Registrar Select2 para los modales de alta rápida de vehículo
     */
    public function init()
    {
        parent::init();
        // Select2 CSS y JS para los selectores de marca/modelo
        \Yii::$app->view->registerCssFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        \Yii::$app->view->registerCssFile('https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css');
        \Yii::$app->view->registerJsFile('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [
            'position' => \yii\web\View::POS_END,
            'depends' => ['yii\web\YiiAsset']
        ]);
        \Yii::$app->view->registerJsFile('@web/js/vehiculo-marca-modelo-select2.js', [
            'position' => \yii\web\View::POS_END,
            'depends' => ['yii\web\YiiAsset', 'yii\bootstrap5\BootstrapPluginAsset']
        ]);
    }
}
