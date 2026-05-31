<?php
declare(strict_types=1);

namespace app\models;

use yii\base\Model;

/**
 * ChecklistReportForm - Formulario para filtrar reporte de checklists
 */
class ChecklistReportForm extends Model
{
    public ?string $fechaDesde = null;
    public ?string $fechaHasta = null;

    public function rules(): array
    {
        return [
            [['fechaDesde', 'fechaHasta'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'fechaDesde' => 'Fecha Desde',
            'fechaHasta' => 'Fecha Hasta',
        ];
    }
}
