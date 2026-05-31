<?php
/**
 * Vista para actualizar/completar un seguimiento
 * @var yii\web\View $this
 * @var app\models\Seguimiento $model
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = $model->isPendiente() ? 'Completar Seguimiento' : 'Editar Seguimiento';
$this->params['breadcrumbs'][] = ['label' => 'Agenda de Seguimientos', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => "Seguimiento #{$model->id}", 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="seguimiento-update">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-edit"></i> <?= Html::encode($this->title) ?></h1>
        <?= Html::a(
            '<i class="fas fa-arrow-left"></i> Cancelar',
            ['view', 'id' => $model->id],
            ['class' => 'btn btn-secondary']
        ) ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-form"></i> Formulario de Seguimiento</h5>
                </div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['options' => ['autocomplete' => 'off']]); ?>

                    <!-- Información de referencia -->
                    <div class="alert alert-info">
                        <strong>Orden:</strong> <?= $model->ordenServicio?->codigo ?? '-' ?><br>
                        <strong>Cliente:</strong> <?= $model->cliente?->nombre_completo ?? '-' ?><br>
                        <strong>Tipo:</strong> <?= $model->tipoLabel ?><br>
                        <strong>Programado:</strong> <?= $model->fecha_programada ? date('d/m/Y H:i', $model->fecha_programada) : '-' ?>
                    </div>

                    <?= $form->field($model, 'resultado')->textarea([
                        'rows' => 4,
                        'placeholder' => 'Describa el resultado del seguimiento...',
                        'required' => true,
                    ]) ?>

                    <div class="row">
                        <div class="col-md-6">
                            <?= $form->field($model, 'satisfaccion')->dropDownList([
                                1 => '1 ⭐ - Muy insatisfecho',
                                2 => '2 ⭐⭐ - Insatisfecho',
                                3 => '3 ⭐⭐⭐ - Neutral',
                                4 => '4 ⭐⭐⭐⭐ - Satisfecho',
                                5 => '5 ⭐⭐⭐⭐⭐ - Muy satisfecho',
                            ], [
                                'prompt' => 'Seleccione nivel de satisfacción...',
                            ]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'recomendariamos')->checkbox([
                                'label' => '¿El cliente nos recomendaría?',
                                'uncheck' => null,
                            ]) ?>
                        </div>
                    </div>

                    <?= $form->field($model, 'observaciones')->textarea([
                        'rows' => 3,
                        'placeholder' => 'Observaciones adicionales...',
                    ]) ?>

                    <div class="form-group">
                        <?= Html::submitButton(
                            '<i class="fas fa-save"></i> Guardar',
                            ['class' => 'btn btn-success']
                        ) ?>
                        <?= Html::a(
                            '<i class="fas fa-times"></i> Cancelar',
                            ['view', 'id' => $model->id],
                            ['class' => 'btn btn-secondary']
                        ) ?>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- Guía rápida -->
            <div class="card bg-light">
                <div class="card-header">
                    <h5><i class="fas fa-lightbulb"></i> Guía Rápida</h5>
                </div>
                <div class="card-body">
                    <h6>Niveles de Satisfacción:</h6>
                    <ul class="small">
                        <li>⭐ - Muy insatisfecho (requiere atención inmediata)</li>
                        <li>⭐⭐ - Insatisfecho (mejorable)</li>
                        <li>⭐⭐⭐ - Neutral (ni bueno ni malo)</li>
                        <li>⭐⭐⭐⭐ - Satisfecho (buen servicio)</li>
                        <li>⭐⭐⭐⭐⭐ - Muy satisfecho (excelente)</li>
                    </ul>
                    
                    <hr>
                    
                    <h6>Consejos para el seguimiento:</h6>
                    <ul class="small">
                        <li>Sé amable y profesional</li>
                        <li>Escucha activamente al cliente</li>
                        <li>Toma nota de todas las observaciones</li>
                        <li>Si hay problemas, ofrece soluciones</li>
                        <li>Agradece siempre su tiempo</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
