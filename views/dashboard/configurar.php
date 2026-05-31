<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, array{is_visible: bool, sort_order: int, is_collapsed: bool}> $preferencias */
/** @var array<string, array{id: string, title: string, category: string}> $widgetsDisponibles */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

$this->title = 'Configurar Dashboard';
$this->params['breadcrumbs'][] = $this->title;
$this->registerCsrfMetaTags();
?>

<div class="dashboard-configurar">
    <div class="row mb-4">
        <div class="col-12">
            <h1><?= Html::encode($this->title) ?></h1>
            <p class="text-muted">Personaliza tu dashboard seleccionando qué widgets mostrar y en qué orden.</p>
        </div>
    </div>

    <?php $form = ActiveForm::begin([
        'action' => ['dashboard/guardar-preferencias'],
        'method' => 'post',
        'options' => ['id' => 'form-configurar-dashboard'],
    ]); ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Widgets Disponibles</h5>
                    <?= Html::submitButton('<i class="bi bi-save"></i> Guardar Configuración', [
                        'class' => 'btn btn-sm btn-primary',
                        'id' => 'btn-guardar-preferencias',
                    ]) ?>
                </div>
                <div class="card-body">
                    <div id="widgets-container">
                        <?php
                        // Agrupar widgets por categoría
                        $widgetsPorCategoria = [];
                        foreach ($widgetsDisponibles as $widget) {
                            $categoria = $widget['category'];
                            if (!isset($widgetsPorCategoria[$categoria])) {
                                $widgetsPorCategoria[$categoria] = [];
                            }
                            $widgetsPorCategoria[$categoria][] = $widget;
                        }

                        $orden = 0;
                        foreach ($widgetsPorCategoria as $categoria => $widgets):
                        ?>
                            <div class="mb-4">
                                <h6 class="text-muted text-uppercase small"><?= Html::encode($categoria) ?></h6>
                                <div class="list-group">
                                    <?php foreach ($widgets as $widget): 
                                        $pref = $preferencias[$widget['id']] ?? null;
                                        $isVisible = $pref !== null ? $pref['is_visible'] : false;
                                        $isCollapsed = $pref !== null ? $pref['is_collapsed'] : false;
                                    ?>
                                        <div class="list-group-item list-group-item-action widget-item" 
                                             data-widget-id="<?= Html::encode($widget['id']) ?>"
                                             data-visible="<?= $isVisible ? '1' : '0' ?>"
                                             data-collapsed="<?= $isCollapsed ? '1' : '0' ?>">
                                            <div class="d-flex w-100 justify-content-between align-items-center">
                                                <div class="form-check">
                                                    <?= Html::checkbox('widgets[]', $isVisible, [
                                                        'value' => $widget['id'],
                                                        'uncheck' => null,
                                                        'class' => 'form-check-input',
                                                    ]) ?>
                                                    <label class="form-check-label">
                                                        <?= Html::encode($widget['title']) ?>
                                                        <small class="text-muted d-block"><?= Html::encode($widget['id']) ?></small>
                                                    </label>
                                                </div>
                                                <div>
                                                    <span class="badge <?= $isVisible ? 'bg-success' : 'bg-secondary' ?> widget-status">
                                                        <?= $isVisible ? 'Visible' : 'Oculto' ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Ayuda</h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Marca/desmarca los widgets para mostrar/ocultarlos.</li>
                        <li>Haz clic en "Guardar Configuración" para aplicar los cambios.</li>
                        <li>Los cambios se aplicarán inmediatamente después de guardar.</li>
                        <li>Puedes volver a esta página cuando quieras reconfigurar tu dashboard.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <?php ActiveForm::end(); ?>
</div>

<style>
.widget-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.widget-item:hover {
    background-color: #f8f9fa;
}

.widget-item.dragging {
    opacity: 0.5;
    background-color: #e9ecef;
}
</style>


