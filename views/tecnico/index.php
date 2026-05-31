<?php
/** @var yii\web\View $this */
/** @var app\models\search\TecnicoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\ActiveForm;
use app\models\Especialidad;

$this->title = 'Técnicos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="tecnico-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person-gear me-2"></i><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-tags me-1"></i>Especialidades', ['/especialidad/index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Técnico', ['create'], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <!-- Filtros -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'nombre')->textInput(['placeholder' => 'Nombre o apellido...'])->label('Nombre') ?>
                </div>
                <div class="col-md-4">
                    <?= $form->field($searchModel, 'especialidad_id')->dropDownList(
                        Especialidad::getEspecialidadesList(),
                        ['prompt' => 'Todas las especialidades']
                    )->label('Especialidad') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'status')->dropDownList(['' => 'Todos', '1' => 'Activo', '0' => 'Inactivo'])->label('Estado') ?>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1 ts-filter-actions">
                    <button type="submit" class="btn btn-primary ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-hover table-striped align-middle'],
        'columns'      => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                'label'  => 'Nombre Completo',
                'format' => 'raw',
                'value'  => fn($m) => Html::a(Html::encode($m->getFullName()), ['view', 'id' => $m->id]),
            ],
            [
                'label'  => 'Especialidad',
                'format' => 'raw',
                'value'  => fn($m) => $m->especialidad
                    ? '<span class="badge bg-info text-dark">' . Html::encode($m->especialidad->nombre) . '</span>'
                    : '—',
            ],
            'email:email',
            'telefono',
            [
                'label' => 'Costo/Hora',
'value' => fn($m) => $m->costo_hora ? '$ ' . number_format((float)$m->costo_hora, 0, ',', '.') : '—',
            ],
            [
                'label'  => 'Estado',
                'format' => 'raw',
                'value'  => fn($m) => $m->status
                    ? '<span class="badge bg-success">Activo</span>'
                    : '<span class="badge bg-secondary">Inactivo</span>',
            ],
            [
                'class'    => 'yii\grid\ActionColumn',
                'template' => '{view} {update} {deactivate}',
                'buttons'  => [
                    'view' => fn($url, $m) => Html::a('<i class="bi bi-eye"></i><span>Ver</span>', ['view', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']),
                    'update' => fn($url, $m) => Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $m->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']),
                    'deactivate' => fn($url, $m) => $m->status
                        ? Html::a('<i class="bi bi-person-x"></i><span>Desactivar</span>', ['deactivate', 'id' => $m->id], [
                            'class' => 'btn btn-sm btn-outline-danger ts-action-btn', 'data-method' => 'post',
                            'data-confirm' => '¿Desactivar este técnico?',
                        ])
                        : '',
                ],
            ],
        ],
    ]); ?>
</div>
