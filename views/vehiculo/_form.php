<?php
/** @var yii\web\View $this */
/** @var app\models\Vehiculo $model */
/** @var array<int,string> $clientes */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Vehiculo;
?>

<?php $form = ActiveForm::begin([
    'id'      => 'vehiculo-form',
    'options' => ['enctype' => 'multipart/form-data'],
]); ?>

<div class="row g-3">
    <div class="col-md-4">
        <?= $form->field($model, 'patente')->textInput([
            'maxlength' => 10, 
            'placeholder' => 'ABCD-12 o AB-1234',
            'class' => 'form-control patente-input'
        ])->hint('Formato: ABCD-12 (nuevo) o AB-1234 (antiguo). Se normaliza automáticamente.') ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'marca')->textInput([
            'maxlength' => 60, 
            'placeholder' => 'Buscar o escribir nueva marca...',
            'id' => 'vehiculo-marca',
            'class' => 'form-control select2-marca'
        ]) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'modelo')->textInput([
            'maxlength' => 60, 
            'placeholder' => 'Buscar o escribir nuevo modelo...',
            'id' => 'vehiculo-modelo',
            'class' => 'form-control select2-modelo'
        ]) ?>
    </div>

    <div class="col-md-3">
        <?= $form->field($model, 'anio')->input('number', ['min' => 1900, 'max' => date('Y') + 1, 'placeholder' => date('Y')]) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'ultimo_km')->input('number', ['min' => 0, 'placeholder' => '0']) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'vin')->textInput(['maxlength' => 17, 'placeholder' => 'VIN (opcional, 17 caracteres)'])
            ->hint('Sin I, O ni Q. Opcional.') ?>
    </div>

    <div class="col-md-8">
        <?= $form->field($model, 'cliente_id')->dropDownList(
            $clientes,
            ['prompt' => '— Seleccione propietario —']
        ) ?>
    </div>
    <div class="col-md-4">
        <?= $form->field($model, 'status')->dropDownList(Vehiculo::getEstadosList()) ?>
    </div>

    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Foto del Vehículo</label>
            <input type="file" name="Vehiculo[foto]" accept="image/*" class="form-control">
            <?php if (!$model->isNewRecord && $model->foto_path): ?>
                <div class="mt-2">
                    <img src="<?= Yii::$app->request->baseUrl . '/' . $model->foto_path ?>"
                         alt="Foto actual" style="max-height:100px" class="rounded border">
                    <small class="text-muted d-block">Foto actual (subir nueva para reemplazar)</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Registrar Vehículo' : 'Actualizar' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

<?php
// El CSS y JS de Select2 ya están registrados por AppAsset
// Solo necesitamos inicializar los selectores en este formulario
$js = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    if (window.VehiculoMarcaModeloSelect2) {
        window.VehiculoMarcaModeloSelect2.initForm(
            'vehiculo-form',
            'vehiculo-marca',
            'vehiculo-modelo'
        );
    }
});
JS;
$this->registerJs($js, \yii\web\View::POS_END);
?>
