<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\Categoria;
use app\models\Servicio;
use app\models\InventoryItem;

/**
 * CategoriaService: lógica de negocio para categorías.
 * El árbol de categorías se cachea 1 hora en FileCache.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class CategoriaService extends BaseService
{
    protected string $logCategoria = 'app.categoria';

    private const CACHE_KEY_TREE = 'categoria_tree';
    private const CACHE_TTL      = 3600;

    /**
     * Retorna el árbol de categorías (desde caché si disponible).
     *
     * @param string|null $tipo  Filtrar por tipo ('servicio','insumo','ambos').
     * @return array<int,string>
     */
    public function getTree(?string $tipo = null): array
    {
        $cacheKey = self::CACHE_KEY_TREE . ($tipo ? '_' . $tipo : '_all');
        $cached   = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        $tree = Categoria::getTree($tipo);
        Yii::$app->cache->set($cacheKey, $tree, self::CACHE_TTL);
        return $tree;
    }

    /**
     * Crea una nueva categoría.
     *
     * @param array $data Datos de la categoría.
     * @return Categoria|null La categoría creada o null en caso de error.
     */
    public function create(array $data): ?Categoria
    {
        return $this->executeInTransaction(function () use ($data): Categoria {
            $cat = new Categoria();
            $cat->nombre      = trim($data['nombre'] ?? '');
            $cat->descripcion = $data['descripcion'] ?? null;
            $cat->padre_id    = !empty($data['padre_id']) ? (int) $data['padre_id'] : null;
            $cat->tipo        = $data['tipo'] ?? 'ambos';
            $cat->icono       = $data['icono'] ?? null;
            $cat->color       = $data['color'] ?? null;
            $cat->orden       = (int) ($data['orden'] ?? 0);
            $cat->status      = (int) ($data['status'] ?? 1);

            if (!$cat->validate()) {
                throw new ServiceException(implode('; ', $cat->getFirstErrors()));
            }
            $this->asegurar($cat->save(false), 'Error al guardar la categoría.');
            $this->invalidateCache();
            $this->log("Categoría creada: #{$cat->id} ({$cat->nombre})");
            return $cat;
        });
    }

    /**
     * Actualiza una categoría existente.
     *
     * @param Categoria $cat  Instancia a actualizar.
     * @param array     $data Datos a actualizar.
     * @return Categoria|null La categoría actualizada o null en caso de error.
     */
    public function update(Categoria $cat, array $data): ?Categoria
    {
        return $this->executeInTransaction(function () use ($cat, $data): Categoria {
            if (isset($data['nombre']))      $cat->nombre      = trim($data['nombre']);
            if (array_key_exists('descripcion', $data)) $cat->descripcion = $data['descripcion'];
            if (array_key_exists('padre_id', $data))    $cat->padre_id    = !empty($data['padre_id']) ? (int) $data['padre_id'] : null;
            if (isset($data['tipo']))        $cat->tipo        = $data['tipo'];
            if (array_key_exists('icono', $data))       $cat->icono       = $data['icono'];
            if (array_key_exists('color', $data))       $cat->color       = $data['color'];
            if (isset($data['orden']))       $cat->orden       = (int) $data['orden'];
            if (isset($data['status']))      $cat->status      = (int) $data['status'];

            if (!$cat->validate()) {
                throw new ServiceException(implode('; ', $cat->getFirstErrors()));
            }
            $this->asegurar($cat->save(false), 'Error al actualizar la categoría.');
            $this->invalidateCache();
            $this->log("Categoría actualizada: #{$cat->id}");
            return $cat;
        });
    }

    /**
     * Desactiva una categoría. Verifica que no tenga hijos activos.
     *
     * @param int $id ID de la categoría a desactivar.
     * @return bool True si se desactivó exitosamente.
     */
    public function deactivate(int $id): bool
    {
        $cat = Categoria::findOne($id);
        if ($cat === null) {
            $this->agregarError('Categoría no encontrada.');
            return false;
        }

        $hijosActivos = Categoria::find()->where(['padre_id' => $id, 'status' => 1])->count();
        if ($hijosActivos > 0) {
            $this->agregarError('No se puede desactivar: la categoría tiene subcategorías activas.');
            return false;
        }

        $cat->status = 0;
        if (!$cat->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar la categoría.');
            return false;
        }

        $this->invalidateCache();
        $this->log("Categoría desactivada: #{$id}");
        return true;
    }

    /**
     * Elimina una categoría solo si está vacía y sin subcategorías.
     *
     * @param int $id ID de categoría.
     * @return bool True si se eliminó.
     */
    public function deleteIfEmpty(int $id): bool
    {
        return (bool) $this->executeInTransaction(function () use ($id): bool {
            $cat = Categoria::findOne($id);
            if ($cat === null) {
                throw new ServiceException('Categoría no encontrada.');
            }

            $hijos = (int) Categoria::find()->where(['padre_id' => $id])->count();
            if ($hijos > 0) {
                throw new ServiceException('No se puede eliminar: la categoría tiene subcategorías.');
            }

            $servicios = (int) Servicio::find()->where(['categoria_id' => $id])->count();
            $insumos   = (int) InventoryItem::find()->where(['categoria_id' => $id])->count();
            if (($servicios + $insumos) > 0) {
                throw new ServiceException('No se puede eliminar: la categoría tiene items asociados.');
            }

            $this->asegurar((bool) $cat->delete(), 'Error al eliminar la categoría.');
            $this->invalidateCache();
            $this->log("Categoría eliminada: #{$id}");
            return true;
        });
    }

    /**
     * Invalida el caché del árbol de categorías.
     */
    public function invalidateCache(): void
    {
        Yii::$app->cache->delete(self::CACHE_KEY_TREE . '_all');
        foreach (['servicio', 'insumo', 'ambos'] as $tipo) {
            Yii::$app->cache->delete(self::CACHE_KEY_TREE . '_' . $tipo);
        }
    }
}
