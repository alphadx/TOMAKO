<?php
declare(strict_types=1);

/** @var app\models\OrdenServicio $model */

echo $this->render('@app/views/orden/_tab_checklist', [
    'model' => $model,
    'gestionarRoute' => ['gestionar-checklist', 'id' => $model->id],
    'toggleRoute' => 'actualizar-checklist-item',
]);
