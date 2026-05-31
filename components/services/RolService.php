<?php
declare(strict_types=1);
namespace app\components\services;

use app\models\Rol;
use app\models\RolPermiso;

/**
 * RolService: gestión de roles y permisos.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class RolService extends BaseService
{
    protected string $logCategoria = 'app.rol';

    /**
     * Crea un nuevo rol.
     *
     * @param array $data Datos del rol (nombre, descripcion, activo).
     * @return Rol|null El rol creado o null en caso de error.
     */
    public function create(array $data): ?Rol
    {
        return $this->executeInTransaction(function () use ($data): Rol {
            $rol = new Rol();
            $rol->nombre      = $data['nombre'] ?? '';
            $rol->descripcion = $data['descripcion'] ?? null;
            $rol->activo      = (int) ($data['activo'] ?? 1);

            if (!$rol->validate()) {
                throw new ServiceException(implode('; ', $rol->getFirstErrors()));
            }
            $this->asegurar($rol->save(false), 'Error al guardar el rol.');
            $this->log("Rol creado: #{$rol->id} ({$rol->nombre})");
            return $rol;
        });
    }

    /**
     * Actualiza un rol existente.
     *
     * @param Rol   $rol  Instancia del rol a actualizar.
     * @param array $data Datos a actualizar.
     * @return Rol|null El rol actualizado o null en caso de error.
     */
    public function update(Rol $rol, array $data): ?Rol
    {
        return $this->executeInTransaction(function () use ($rol, $data): Rol {
            if (isset($data['nombre']))      $rol->nombre      = $data['nombre'];
            if (isset($data['descripcion'])) $rol->descripcion = $data['descripcion'];
            if (isset($data['activo']))      $rol->activo      = (int) $data['activo'];

            if (!$rol->validate()) {
                throw new ServiceException(implode('; ', $rol->getFirstErrors()));
            }
            $this->asegurar($rol->save(false), 'Error al actualizar el rol.');
            $this->log("Rol actualizado: #{$rol->id}");
            return $rol;
        });
    }

    /**
     * Asigna permisos a un rol (reemplaza los permisos existentes).
     *
     * @param int   $rolId      ID del rol.
     * @param int[] $permisoIds Array de IDs de permisos a asignar.
     */
    public function assignPermissions(int $rolId, array $permisoIds): void
    {
        $this->executeInTransaction(function () use ($rolId, $permisoIds): void {
            // Eliminar permisos actuales
            RolPermiso::deleteAll(['rol_id' => $rolId]);

            // Insertar nuevos permisos
            foreach ($permisoIds as $permisoId) {
                $rp = new RolPermiso();
                $rp->rol_id     = $rolId;
                $rp->permiso_id = (int) $permisoId;
                $rp->save(false);
            }
            $this->log("Permisos asignados al rol #{$rolId}: " . implode(',', $permisoIds));
            
            // Invalidar caché de permisos para usuarios con este rol
            \app\models\User::invalidatePermisoCache($rolId);
        });
    }

    /**
     * Retorna los IDs de permisos asignados a un rol.
     *
     * @param int $rolId ID del rol.
     * @return int[] Array de IDs de permisos.
     */
    public function getPermisosForRol(int $rolId): array
    {
        return array_map(
            'intval',
            RolPermiso::find()
                ->where(['rol_id' => $rolId])
                ->select('permiso_id')
                ->column()
        );
    }
}
