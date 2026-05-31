<?php
/** @var yii\web\View $this */
/** @var app\models\Rol $model */
/** @var array $permisos  Permisos agrupados por módulo */
/** @var int[] $asignados IDs de permisos ya asignados */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$asignados = $asignados ?? [];
?>

<?php $form = ActiveForm::begin(['id' => 'rol-form']); ?>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <?= $form->field($model, 'nombre')->textInput(['maxlength' => 60, 'placeholder' => 'Nombre del rol']) ?>
    </div>
    <div class="col-md-3">
        <?= $form->field($model, 'activo')->dropDownList(['1' => 'Activo', '0' => 'Inactivo']) ?>
    </div>
    <div class="col-12">
        <?= $form->field($model, 'descripcion')->textarea(['rows' => 2, 'placeholder' => 'Descripción breve del rol...']) ?>
    </div>
</div>

<hr>
<h5 class="mb-3"><i class="bi bi-key me-2"></i>Permisos</h5>

<?php if (!empty($permisos)): ?>
    <div class="row g-3">
        <?php foreach ($permisos as $modulo => $listaPermisos): ?>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header py-2 d-flex justify-content-between align-items-center">
                    <strong class="text-capitalize"><?= Html::encode($modulo) ?></strong>
                    <button type="button" class="btn btn-xs btn-link p-0 text-muted small toggle-modulo"
                            data-modulo="<?= Html::encode($modulo) ?>">
                        seleccionar todo
                    </button>
                </div>
                <div class="card-body py-2">
                    <?php foreach ($listaPermisos as $permiso): ?>
                    <div class="form-check">
                        <input class="form-check-input permiso-check-<?= Html::encode($modulo) ?>"
                               type="checkbox"
                               name="permisos[]"
                               value="<?= $permiso->id ?>"
                               id="perm_<?= $permiso->id ?>"
                               <?= in_array($permiso->id, $asignados, true) ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="perm_<?= $permiso->id ?>">
                            <?= Html::encode($permiso->nombre) ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p class="text-muted">No hay permisos definidos en el sistema aún.</p>
<?php endif; ?>

<div class="mt-4 d-flex gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-floppy me-1"></i><?= $model->isNewRecord ? 'Crear Rol' : 'Actualizar Rol' ?>
    </button>
    <?= Html::a('<i class="bi bi-x-lg me-1"></i>Cancelar', ['index'], ['class' => 'btn btn-secondary']) ?>
</div>

<?php ActiveForm::end(); ?>

<script>
document.querySelectorAll('.toggle-modulo').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var modulo = this.dataset.modulo;
        var checks = document.querySelectorAll('.permiso-check-' + modulo);
        var allChecked = Array.from(checks).every(function(c) { return c.checked; });
        checks.forEach(function(c) { c.checked = !allChecked; });
        this.textContent = allChecked ? 'seleccionar todo' : 'deseleccionar todo';
    });
});
</script>
