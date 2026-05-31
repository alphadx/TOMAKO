<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use app\models\InventoryItem;
use app\components\services\InventarioService;

/**
 * Controlador para reportes de inventario.
 */
class InventarioReportController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
        ];
    }

    /**
     * Reporte de valorización de inventario.
     */
    public function actionValorizacion(): string
    {
        $service = new InventarioService();
        
        // Obtener todos los items activos
        $items = InventoryItem::find()
            ->where(['status' => 1])
            ->with('categoria')
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        // Calcular valorización por categoría
        $valorPorCategoria = [];
        $valorTotal = 0;
        $totalItems = 0;
        $totalValorCosto = 0;

        foreach ($items as $item) {
            $valorUnitario = (float) $item->precio_unitario;
            $cantidad = (int) $item->cantidad;
            $valorTotalItem = $valorUnitario * $cantidad;
            $valorTotal += $valorTotalItem;
            $totalItems += $cantidad;
            
            $categoriaNombre = $item->categoria ? $item->categoria->nombre : 'Sin Categoría';
            if (!isset($valorPorCategoria[$categoriaNombre])) {
                $valorPorCategoria[$categoriaNombre] = [
                    'cantidad' => 0,
                    'valor' => 0,
                    'items' => 0,
                ];
            }
            $valorPorCategoria[$categoriaNombre]['cantidad'] += $cantidad;
            $valorPorCategoria[$categoriaNombre]['valor'] += $valorTotalItem;
            $valorPorCategoria[$categoriaNombre]['items']++;
        }

        // Ordenar por valor descendente
        arsort($valorPorCategoria);

        // KPIs adicionales
        $itemsSinStock = InventoryItem::find()
            ->where(['status' => 1, 'cantidad' => 0])
            ->count();
        
        $itemsBajoStock = InventoryItem::find()
            ->where(['status' => 1])
            ->andWhere(['<=', 'cantidad', new \yii\db\Expression('stock_minimo')])
            ->count();

        return $this->render('valorizacion', [
            'items' => $items,
            'valorPorCategoria' => $valorPorCategoria,
            'valorTotal' => $valorTotal,
            'totalItems' => $totalItems,
            'itemsSinStock' => $itemsSinStock,
            'itemsBajoStock' => $itemsBajoStock,
        ]);
    }

    /**
     * Exportar valorización a CSV.
     */
    public function actionExportValorizacionCsv(): Response
    {
        $items = InventoryItem::find()
            ->where(['status' => 1])
            ->with('categoria')
            ->orderBy(['nombre' => SORT_ASC])
            ->all();

        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \yii\web\ServerErrorHttpException('No se pudo preparar la exportación CSV.');
        }

        fputcsv($handle, [
            'SKU', 
            'Nombre', 
            'Categoría', 
            'Precio Unitario', 
            'Cantidad', 
            'Valor Total',
            'Stock Mínimo',
            'Estado Stock'
        ]);

        $valorTotal = 0;
        foreach ($items as $item) {
            $valorUnitario = (float) $item->precio_unitario;
            $cantidad = (int) $item->cantidad;
            $valorItem = $valorUnitario * $cantidad;
            $valorTotal += $valorItem;

            fputcsv($handle, [
                $item->sku,
                $item->nombre,
                $item->categoria ? $item->categoria->nombre : '',
                number_format($valorUnitario, 2, '.', ''),
                (string) $cantidad,
                number_format($valorItem, 2, '.', ''),
                (string) $item->stock_minimo,
                $item->getEstadoStock(),
            ]);
        }

        // Agregar fila de total
        fputcsv($handle, ['', '', 'TOTAL', '', '', number_format($valorTotal, 2, '.', ''), '', '']);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Yii::$app->response->sendContentAsFile(
            $csv !== false ? $csv : '',
            'valorizacion-inventario-' . date('Y-m-d-His') . '.csv',
            ['mimeType' => 'text/csv; charset=UTF-8']
        );
    }
}
