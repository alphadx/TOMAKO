<?php
/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;

$this->title = 'Log de Emails';
$this->params['breadcrumbs'][] = ['label' => 'Notificaciones', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-email-log">
    <h1 class="h4 mb-3"><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-hover align-middle'],
        'columns' => [
            [
                'label' => 'Fecha',
                'value' => static fn($m): string => $m->enviado_at ? date('d/m/Y H:i', (int) $m->enviado_at) : '—',
            ],
            'destinatario:email',
            'plantilla',
            'asunto',
            [
                'label' => 'Estado',
                'format' => 'raw',
                'value' => static fn($m): string => (int) $m->exito === 1
                    ? '<span class="badge bg-success">Enviado</span>'
                    : '<span class="badge bg-danger">Fallido</span>',
            ],
            [
                'attribute' => 'error',
                'contentOptions' => ['style' => 'max-width:300px; white-space:normal;'],
            ],
        ],
    ]) ?>
</div>
