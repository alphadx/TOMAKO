<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use app\models\Modelo;
use app\models\Marca;

/**
 * Controlador API para operaciones con modelos de vehículos.
 */
class ApiModeloController extends Controller
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
     * Busca modelos por término de búsqueda filtrados por marca.
     *
     * @return array
     */
    public function actionSearch(): array
    {
        $term = Yii::$app->request->get('q', '');
        $marcaId = Yii::$app->request->get('marca_id', 0);
        $term = strtoupper(trim($term));
        
        $query = Modelo::find();
        
        if ($marcaId > 0) {
            $query->andWhere(['marca_id' => $marcaId]);
        }
        
        if (!empty($term)) {
            $query->andWhere(['LIKE', 'modelo.nombre', $term]);
        }
        
        $modelos = $query
            ->innerJoinWith('marca')
            ->orderBy('modelo.nombre')
            ->limit(20)
            ->select(['modelo.id', 'modelo.nombre', 'modelo.marca_id'])
            ->asArray()
            ->all();
        
        // Formatear resultados para Select2
        $results = [];
        foreach ($modelos as $modelo) {
            $results[] = [
                'id' => $modelo['id'],
                'text' => $modelo['nombre'],
            ];
        }
        
        // Si hay término de búsqueda y no se encontró exacto, sugerir creación
        if (!empty($term) && $marcaId > 0) {
            $existeExacto = Modelo::findOne(['marca_id' => $marcaId, 'nombre' => $term]) !== null;
            
            if (!$existeExacto) {
                // Obtener nombre de la marca
                $marca = Marca::findOne($marcaId);
                $nombreMarca = $marca ? $marca->nombre : '';
                
                array_unshift($results, [
                    'id' => 'new:' . $term,
                    'text' => 'Crear nuevo modelo: ' . $term . ($nombreMarca ? ' (' . $nombreMarca . ')' : ''),
                    'create' => true,
                    'nombre' => $term,
                    'marca_id' => $marcaId,
                ]);
            }
        }
        
        return ['results' => $results];
    }

    /**
     * Crea un nuevo modelo.
     *
     * @return array
     */
    public function actionCreate(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $nombre = Yii::$app->request->post('nombre', '');
        $marcaId = (int) Yii::$app->request->post('marca_id', 0);
        $nombre = strtoupper(trim($nombre));
        
        if (empty($nombre)) {
            return [
                'success' => false,
                'message' => 'El nombre del modelo es obligatorio.',
            ];
        }
        
        if ($marcaId <= 0) {
            return [
                'success' => false,
                'message' => 'Debe seleccionar una marca.',
            ];
        }
        
        // Verificar si ya existe
        $modeloExistente = Modelo::findOne(['marca_id' => $marcaId, 'nombre' => $nombre]);
        if ($modeloExistente !== null) {
            return [
                'success' => true,
                'id' => $modeloExistente->id,
                'text' => $modeloExistente->nombre,
                'message' => 'El modelo ya existía.',
            ];
        }
        
        $modelo = new Modelo();
        $modelo->marca_id = $marcaId;
        $modelo->nombre = $nombre;
        
        if ($modelo->save()) {
            return [
                'success' => true,
                'id' => $modelo->id,
                'text' => $modelo->nombre,
            ];
        }
        
        return [
            'success' => false,
            'message' => 'No fue posible crear el modelo: ' . implode(', ', $modelo->getFirstErrors()),
        ];
    }

    /**
     * Lista todos los modelos de una marca específica.
     *
     * @return array
     */
    public function actionList(): array
    {
        $marcaId = (int) Yii::$app->request->get('marca_id', 0);
        
        if ($marcaId <= 0) {
            return ['results' => []];
        }
        
        $modelos = Modelo::find()
            ->where(['marca_id' => $marcaId])
            ->orderBy('nombre')
            ->select(['id', 'nombre'])
            ->asArray()
            ->all();
        
        $results = [];
        foreach ($modelos as $modelo) {
            $results[] = [
                'id' => $modelo['id'],
                'text' => $modelo['nombre'],
            ];
        }
        
        return ['results' => $results];
    }
}
