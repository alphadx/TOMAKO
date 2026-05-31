<?php
declare(strict_types=1);

namespace app\models;

use yii\base\Model;

/**
 * Formulario para la calculadora de precios de servicios.
 * 
 * @property int|null $servicio_id
 * @property int $cantidad
 * @property float $margen_ganancia
 * @property float $descuento
 * @property bool $incluir_repuestos
 * @property float $costo_repuestos
 * @property float $porcentaje_repuestos
 * @property string|null $cliente_nombre
 * @property string|null $cliente_rut
 * @property string|null $vehiculo_patente
 */
class CalculadoraForm extends Model
{
    public ?int $servicio_id = null;
    public int $cantidad = 1;
    public float $margen_ganancia = 20;
    public float $descuento = 0;
    public bool $incluir_repuestos = false;
    public float $costo_repuestos = 0;
    public float $porcentaje_repuestos = 15;
    
    // Campos opcionales para impresión
    public ?string $cliente_nombre = null;
    public ?string $cliente_rut = null;
    public ?string $vehiculo_patente = null;
    
    // Selección múltiple de servicios
    public array $servicios_seleccionados = [];

    public function rules(): array
    {
        return [
            [['servicio_id'], 'required', 'on' => 'calcular'],
            [['servicio_id'], 'integer'],
            [['cantidad'], 'integer', 'min' => 1, 'max' => 999],
            [['margen_ganancia', 'descuento'], 'number', 'min' => 0, 'max' => 100],
            [['incluir_repuestos'], 'boolean'],
            [['costo_repuestos'], 'number', 'min' => 0],
            [['porcentaje_repuestos'], 'number', 'min' => 0, 'max' => 100],
            [['cliente_nombre', 'cliente_rut', 'vehiculo_patente'], 'string', 'max' => 255],
            [['servicios_seleccionados'], 'each', 'rule' => ['integer']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'servicio_id' => 'Servicio',
            'cantidad' => 'Cantidad',
            'margen_ganancia' => 'Margen Ganancia (%)',
            'descuento' => 'Descuento (%)',
            'incluir_repuestos' => 'Incluir Repuestos',
            'costo_repuestos' => 'Costo Repuestos ($)',
            'porcentaje_repuestos' => 'Margen Repuestos (%)',
            'cliente_nombre' => 'Nombre del Cliente',
            'cliente_rut' => 'RUT',
            'vehiculo_patente' => 'Patente del Vehículo',
            'servicios_seleccionados' => 'Servicios Seleccionados',
        ];
    }
}
