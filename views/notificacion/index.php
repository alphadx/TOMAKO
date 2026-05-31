<?php
/** @var yii\web\View $this */
/** @var app\models\search\NotificacionSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use app\models\Notificacion;

$this->title = 'Mis Notificaciones';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="notificacion-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0"><?= Html::encode($this->title) ?></h1>
        <button class="btn btn-outline-secondary btn-sm" id="btn-marcar-todas">Marcar todas como leidas</button>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'options' => ['class' => 'row g-2 mb-3']]); ?>
    <div class="col-md-3">
        <?= $form->field($searchModel, 'tipo')->dropDownList([
            '' => 'Todos',
            Notificacion::TIPO_STOCK_BAJO => 'Stock bajo',
            Notificacion::TIPO_ORDEN_LISTA => 'Orden lista',
            Notificacion::TIPO_CITA_CONFIRMADA => 'Cita confirmada',
            Notificacion::TIPO_SISTEMA => 'Sistema',
        ], ['class' => 'form-select'])->label('Tipo') ?>
    </div>
    <div class="col-md-2">
        <?= $form->field($searchModel, 'leida')->dropDownList([
            '' => 'Todas',
            '0' => 'No leidas',
            '1' => 'Leidas',
        ], ['class' => 'form-select'])->label('Estado') ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($searchModel, 'titulo')->textInput(['placeholder' => 'Buscar por titulo']) ?>
    </div>
    <div class="col-md-2 align-self-end">
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover align-middle'],
        'columns' => [
            [
                'label' => 'Tipo',
                'format' => 'raw',
                'value' => static function (Notificacion $n): string {
                    $map = Notificacion::getTipoBadgeClass();
                    $badge = $map[$n->tipo] ?? 'secondary';
                    return Html::tag('span', Html::encode($n->tipo), ['class' => 'badge bg-' . $badge]);
                },
            ],
            [
                'attribute' => 'titulo',
                'format' => 'raw',
                'value' => static function (Notificacion $n): string {
                    $title = Html::encode($n->titulo);
                    $cls = (int) $n->leida === 1 ? 'text-muted' : 'fw-semibold';

                    if (!empty($n->url)) {
                        return Html::a($title, $n->url, ['class' => $cls]);
                    }

                    return Html::tag('span', $title, ['class' => $cls]);
                },
            ],
            [
                'attribute' => 'mensaje',
                'contentOptions' => ['style' => 'max-width:420px; white-space:normal;'],
            ],
            [
                'label' => 'Recibida',
                'value' => static fn(Notificacion $n): string => $n->created_at ? Yii::$app->formatter->asRelativeTime($n->created_at) : '—',
            ],
            [
                'label' => 'Accion',
                'format' => 'raw',
                'value' => static function (Notificacion $n): string {
                    if ((int) $n->leida === 1) {
                        return '<span class="badge bg-light text-dark">Leida</span>';
                    }

                    return Html::button('Marcar leida', [
                        'class' => 'btn btn-sm btn-outline-primary notif-leer',
                        'data-id' => $n->id,
                    ]);
                },
            ],
        ],
    ]) ?>
</div>
<?php
$urlMarcar = Url::to(['marcar-leida']);
$urlMarcarTodas = Url::to(['marcar-todas-leidas']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;
$this->registerJs(<<<JS
(function () {
    document.querySelectorAll('.notif-leer').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.dataset.id;
            fetch('{$urlMarcar}?id=' + id, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: '{$csrfParam}={$csrfToken}'
            }).then(function () { window.location.reload(); });
        });
    });

    var markAll = document.getElementById('btn-marcar-todas');
    if (markAll) {
        markAll.addEventListener('click', function () {
            fetch('{$urlMarcarTodas}', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: '{$csrfParam}={$csrfToken}'
            }).then(function () { window.location.reload(); });
        });
    }
}());
JS);
