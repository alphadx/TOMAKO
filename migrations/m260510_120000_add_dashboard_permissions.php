<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Query;

final class m260510_120000_add_dashboard_permissions extends Migration
{
    public function safeUp(): void
    {
        $now = time();

        $permissions = [
            ['dashboard.ver', 'Ver dashboard'],
            ['dashboard.view', 'View dashboard'],
        ];

        foreach ($permissions as [$name, $description]) {
            $exists = (new Query())
                ->from('{{%permiso}}')
                ->where(['nombre' => $name])
                ->exists();

            if ($exists) {
                continue;
            }

            $this->insert('{{%permiso}}', [
                'nombre' => $name,
                'descripcion' => $description,
                'modulo' => 'dashboard',
                'created_at' => $now,
            ]);
        }

        $roles = (new Query())
            ->select('id')
            ->from('{{%rol}}')
            ->where(['activo' => 1])
            ->column();

        $dashboardPermissionIds = (new Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where(['in', 'nombre', ['dashboard.ver', 'dashboard.view']])
            ->column();

        foreach ($roles as $rolId) {
            foreach ($dashboardPermissionIds as $permisoId) {
                $relationExists = (new Query())
                    ->from('{{%rol_permiso}}')
                    ->where(['rol_id' => (int) $rolId, 'permiso_id' => (int) $permisoId])
                    ->exists();

                if ($relationExists) {
                    continue;
                }

                $this->insert('{{%rol_permiso}}', [
                    'rol_id' => (int) $rolId,
                    'permiso_id' => (int) $permisoId,
                ]);
            }
        }
    }

    public function safeDown(): void
    {
        $permissionIds = (new Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->where(['in', 'nombre', ['dashboard.ver', 'dashboard.view']])
            ->column();

        if ($permissionIds !== []) {
            $this->delete('{{%rol_permiso}}', ['permiso_id' => $permissionIds]);
            $this->delete('{{%permiso}}', ['id' => $permissionIds]);
        }
    }
}
