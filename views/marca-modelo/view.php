<?php
/** @var yii\web\View $this */
/** @var app\models\Marca $marca */
/** @var app\models\Modelo[] $modelos */

use yii\helpers\Html;
use yii\grid\GridView;
use yii\data\ArrayDataProvider;

$this->title = $marca->nombre . ' - Modelos';
$this->params['breadcrumbs'][] = ['label' => 'Marcas y Modelos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="marca-modelo-view">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-car-front me-2"></i><?= Html::encode($marca->nombre) ?></h1>
        <div>
            <?= Html::a('<i class="bi bi-pencil me-1"></i>Editar Marca', ['update-marca', 'id' => $marca->id], ['class' => 'btn btn-warning']) ?>
            <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Modelo', ['create-modelo', 'marcaId' => $marca->id], ['class' => 'btn btn-success']) ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Modelos de <?= Html::encode($marca->nombre) ?></h5>
        </div>
        <div class="card-body p-0">
            <?php
            $modelosProvider = new ArrayDataProvider([
                'allModels' => $modelos,
                'pagination' => false,
            ]);
            ?>
            <?= GridView::widget([
                'dataProvider' => $modelosProvider,
                'tableOptions' => ['class' => 'table table-hover table-striped mb-0'],
                'headerRowOptions' => ['class' => 'table-light'],
                'layout' => '{items}',
                'emptyText' => '<div class="alert alert-info m-3"><i class="bi bi-info-circle me-2"></i>No hay modelos registrados para esta marca.</div>',
                'emptyTextOptions' => ['class' => ''],
                'columns' => [
                    ['class' => 'yii\grid\SerialColumn'],
                    [
                        'label' => 'Nombre del Modelo',
                        'attribute' => 'nombre',
                        'value' => fn($model) => '<strong>' . Html::encode($model->nombre) . '</strong>',
                        'format' => 'raw',
                    ],
                    [
                        'class' => 'yii\grid\ActionColumn',
                        'template' => '{update} {delete}',
                        'buttons' => [
                            'update' => fn($url, $model) => Html::a(
                                '<i class="bi bi-pencil"></i>',
                                ['update-modelo', 'id' => $model->id],
                                ['class' => 'btn btn-sm btn-outline-warning', 'title' => 'Editar modelo']
                            ),
                            'delete' => fn($url, $model) => Html::a(
                                '<i class="bi bi-trash"></i>',
                                ['delete-modelo', 'id' => $model->id],
                                [
                                    'class' => 'btn btn-sm btn-outline-danger',
                                    'title' => 'Eliminar modelo',
                                    'data-confirm' => '¿Está seguro que desea eliminar este modelo?',
                                    'data-method' => 'post',
                                ]
                            ),
                        ],
                        'contentOptions' => ['style' => 'width: 150px;'],
                    ],
                ],
            ]); ?>
        </div>
        <div class="card-footer bg-transparent">
            <?= Html::a('<i class="bi bi-arrow-left me-1"></i>Volver al listado', ['index'], ['class' => 'btn btn-secondary']) ?>
        </div>
    </div>
</div>
