<?php
/** @var yii\web\View $this */
/** @var app\models\search\MarcaSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;

$this->title = 'Marcas de Vehículos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="marca-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-car-front me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nueva Marca', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-6">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre...'])->label('Nombre') ?>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'label' => 'Nombre',
                'value' => fn($model) => $model->nombre,
            ],
            [
                'label' => 'Modelos',
                'format' => 'raw',
                'value' => function ($model) {
                    $total = (int) $model->getModelos()->count();
                    return $total > 0
                        ? '<span class="badge bg-primary">' . $total . '</span>'
                        : '<span class="badge bg-secondary">0</span>';
                },
            ],
            [
                'label' => 'Acciones',
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::a('<i class="bi bi-eye"></i>', ['view', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-primary',
                            'title' => 'Ver',
                        ]) . ' ' .
                        Html::a('<i class="bi bi-pencil"></i>', ['update', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-warning',
                            'title' => 'Editar',
                        ]) . ' ' .
                        Html::a('<i class="bi bi-trash"></i>', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-sm btn-outline-danger',
                            'title' => 'Eliminar',
                            'data-confirm' => '¿Está seguro que desea eliminar esta marca? Esta acción no se puede deshacer.',
                            'data-method' => 'post',
                        ]);
                },
            ],
        ],
    ]); ?>
</div>
