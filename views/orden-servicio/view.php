<?php
declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap5\Tabs;
use app\models\OrdenServicio;

/** @var yii\web\View $this */
/** @var OrdenServicio $model */

$this->title = 'Orden ' . $model->codigo;
$this->params['breadcrumbs'][] = ['label' => 'Órdenes', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="orden-servicio-view">
    <!-- Header with Code and State -->
    <div class="mb-3">
        <h1><?= Html::encode($this->title) ?></h1>
        <p>
            <?= $model->getEstadoBadgeClass() ?>
            <?= $model->getPrioridadBadge() ?>
        </p>
    </div>

    <!-- Quick Actions -->
    <div class="mb-3">
        <?php if ($model->puedeTransicionar('en_progreso')): ?>
            <?= Html::a('Iniciar', '#', ['class' => 'btn btn-success', 'data-action' => 'cambiar-estado', 'data-estado' => 'en_progreso']) ?>
        <?php endif ?>
        
        <?php if ($model->puedeTransicionar('listo_para_entrega')): ?>
            <?= Html::a('Marcar Listo', '#', ['class' => 'btn btn-info', 'data-action' => 'cambiar-estado', 'data-estado' => 'listo_para_entrega']) ?>
        <?php endif ?>
        
        <?php if ($model->puedeTransicionar('entregada') && false /*placeholder for Hito 10*/): ?>
            <?= Html::a('Entregar', ['ver-cerrar', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?php endif ?>
        
        <?php if ($model->puedeTransicionar('cancelada')): ?>
            <?= Html::a('Cancelar', '#', ['class' => 'btn btn-danger', 'data-action' => 'cancelar']) ?>
        <?php endif ?>

        <?= Html::a('Editar', '#', ['class' => 'btn btn-warning']) ?>
        <?= Html::a('Atrás', ['index'], ['class' => 'btn btn-secondary']) ?>
    </div>

    <!-- Tabs -->
    <?= Tabs::widget([
        'items' => [
            [
                'label' => 'Datos Generales',
                'content' => $this->render('_tab_datos', ['model' => $model]),
                'active' => true,
            ],
            [
                'label' => 'Servicios (' . count($model->detalles) . ')',
                'content' => $this->render('_tab_servicios', ['model' => $model]),
            ],
            [
                'label' => 'Técnicos (' . count($model->asignaciones) . ')',
                'content' => $this->render('_tab_tecnicos', ['model' => $model]),
            ],
            [
                'label' => 'Notas (' . count($model->notas) . ')',
                'content' => $this->render('_tab_notas', ['model' => $model]),
            ],
            [
                'label' => 'Timeline',
                'content' => $this->render('_tab_timeline', ['model' => $model]),
            ],
            [
                'label' => 'Checklist',
                'content' => $this->render('_tab_checklist', ['model' => $model]),
                'options' => ['id' => 'tab-checklist'],
            ],
            [
                'label' => 'Repuestos (' . count($model->repuestos) . ')',
                'content' => $this->render('_repuestos', ['model' => $model]),
            ],
            [
                'label' => 'Archivos (' . count($model->archivos) . ')',
                'content' => $this->renderAjax('_archivos', ['model' => $model]),
                'options' => ['id' => 'tab-archivos'],
            ],
            [
                'label' => 'Seguimientos',
                'content' => $this->renderAjax('_seguimientos', [
                    'orden' => $model,
                    'seguimientosProvider' => new \yii\data\ActiveDataProvider([
                        'query' => \app\models\Seguimiento::find()
                            ->where(['orden_servicio_id' => $model->id])
                            ->orderBy(['fecha_programada' => SORT_DESC]),
                        'pagination' => ['pageSize' => 5],
                    ]),
                ]),
                'options' => ['id' => 'tab-seguimientos'],
            ],
        ],
    ]) ?>
</div>

<?php
$this->registerJs(<<<'JS'
// Handle actions
document.querySelectorAll('[data-action]').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const action = this.dataset.action;
        const estado = this.dataset.estado;
        // TODO: Implement action handling
    });
});
JS
) ?>
