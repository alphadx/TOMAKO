<?php
declare(strict_types=1);

/** @var app\models\OrdenServicio $model */

echo $this->render('@app/views/orden/_tab_notas', [
    'model' => $model,
    'addNotaRoute' => ['/orden/agregar-nota', 'id' => $model->id],
    'showAuthor' => true,
    'multiline' => true,
]);
