<?php
/** @var yii\web\View $this */
/** @var app\models\User $model */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Mi Perfil';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="usuario-profile">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0"><i class="bi bi-person-circle me-2"></i><?= Html::encode($this->title) ?></h1>
        <?= Html::a('<i class="bi bi-key me-1"></i>Cambiar Contraseña', ['change-password'], ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><strong>Datos Personales</strong></div>
                <div class="card-body">
                    <?php $form = ActiveForm::begin(['id' => 'profile-form']); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <?= $form->field($model, 'nombre')->textInput(['maxlength' => 100]) ?>
                        </div>
                        <div class="col-md-6">
                            <?= $form->field($model, 'apellido')->textInput(['maxlength' => 100]) ?>
                        </div>
                        <div class="col-12">
                            <?= $form->field($model, 'email')->input('email', ['maxlength' => 150]) ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-floppy me-1"></i>Guardar Cambios
                        </button>
                    </div>

                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><strong>Información de Cuenta</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Usuario:</dt>
                        <dd class="col-7"><?= Html::encode($model->username) ?></dd>
                        <dt class="col-5">Rol:</dt>
                        <dd class="col-7">
                            <span class="badge bg-secondary"><?= Html::encode($model->rol->nombre ?? '—') ?></span>
                        </dd>
                        <dt class="col-5">Estado:</dt>
                        <dd class="col-7">
                            <?= $model->activo
                                ? '<span class="badge bg-success">Activo</span>'
                                : '<span class="badge bg-danger">Inactivo</span>' ?>
                        </dd>
                        <dt class="col-5">Último acceso:</dt>
                        <dd class="col-7"><?= $model->ultimo_login ? date('d/m/Y H:i', $model->ultimo_login) : '—' ?></dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
