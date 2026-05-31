<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: seed de roles, permisos y usuario administrador inicial.
 */
class m000000_000005_seed_roles_permisos extends Migration
{
    private array $modulos = [
        'usuario', 'rol', 'cliente', 'vehiculo', 'cita', 'orden',
        'inventario', 'servicio', 'categoria', 'tecnico', 'especialidad', 'pago', 'admin', 'marca',
    ];

    private array $acciones = ['ver', 'crear', 'editar', 'eliminar'];

    public function safeUp(): void
    {
        $now = time();

        // ── Roles ──────────────────────────────────────────────────────────────
        $this->batchInsert('{{%rol}}', ['id', 'nombre', 'descripcion', 'activo', 'created_at', 'updated_at'], [
            [1, 'Administrador',  'Acceso total al sistema',             1, $now, $now],
            [2, 'Operador',       'Acceso operativo general',            1, $now, $now],
            [3, 'Mecánico',       'Acceso a órdenes de servicio',        1, $now, $now],
            [4, 'Recepcionista',  'Acceso a clientes, citas y vehículos',1, $now, $now],
        ]);

        // ── Usuario Administrador ──────────────────────────────────────────────
        $adminPassword = trim((string) (getenv('TS_ADMIN_PASSWORD') ?: ''));
        if ($adminPassword === '') {
            $adminPassword = bin2hex(random_bytes(6)) . 'Aa!9';
            echo "[seed] TS_ADMIN_PASSWORD no definido. Password admin generado: {$adminPassword}\n";
        }

        $passwordHash = password_hash($adminPassword, PASSWORD_BCRYPT, ['cost' => 13]);
        $authKey      = bin2hex(random_bytes(16)); // 32 hex chars
        $this->insert('{{%usuario}}', [
            'username'       => 'admin',
            'email'          => 'admin@tomako.cl',
            'password_hash'  => $passwordHash,
            'auth_key'       => $authKey,
            'rol_id'         => 1,
            'nombre'         => 'Administrador',
            'apellido'       => 'Sistema',
            'activo'         => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);

        // ── Permisos ───────────────────────────────────────────────────────────
        $permisos = [];
        foreach ($this->modulos as $modulo) {
            foreach ($this->acciones as $accion) {
                $permisos[] = [
                    'nombre'      => "{$modulo}.{$accion}",
                    'descripcion' => ucfirst($accion) . ' ' . $modulo,
                    'modulo'      => $modulo,
                    'created_at'  => $now,
                ];
            }
        }
        $this->batchInsert('{{%permiso}}', ['nombre', 'descripcion', 'modulo', 'created_at'], $permisos);

        // ── Asignar TODOS los permisos al Administrador ────────────────────────
        $permisoIds = (new \yii\db\Query())
            ->select('id')
            ->from('{{%permiso}}')
            ->column();

        $rolPermisos = [];
        foreach ($permisoIds as $permisoId) {
            $rolPermisos[] = [1, $permisoId];
        }
        $this->batchInsert('{{%rol_permiso}}', ['rol_id', 'permiso_id'], $rolPermisos);
    }

    public function safeDown(): void
    {
        $this->delete('{{%rol_permiso}}', ['rol_id' => 1]);
        $this->delete('{{%permiso}}', ['modulo' => $this->modulos]);
        $this->delete('{{%usuario}}', ['email' => 'admin@tomako.cl']);
        $this->delete('{{%rol}}', ['id' => [1, 2, 3, 4]]);
    }
}
