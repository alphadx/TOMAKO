<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Plantillas de Notificacion';
$this->params['breadcrumbs'][] = ['label' => 'Notificaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-plantillas">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('Test Email', ['test-email'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Email Log', ['email-log'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('Nueva Plantilla', ['crear-plantilla'], ['class' => 'btn btn-primary btn-sm']) ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover'],
        'columns' => [
            'codigo',
            'canal',
            'asunto',
            [
                'label' => 'Activa',
                'value' => static fn($m): string => (int) $m->activo === 1 ? 'Si' : 'No',
            ],
            [
                'class' => 'yii\\grid\\ActionColumn',
                'template' => '{update}',
                'buttons' => [
                    'update' => static fn($url, $m): string => Html::a('Editar', ['editar-plantilla', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary']),
                ],
            ],
        ],
    ]) ?>
</div>
