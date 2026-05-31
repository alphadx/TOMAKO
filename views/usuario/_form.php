<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var array $roles */
/** @var bool $isUpdate */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$isUpdate = $isUpdate ?? !$model->isNewRecord;
?>

<?php $form = ActiveForm::begin(['id' => 'usuario-form']); ?>

<div class="row g-3">
    <div class="col-md-6">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 100, 'placeholder' => 'Nombre']) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'apellido')->textInput(['maxlength' => 100, 'placeholder' => 'Apellido']) ?>
    </div>

    <div class="col-md-6">
        <?= $form->field($model, 'username')->textInput(['maxlength' => 60, 'placeholder' => 'nombre.usuario']) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'email')->input('email', ['maxlength' => 150, 'placeholder' => 'correo@tomako.cl']) ?>
    </div>

    <div class="col-md-6">
        <?= $form->field($model, 'rol_id')->dropDownList($roles, ['prompt' => 'Seleccione un rol...']) ?>
    </div>
    <div class="col-md-6">
        <?= $form->field($model, 'activo')->dropDownList(['1' => 'Activo', '0' => 'Inactivo']) ?>
    </div>

    <div class="col-md-6">
        <?php
        $pwdOptions = ['maxlength' => 255, 'placeholder' => $isUpdate ? 'Dejar vacío para no cambiar' : 'Contraseña'];
        if (!$isUpdate) $pwdOptions['required'] = true;
        echo $form->field($model, 'password_hash')
            ->passwordInput($pwdOptions)
            ->label($isUpdate ? 'Nueva Contraseña (opcional)' : 'Contraseña');
        ?>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $isUpdate ? 'Actualizar' : 'Crear Usuario' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>
