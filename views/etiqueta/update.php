<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var app\models\Etiqueta $model */

$this->title = 'Editar Etiqueta';
$this->params['breadcrumbs'][] = ['label' => 'Etiquetas', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->nombre, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Editar';
?>

<div class="etiqueta-update">
    <div class="row mb-3">
        <div class="col-12">
            <h1><?= Html::encode($this->title) ?></h1>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'nombre')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'color')->dropDownList(
                        \app\models\Etiqueta::getColoresList(),
                        ['prompt' => 'Seleccione un color...']
                    ) ?>

                    <?= $form->field($model, 'descripcion')->textarea(['rows' => 4]) ?>

                    <?= $form->field($model, 'status')->checkbox() ?>

                    <div class="form-group mt-3">
                        <?= Html::submitButton('Guardar', ['class' => 'btn btn-success']) ?>
                        <?= Html::a('Cancelar', ['view', 'id' => $model->id], ['class' => 'btn btn-secondary']) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h5>Colores Disponibles</h5>
                    <ul class="list-unstyled">
                        <?php foreach (\app\models\Etiqueta::getColoresList() as $valor => $label): ?>
                            <li>
                                <span class="badge bg-<?= $valor ?>"><?= $label ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
