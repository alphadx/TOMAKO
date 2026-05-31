<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\models\Marca;

/**
 * Controlador API para operaciones con marcas de vehículos.
 */
class ApiMarcaController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        
        $behaviors['contentNegotiator'] = [
            'class' => \yii\filters\ContentNegotiator::class,
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        return $behaviors;
    }

    /**
     * Busca marcas por término de búsqueda.
     * Retorna marcas existentes y opción de crear nueva si no existe.
     *
     * @return array
     */
    public function actionSearch(): array
    {
        $term = Yii::$app->request->get('q', '');
        $term = strtoupper(trim($term));
        
        $query = Marca::find();
        
        if (!empty($term)) {
            $query->where(['LIKE', 'nombre', $term]);
        }
        
        $marcas = $query
            ->orderBy('nombre')
            ->limit(20)
            ->select(['id', 'nombre'])
            ->asArray()
            ->all();
        
        // Formatear resultados para Select2
        $results = [];
        foreach ($marcas as $marca) {
            $results[] = [
                'id' => $marca['id'],
                'text' => $marca['nombre'],
            ];
        }
        
        // Si hay término de búsqueda y no se encontró exacto, sugerir creación
        if (!empty($term)) {
            $existeExacto = Marca::findOne(['nombre' => $term]) !== null;
            
            if (!$existeExacto) {
                array_unshift($results, [
                    'id' => 'new:' . $term,
                    'text' => 'Crear nueva marca: ' . $term,
                    'create' => true,
                    'nombre' => $term,
                ]);
            }
        }
        
        return ['results' => $results];
    }

    /**
     * Crea una nueva marca.
     *
     * @return array
     */
    public function actionCreate(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $nombre = Yii::$app->request->post('nombre', '');
        $nombre = strtoupper(trim($nombre));
        
        if (empty($nombre)) {
            return [
                'success' => false,
                'message' => 'El nombre de la marca es obligatorio.',
            ];
        }
        
        // Verificar si ya existe
        $marcaExistente = Marca::findOne(['nombre' => $nombre]);
        if ($marcaExistente !== null) {
            return [
                'success' => true,
                'id' => $marcaExistente->id,
                'text' => $marcaExistente->nombre,
                'message' => 'La marca ya existía.',
            ];
        }
        
        $marca = new Marca();
        $marca->nombre = $nombre;
        
        if ($marca->save()) {
            return [
                'success' => true,
                'id' => $marca->id,
                'text' => $marca->nombre,
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No fue posible crear la marca: ' . implode(', ', $marca->getFirstErrors()),
        ];
    }

    /**
     * Lista todas las marcas activas.
     *
     * @return array
     */
    public function actionList(): array
    {
        $marcas = Marca::find()
            ->orderBy('nombre')
            ->select(['id', 'nombre'])
            ->asArray()
            ->all();
        
        $results = [];
        foreach ($marcas as $marca) {
            $results[] = [
                'id' => $marca['id'],
                'text' => $marca['nombre'],
            ];
        }
        
        return ['results' => $results];
    }
}
