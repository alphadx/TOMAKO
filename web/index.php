<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__, 1));
$dotenv->safeLoad();

if (PHP_SAPI !== 'cli') {
    $firstHeaderValue = static function (string $headerName): ?string {
        if (empty($_SERVER[$headerName])) {
            return null;
        }
        $parts = explode(',', (string) $_SERVER[$headerName]);
        $value = trim((string) $parts[0]);
        return $value !== '' ? $value : null;
    };

    $publicHost = $firstHeaderValue('HTTP_X_FORWARDED_HOST')
        ?? $firstHeaderValue('HTTP_X_ORIGINAL_HOST');

    if ($publicHost === null) {
        $forwarded = $firstHeaderValue('HTTP_FORWARDED');
        if ($forwarded !== null && preg_match('/(?:^|;)\s*host=([^;]+)/i', $forwarded, $m) === 1) {
            $publicHost = trim($m[1], "\"' ");
        }
    }

    if ($publicHost === null && isset($_SERVER['HTTP_HOST']) && str_contains((string) $_SERVER['HTTP_HOST'], 'localhost')) {
        $referer = $firstHeaderValue('HTTP_REFERER');
        if ($referer !== null) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $refererPort = parse_url($referer, PHP_URL_PORT);
            if (is_string($refererHost) && $refererHost !== '') {
                $publicHost = $refererHost . ($refererPort ? ':' . $refererPort : '');
            }
        }
    }

    if ($publicHost !== null) {
        $_SERVER['HTTP_HOST'] = $publicHost;
        $_SERVER['SERVER_NAME'] = preg_replace('/:\\d+$/', '', $publicHost) ?: $publicHost;
    }

    $forwardedProto = strtolower((string) ($firstHeaderValue('HTTP_X_FORWARDED_PROTO')
        ?? $firstHeaderValue('HTTP_X_FORWARDED_SCHEME')
        ?? $firstHeaderValue('HTTP_X_SCHEME')));

    if ($forwardedProto === 'https') {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['REQUEST_SCHEME'] = 'https';
    }

    $forwardedPort = $firstHeaderValue('HTTP_X_FORWARDED_PORT');
    if ($forwardedPort !== null) {
        $_SERVER['SERVER_PORT'] = $forwardedPort;
    } elseif ($forwardedProto === 'https') {
        $_SERVER['SERVER_PORT'] = '443';
    }
}

$debug = getenv('APP_DEBUG');
defined('YII_DEBUG') or define('YII_DEBUG', $debug === 'true' || $debug === '1' || $debug === true);
defined('YII_ENV') or define('YII_ENV', getenv('APP_ENV') ?: 'prod');

require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();
