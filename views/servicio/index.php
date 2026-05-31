<?php
/** @var yii\web\View $this */
/** @var app\models\search\ServicioSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Categoria;

$this->title = 'Servicios';
$this->params['breadcrumbs'][] = $this->title;

$hayFiltros =
    ($searchModel->codigo !== null && $searchModel->codigo !== '') ||
    ($searchModel->nombre !== null && $searchModel->nombre !== '') ||
    $searchModel->categoria_id !== null ||
    $searchModel->status !== null;

$formatDuracion = static function (?int $duracion): string {
    if (empty($duracion)) {
        return '—';
    }

    if ($duracion < 60) {
        return $duracion . ' minutos';
    }

    $horas = intdiv($duracion, 60);
    $min   = $duracion % 60;

    if ($min === 0) {
        return $horas . ' horas';
    }

    return $horas . ' horas ' . $min . ' minutos';
};
?>

<div class="servicio-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-tools me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-download me-1"></i>Exportar CSV', ['export'], ['class' => 'btn btn-outline-primary']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Servicio', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'codigo')->textInput(['placeholder' => 'Código...'])->label('Código') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre...'])->label('Nombre') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'categoria_id')->dropDownList(
                        ['' => 'Todas las categorías'] + Categoria::getCategoriasList()
                    )->label('Categoría') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'status')->dropDownList([
                        '' => 'Todos', '1' => 'Activo', '0' => 'Inactivo',
                    ])->label('Estado') ?>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100 ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?php if ($hayFiltros): ?>
        <div class="alert alert-info py-2 px-3 d-flex justify-content-between align-items-center" role="alert">
            <span><i class="bi bi-funnel-fill me-1"></i>Filtro activo</span>
            <?= Html::a('Limpiar filtros', ['index'], ['class' => 'btn btn-sm btn-outline-primary']) ?>
        </div>
    <?php endif; ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'emptyText' => 'Sin resultados',
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'label' => 'Código',
                'value' => fn($model) => $model->codigo,
            ],
            'nombre',
            [
                'label' => 'Categoría',
                'value' => fn($model) => $model->categoria ? $model->categoria->nombre : '—',
            ],
            [
                'label'  => 'Precio Base',
                'format' => 'raw',
                'value'  => fn($model) => '$' . number_format((float)$model->precio_base, 2, '.', ','),
            ],
            [
                'label' => 'Duración',
                'value' => fn($model) => $formatDuracion($model->duracion_estimada),
            ],
            [
                'label'   => 'Estado',
                'format'  => 'raw',
                'content' => fn($model) => $model->status
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-danger">Inactivo</span>',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {deactivate} {activate}',
                'buttons'  => [
                    'view' => fn($url, $model) => Html::a(
                        '<i class="bi bi-eye"></i><span>Ver</span>',
                        ['view', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']
                    ),
                    'update' => fn($url, $model) => Html::a(
                        '<i class="bi bi-pencil"></i><span>Editar</span>',
                        ['update', 'id' => $model->id],
                        ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']
                    ),
                    'deactivate' => fn($url, $model) => $model->status
                        ? Html::a(
                            '<i class="bi bi-x-circle"></i><span>Desactivar</span>',
                            ['deactivate', 'id' => $model->id],
                            [
                                'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                'title'        => 'Desactivar',
                                'data-method'  => 'post',
                                'data-confirm' => '¿Desactivar este servicio?',
                            ]
                        )
                        : '',
                    'activate' => fn($url, $model) => !$model->status
                        ? Html::a(
                            '<i class="bi bi-check-circle"></i><span>Activar</span>',
                            ['activate', 'id' => $model->id],
                            [
                                'class'        => 'btn btn-sm btn-outline-success ts-action-btn',
                                'title'        => 'Activar',
                                'data-method'  => 'post',
                                'data-confirm' => '¿Activar este servicio?',
                            ]
                        )
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>
