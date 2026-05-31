<?php
/** @var yii\web\View $this */
/** @var app\models\search\VehiculoSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use app\models\Vehiculo;

$this->title = 'Vehículos';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="vehiculo-index">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-car-front me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-plus-lg me-1"></i>Nuevo Vehículo', ['create'], ['class' => 'btn btn-success']) ?>
    </div>

    <!-- Filtros de búsqueda -->
    <?php $form = ActiveForm::begin(['method' => 'get', 'id' => 'search-form']); ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'patente')->textInput(['placeholder' => 'Patente...'])->label('Patente') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'marca')->textInput(['placeholder' => 'Marca...'])->label('Marca') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'modelo')->textInput(['placeholder' => 'Modelo...'])->label('Modelo') ?>
                </div>
                <div class="col-md-2">
                    <?= $form->field($searchModel, 'vin')->textInput(['placeholder' => 'VIN...'])->label('VIN') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($searchModel, 'propietario')->textInput(['placeholder' => 'Nombre propietario...'])->label('Propietario') ?>
                </div>
                <div class="col-md-1">
                    <?= $form->field($searchModel, 'status')->dropDownList(['' => 'Todos', '1' => 'Activo', '0' => 'Inactivo'])->label('Estado') ?>
                </div>
                <div class="col-md-1 d-flex align-items-end gap-1 ts-filter-actions">
                    <button type="submit" class="btn btn-primary ts-action-btn"><i class="bi bi-search"></i><span>Buscar</span></button>
                    <?= Html::a('<i class="bi bi-x-lg"></i><span>Limpiar</span>', ['index'], ['class' => 'btn btn-outline-secondary ts-action-btn', 'title' => 'Limpiar']) ?>
                </div>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

    <!-- Tarjetas de vehículos -->
    <?php if ($dataProvider->totalCount === 0): ?>
        <div class="alert alert-info">No se encontraron vehículos.</div>
    <?php else: ?>
        <div class="row g-3 mb-3">
            <?php foreach ($dataProvider->models as $model): /** @var Vehiculo $model */ ?>
            <div class="col-md-4 col-lg-3">
                <div class="card h-100 shadow-sm border-<?= $model->status ? 'success' : 'secondary' ?>">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="badge bg-dark fs-6 font-monospace"><?= Html::encode($model->patente) ?></span>
                            <span class="badge bg-<?= $model->status ? 'success' : 'secondary' ?>">
                                <?= $model->status ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                        <?php if ($model->foto_path): ?>
                            <img src="<?= Yii::$app->request->baseUrl . '/' . $model->foto_path ?>"
                                 alt="Foto" class="img-fluid rounded mb-2" style="max-height:100px;object-fit:cover;width:100%">
                        <?php else: ?>
                            <div class="bg-light rounded d-flex align-items-center justify-content-center mb-2" style="height:80px">
                                <i class="bi bi-car-front text-muted fs-1"></i>
                            </div>
                        <?php endif; ?>
                        <h6 class="card-title mb-1"><?= Html::encode($model->marca . ' ' . $model->modelo) ?></h6>
                        <small class="text-muted">Año <?= Html::encode((string) $model->anio) ?></small><br>
                        <?php if ($model->cliente): ?>
                            <small>
                                <i class="bi bi-person me-1"></i>
                                <?= Html::a(Html::encode($model->cliente->nombre), ['/cliente/view', 'id' => $model->cliente_id], ['class' => 'text-decoration-none']) ?>
                            </small>
                        <?php endif; ?>
                        <?php if ($model->ultimo_km): ?>
                            <br><small class="text-muted"><i class="bi bi-speedometer2 me-1"></i><?= number_format($model->ultimo_km) ?> km</small>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-transparent d-flex gap-1">
                        <?= Html::a('<i class="bi bi-eye"></i><span>Ver</span>', ['view', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-primary ts-action-btn', 'title' => 'Ver']) ?>
                        <?= Html::a('<i class="bi bi-pencil"></i><span>Editar</span>', ['update', 'id' => $model->id], ['class' => 'btn btn-sm btn-outline-secondary ts-action-btn', 'title' => 'Editar']) ?>
                        <?php if ($model->status): ?>
                            <?= Html::a('<i class="bi bi-x-circle"></i><span>Desactivar</span>', ['deactivate', 'id' => $model->id], [
                                'class'        => 'btn btn-sm btn-outline-danger ts-action-btn',
                                'title'        => 'Desactivar',
                                'data-method'  => 'post',
                                'data-confirm' => '¿Desactivar este vehículo?',
                            ]) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-center">
            <?= \yii\bootstrap5\LinkPager::widget(['pagination' => $dataProvider->pagination]) ?>
        </div>
    <?php endif; ?>
</div>
