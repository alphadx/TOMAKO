<?php

use yii\db\Migration;

/**
 * Migración para agregar permisos del módulo de gestión de proveedores y órdenes de compra
 * HU-015 Fases 2, 3 y 4
 */
class m260524_000005_add_proveedor_permisos extends Migration
{
    public function safeUp()
    {
        $now = time();

        // Módulos nuevos para permisos
        $modulosNuevos = ['proveedor', 'orden-compra'];
        
        // Acciones estándar
        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        // Acciones adicionales específicas para orden-compra
        $accionesEspecialesOrdenCompra = [
            'enviar',
            'recibir',
            'cancelar',
            'agregar-item',
            'eliminar-item',
        ];

        // ── Insertar permisos para módulo proveedor ──────────────────────────────
        $permisosProveedor = [];
        foreach ($acciones as $accion) {
            $permisosProveedor[] = [
                'nombre'      => "proveedor.{$accion}",
                'descripcion' => ucfirst($accion) . ' proveedor',
                'modulo'      => 'proveedor',
                'created_at'  => $now,
            ];
        }
        
        if (!empty($permisosProveedor)) {
            $this->batchInsert('{{%permiso}}', ['nombre', 'descripcion', 'modulo', 'created_at'], $permisosProveedor);
        }

        // ── Insertar permisos para módulo orden-compra ───────────────────────────
        $permisosOrdenCompra = [];
        foreach ($acciones as $accion) {
            $permisosOrdenCompra[] = [
                'nombre'      => "orden-compra.{$accion}",
                'descripcion' => ucfirst($accion) . ' orden de compra',
                'modulo'      => 'orden-compra',
                'created_at'  => $now,
            ];
        }
        
        // Agregar acciones especiales
        foreach ($accionesEspecialesOrdenCompra as $accion) {
            $permisosOrdenCompra[] = [
                'nombre'      => "orden-compra.{$accion}",
                'descripcion' => ucfirst(str_replace('-', ' ', $accion)) . ' orden de compra',
                'modulo'      => 'orden-compra',
                'created_at'  => $now,
            ];
        }
        
        if (!empty($permisosOrdenCompra)) {
            $this->batchInsert('{{%permiso}}', ['nombre', 'descripcion', 'modulo', 'created_at'], $permisosOrdenCompra);
        }

        // ── Asignar permisos al rol Administrador (id=1) ─────────────────────────
        $permisoIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where(['in', 'modulo', $modulosNuevos])
            ->column();

        $rolPermisos = [];
        foreach ($permisoIds as $permisoId) {
            $rolPermisos[] = [1, $permisoId];
        }
        
        if (!empty($rolPermisos)) {
            // Evitar duplicados usando INSERT IGNORE o verificando existentes
            foreach ($rolPermisos as $rolPermiso) {
                $exists = (new \yii\db\Query())
                    ->from('{{%rol_permiso}}')
                    ->where(['rol_id' => $rolPermiso[0], 'permiso_id' => $rolPermiso[1]])
                    ->exists();
                
                if (!$exists) {
                    $this->insert('{{%rol_permiso}}', [
                        'rol_id' => $rolPermiso[0],
                        'permiso_id' => $rolPermiso[1],
                    ]);
                }
            }
        }

        // ── Asignar permisos básicos al rol Operador (id=2) ──────────────────────
        // Operador puede ver y crear órdenes de compra, ver proveedores
        $permisosOperador = (new \yii\db\Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where([
                'or',
                ['nombre' => 'proveedor.ver'],
                ['nombre' => 'orden-compra.ver'],
                ['nombre' => 'orden-compra.crear'],
                ['nombre' => 'orden-compra.recibir'],
            ])
            ->column();

        foreach ($permisosOperador as $permisoId) {
            $exists = (new \yii\db\Query())
                ->from('{{%rol_permiso}}')
                ->where(['rol_id' => 2, 'permiso_id' => $permisoId])
                ->exists();
            
            if (!$exists) {
                $this->insert('{{%rol_permiso}}', [
                    'rol_id' => 2,
                    'permiso_id' => $permisoId,
                ]);
            }
        }

        // ── Asignar permisos al rol Recepcionista (id=4) ─────────────────────────
        // Recepcionista puede ver proveedores y recibir órdenes de compra
        $permisosRecepcionista = (new \yii\db\Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where([
                'or',
                ['nombre' => 'proveedor.ver'],
                ['nombre' => 'orden-compra.ver'],
                ['nombre' => 'orden-compra.recibir'],
            ])
            ->column();

        foreach ($permisosRecepcionista as $permisoId) {
            $exists = (new \yii\db\Query())
                ->from('{{%rol_permiso}}')
                ->where(['rol_id' => 4, 'permiso_id' => $permisoId])
                ->exists();
            
            if (!$exists) {
                $this->insert('{{%rol_permiso}}', [
                    'rol_id' => 4,
                    'permiso_id' => $permisoId,
                ]);
            }
        }
    }

    public function safeDown()
    {
        // Eliminar asignaciones de permisos para roles
        $permisoIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where(['in', 'modulo', ['proveedor', 'orden-compra']])
            ->column();

        if (!empty($permisoIds)) {
            $this->delete('{{%rol_permiso}}', ['in', 'permiso_id', $permisoIds]);
        }

        // Eliminar permisos
        $this->delete('{{%permiso}}', ['in', 'modulo', ['proveedor', 'orden-compra']]);
    }
}
