<?php

return [
    'class' => yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('YII_DB_HOST') ?: 'db',
        getenv('YII_DB_PORT') ?: '3306',
        getenv('YII_DB_NAME') ?: 'yii2db'
    ),
    'username' => getenv('YII_DB_USER') ?: 'yii2user',
    'password' => getenv('YII_DB_PASSWORD') ?: 'yii2pass',
    'charset' => 'utf8mb4',
    'attributes' => [
        \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ],
];
