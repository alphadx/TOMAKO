<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'tallersmart',
    'name' => 'TOMAKO',
    'language' => 'es-CL',
    'sourceLanguage' => 'es-CL',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'on beforeRequest' => static function (): void {
        $default = Yii::$app->params['defaultLanguage'] ?? 'es-CL';
        $lang = Yii::$app->session->get('appLanguage', $default);
        Yii::$app->language = $lang;
    },
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: 'f31WAHoKwfOFTaLg1RNEH3hFJU7Wm1Nb',
            // Permite usar host/protocolo/puerto publicos cuando la app esta detras de proxy.
            'trustedHosts' => array_map('trim', explode(',', getenv('TRUSTED_PROXY_HOSTS') ?: '*')),
            'secureHeaders' => [
                'X-Forwarded-For',
                'X-Forwarded-Host',
                'X-Forwarded-Proto',
                'X-Forwarded-Port',
            ],
        ],
        'session' => [
            'cookieParams' => [
                'httpOnly' => true,
                'secure' => getenv('SESSION_COOKIE_SECURE') === 'true',
                'sameSite' => getenv('SESSION_COOKIE_SAMESITE') ?: 'Strict',
            ],
            'timeout' => (int)(getenv('SESSION_TIMEOUT') ?: 1800),
        ],
        'i18n' => [
            'translations' => [
                'app*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'es-CL',
                    'fileMap' => [
                        'app' => 'app.php',
                        'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
            'cachePath' => '@app/runtime/cache',
            'keyPrefix' => 'ts_',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'authTimeout' => (int)(getenv('AUTH_TIMEOUT') ?: 1800),
            'identityCookie' => [
                'name' => '_identity-tallersmart',
                'httpOnly' => true,
                'secure' => getenv('SESSION_COOKIE_SECURE') === 'true',
                'sameSite' => getenv('SESSION_COOKIE_SAMESITE') ?: 'Strict',
            ],
            // Usar formato array para que SIEMPRE se resuelva a /site/login,
            // independientemente del controlador actual.
            'loginUrl' => ['/site/login'],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            'useFileTransport' => (getenv('APP_ENV') !== 'production'),
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => '@app/runtime/logs/app.log',
                    'maxFileSize' => 1024,
                    'maxLogFiles' => 10,
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['info'],
                    'categories' => ['app.*'],
                    'logFile' => '@app/runtime/logs/info.log',
                    'maxFileSize' => 1024,
                    'maxLogFiles' => 5,
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'usuario' => 'usuario/index',
                'usuario/profile' => 'usuario/profile',
                'usuario/change-password' => 'usuario/change-password',
                'usuario/create' => 'usuario/create',
                'usuario/<id:\d+>' => 'usuario/view',
                'usuario/<id:\d+>/update' => 'usuario/update',
                'usuario/<id:\d+>/deactivate' => 'usuario/deactivate',
                'rol' => 'rol/index',
                'rol/create' => 'rol/create',
                'rol/<id:\d+>' => 'rol/view',
                'rol/<id:\d+>/update' => 'rol/update',
                'categoria' => 'categoria/index',
                'categoria/create' => 'categoria/create',
                'categoria/<id:\d+>' => 'categoria/view',
                'categoria/<id:\d+>/update' => 'categoria/update',
                'categoria/<id:\d+>/deactivate' => 'categoria/deactivate',
                'categoria/<id:\d+>/delete' => 'categoria/delete',
                'servicio' => 'servicio/index',
                'servicio/create' => 'servicio/create',
                'servicio/export' => 'servicio/export',
                'servicio/<id:\d+>' => 'servicio/view',
                'servicio/<id:\d+>/update' => 'servicio/update',
                'servicio/<id:\d+>/deactivate' => 'servicio/deactivate',
                'servicio/<id:\d+>/activate' => 'servicio/activate',
                'cliente' => 'cliente/index',
                'cliente/create' => 'cliente/create',
                'cliente/create-ajax' => 'cliente/create-ajax',
                'cliente/export' => 'cliente/export',
                'cliente/<id:\d+>' => 'cliente/view',
                'cliente/<id:\d+>/update' => 'cliente/update',
                'cliente/<id:\d+>/deactivate' => 'cliente/deactivate',
                'vehiculo' => 'vehiculo/index',
                'vehiculo/create' => 'vehiculo/create',
                'vehiculo/por-cliente/<clienteId:\d+>' => 'vehiculo/por-cliente',
                'vehiculo/<id:\d+>' => 'vehiculo/view',
                'vehiculo/<id:\d+>/update' => 'vehiculo/update',
                'vehiculo/<id:\d+>/deactivate' => 'vehiculo/deactivate',
                'inventario' => 'inventario/index',
                'inventario/create' => 'inventario/create',
                'inventario/<id:\d+>' => 'inventario/view',
                'inventario/<id:\d+>/update' => 'inventario/update',
                'inventario/<id:\d+>/deactivate' => 'inventario/deactivate',
                'inventario/<id:\d+>/entrada' => 'inventario/entrada',
                'inventario/<id:\d+>/ajuste' => 'inventario/ajuste',
                'tecnico' => 'tecnico/index',
                'tecnico/create' => 'tecnico/create',
                'tecnico/<id:\d+>' => 'tecnico/view',
                'tecnico/<id:\d+>/update' => 'tecnico/update',
                'tecnico/<id:\d+>/deactivate' => 'tecnico/deactivate',
                'tecnico/<id:\d+>/certificacion' => 'tecnico/add-certificacion',
                'especialidad' => 'especialidad/index',
                'especialidad/create' => 'especialidad/create',
                'especialidad/<id:\d+>/update' => 'especialidad/update',
                'especialidad/<id:\d+>/deactivate' => 'especialidad/deactivate',
                'cita' => 'cita/index',
                'cita/create' => 'cita/create',
                'cita/calendario' => 'cita/calendario',
                'cita/eventos/<mes:\d{4}-\d{2}>' => 'cita/eventos',
                'cita/estadisticas/<mes:\d{4}-\d{2}>' => 'cita/estadisticas',
                'cita/vehiculos-por-cliente' => 'cita/vehiculos-por-cliente',
                'cita/<id:\d+>' => 'cita/view',
                'cita/<id:\d+>/update' => 'cita/update',
                'cita/<id:\d+>/reprogramar' => 'cita/reprogramar',
                'cita/<id:\d+>/confirmar' => 'cita/confirmar',
                'cita/<id:\d+>/cancelar' => 'cita/cancelar',
                'cita/<id:\d+>/no-show' => 'cita/no-show',
                'cita/<id:\d+>/iniciar-servicio' => 'cita/iniciar-servicio',
                'orden' => 'orden/index',
                'orden/create' => 'orden/create',
                'orden/desde-cita/<citaId:\d+>' => 'orden/desde-cita',
                'orden/<id:\d+>' => 'orden/view',
                'orden/<id:\d+>/update' => 'orden/update',
                'orden/<id:\d+>/cambiar-estado' => 'orden/cambiar-estado',
                'orden/<id:\d+>/agregar-nota' => 'orden/agregar-nota',
                'orden/<id:\d+>/asignar-tecnico' => 'orden/asignar-tecnico',
                'orden/<id:\d+>/agregar-repuesto' => 'orden/agregar-repuesto',
                'orden/<id:\d+>/eliminar-repuesto' => 'orden/eliminar-repuesto',
                'orden/actualizar-cantidad-repuesto/<id:\d+>' => 'orden/actualizar-cantidad-repuesto',
                'pago' => 'pago/index',
                'pago/create' => 'pago/create',
                'pago/<id:\d+>' => 'pago/view',
                'pago/<id:\d+>/confirmar' => 'pago/confirmar',
                'pago/<id:\d+>/anular' => 'pago/anular',
                'pago/historial/<ordenId:\d+>' => 'pago/historial',
                'pago/reporte-ingresos' => 'pago/reporte-ingresos',
                'pago/reporte-por-metodo' => 'pago/reporte-por-metodo',
                'pago/exportar-csv/<tipo:[a-z\-]+>' => 'pago/exportar-csv',
                'pago/cierre-caja' => 'pago/cierre-caja',
                'pago/abrir-caja' => 'pago/abrir-caja',
                'pago/cerrar-caja/<id:\d+>' => 'pago/cerrar-caja',
                'notificaciones' => 'notificacion/index',
                'notificaciones/preferencias' => 'notificacion/preferencias',
                'notificaciones/contador' => 'notificacion/contador-json',
                'notificaciones/marcar-leida' => 'notificacion/marcar-leida',
                'notificaciones/marcar-todas' => 'notificacion/marcar-todas-leidas',
                'admin/notificaciones/plantillas' => 'notificacion/plantillas',
                'admin/notificaciones/plantillas/nueva' => 'notificacion/crear-plantilla',
                'admin/notificaciones/plantillas/<id:\d+>' => 'notificacion/editar-plantilla',
                'admin/notificaciones/email-log' => 'notificacion/email-log',
                'admin/notificaciones/test-email' => 'notificacion/test-email',
                'dashboard' => 'dashboard/index',
                'dashboard/configurar' => 'dashboard/configurar',
                'dashboard/guardar-preferencias' => 'dashboard/guardar-preferencias',
                'dashboard/refresh-kpi/<kpi:[a-z_]+>' => 'dashboard/refresh-kpi',
                'dashboard/refresh-kpi' => 'dashboard/refresh-kpi',
                'manual' => 'manual/index',
                'manual-usuario' => 'manual/index',
                'calculadora' => 'calculadora/index',
                'calculadora/imprimir' => 'calculadora/imprimir',
                'cotizacion/validacion' => 'calculadora/validacion',
                '' => 'dashboard/index',
                'login' => 'site/login',
                'logout' => 'site/logout',
                'idioma' => 'site/change-language',
                'password/request' => 'site/request-password-reset',
                'password/reset/<token:[A-Za-z0-9_\-]+>' => 'site/reset-password',
                'admin' => 'admin/database',
                'admin/database' => 'admin/database',
                'admin/database-init' => 'admin/database-init',
            ],
        ],
        'db' => $db,
        'inventarioService' => [
            'class' => 'app\\components\\services\\InventarioService::class',
        ],
    ],
    'container' => [
        'definitions' => [
            \yii\grid\GridView::class => [
                'pager' => [
                    'class' => \yii\bootstrap5\LinkPager::class,
                ],
            ],
        ],
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
