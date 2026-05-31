<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Expande Hito 10 (Pagos/Finanzas) y crea Hito 11 (Notificaciones).
 */
class m000000_000018_finanzas_notificaciones extends Migration
{
    public function safeUp(): void
    {
        $this->crearTablaMetodoPago();
        $this->expandirTablaPago();
        $this->crearTablaCierreCaja();
        $this->asociarPagosACierreCaja();

        $this->crearTablaNotificacion();
        $this->crearTablaPlantillaNotificacion();
        $this->crearTablaEmailLog();
        $this->crearTablaPreferenciaNotificacion();
        $this->seedPlantillasNotificacion();
    }

    public function safeDown(): void
    {
        if ($this->db->schema->getTableSchema('{{%preferencia_notificacion}}', true) !== null) {
            $this->dropForeignKey('fk_pref_notif_usuario', '{{%preferencia_notificacion}}');
            $this->dropTable('{{%preferencia_notificacion}}');
        }

        if ($this->db->schema->getTableSchema('{{%email_log}}', true) !== null) {
            $this->dropTable('{{%email_log}}');
        }

        if ($this->db->schema->getTableSchema('{{%plantilla_notificacion}}', true) !== null) {
            $this->dropTable('{{%plantilla_notificacion}}');
        }

        if ($this->db->schema->getTableSchema('{{%notificacion}}', true) !== null) {
            $this->dropForeignKey('fk_notificacion_usuario', '{{%notificacion}}');
            $this->dropTable('{{%notificacion}}');
        }

        if ($this->db->schema->getTableSchema('{{%cierre_caja}}', true) !== null) {
            $this->dropForeignKey('fk_cierre_caja_usuario', '{{%cierre_caja}}');
            $this->dropTable('{{%cierre_caja}}');
        }

        $pago = $this->db->schema->getTableSchema('{{%pago}}', true);
        if ($pago !== null) {
            if (isset($pago->columns['cierre_caja_id'])) {
                $this->dropForeignKey('fk_pago_cierre_caja', '{{%pago}}');
                $this->dropIndex('idx_pago_cierre_caja_id', '{{%pago}}');
                $this->dropColumn('{{%pago}}', 'cierre_caja_id');
            }
            if (isset($pago->columns['metodo_pago_id'])) {
                $this->dropForeignKey('fk_pago_metodo_pago', '{{%pago}}');
                $this->dropIndex('idx_pago_metodo_pago_id', '{{%pago}}');
                $this->dropColumn('{{%pago}}', 'metodo_pago_id');
            }
            if (isset($pago->columns['anulado_motivo'])) {
                $this->dropColumn('{{%pago}}', 'anulado_motivo');
            }
            if (isset($pago->columns['observaciones'])) {
                $this->dropColumn('{{%pago}}', 'observaciones');
            }
        }

        if ($this->db->schema->getTableSchema('{{%metodo_pago}}', true) !== null) {
            $this->dropTable('{{%metodo_pago}}');
        }
    }

    private function crearTablaMetodoPago(): void
    {
        if ($this->db->schema->getTableSchema('{{%metodo_pago}}', true) !== null) {
            return;
        }

        $this->createTable('{{%metodo_pago}}', [
            'id' => $this->primaryKey()->unsigned(),
            'codigo' => $this->string(30)->notNull(),
            'nombre' => $this->string(80)->notNull(),
            'activo' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_metodo_pago_codigo', '{{%metodo_pago}}', 'codigo', true);

        $now = time();
        $this->batchInsert('{{%metodo_pago}}', ['codigo', 'nombre', 'activo', 'created_at', 'updated_at'], [
            ['efectivo', 'Efectivo', 1, $now, $now],
            ['tarjeta_debito', 'Tarjeta Debito', 1, $now, $now],
            ['tarjeta_credito', 'Tarjeta Credito', 1, $now, $now],
            ['transferencia', 'Transferencia', 1, $now, $now],
            ['otro', 'Otro', 1, $now, $now],
        ]);
    }

    private function expandirTablaPago(): void
    {
        $pago = $this->db->schema->getTableSchema('{{%pago}}', true);
        if ($pago === null) {
            return;
        }

        if (!isset($pago->columns['metodo_pago_id'])) {
            $this->addColumn('{{%pago}}', 'metodo_pago_id', $this->integer()->unsigned()->null()->after('monto'));
            $this->createIndex('idx_pago_metodo_pago_id', '{{%pago}}', 'metodo_pago_id');
            $this->addForeignKey(
                'fk_pago_metodo_pago',
                '{{%pago}}',
                'metodo_pago_id',
                '{{%metodo_pago}}',
                'id',
                'RESTRICT'
            );

            $this->execute("UPDATE {{%pago}} p JOIN {{%metodo_pago}} m ON m.codigo = p.metodo_pago SET p.metodo_pago_id = m.id");
        }

        $pago = $this->db->schema->getTableSchema('{{%pago}}', true);
        if (!isset($pago->columns['observaciones'])) {
            $this->addColumn('{{%pago}}', 'observaciones', $this->text()->null()->after('notas'));
        }

        if (!isset($pago->columns['anulado_motivo'])) {
            $this->addColumn('{{%pago}}', 'anulado_motivo', $this->string(255)->null()->after('observaciones'));
        }
    }

    private function crearTablaCierreCaja(): void
    {
        if ($this->db->schema->getTableSchema('{{%cierre_caja}}', true) !== null) {
            return;
        }

        $this->createTable('{{%cierre_caja}}', [
            'id' => $this->primaryKey()->unsigned(),
            'usuario_id' => $this->integer()->unsigned()->notNull(),
            'fecha' => $this->date()->notNull(),
            'monto_inicial' => $this->decimal(10, 2)->notNull()->defaultValue(0),
            'monto_esperado' => $this->decimal(10, 2)->null(),
            'monto_final' => $this->decimal(10, 2)->null(),
            'diferencia' => $this->decimal(10, 2)->null(),
            'estado' => "ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto'",
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
            'closed_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_cierre_caja_usuario_id', '{{%cierre_caja}}', 'usuario_id');
        $this->createIndex('idx_cierre_caja_fecha', '{{%cierre_caja}}', 'fecha');
        $this->createIndex('idx_cierre_caja_estado', '{{%cierre_caja}}', 'estado');

        $this->addForeignKey(
            'fk_cierre_caja_usuario',
            '{{%cierre_caja}}',
            'usuario_id',
            '{{%usuario}}',
            'id',
            'RESTRICT'
        );
    }

    private function asociarPagosACierreCaja(): void
    {
        $pago = $this->db->schema->getTableSchema('{{%pago}}', true);
        if ($pago === null) {
            return;
        }

        if (!isset($pago->columns['cierre_caja_id'])) {
            $this->addColumn('{{%pago}}', 'cierre_caja_id', $this->integer()->unsigned()->null()->after('orden_id'));
            $this->createIndex('idx_pago_cierre_caja_id', '{{%pago}}', 'cierre_caja_id');
            $this->addForeignKey(
                'fk_pago_cierre_caja',
                '{{%pago}}',
                'cierre_caja_id',
                '{{%cierre_caja}}',
                'id',
                'SET NULL'
            );
        }
    }

    private function crearTablaNotificacion(): void
    {
        if ($this->db->schema->getTableSchema('{{%notificacion}}', true) !== null) {
            return;
        }

        $this->createTable('{{%notificacion}}', [
            'id' => $this->primaryKey()->unsigned(),
            'usuario_id' => $this->integer()->unsigned()->notNull(),
            'tipo' => "ENUM('stock_bajo','orden_lista','cita_confirmada','sistema') NOT NULL DEFAULT 'sistema'",
            'titulo' => $this->string(150)->notNull(),
            'mensaje' => $this->text()->notNull(),
            'url' => $this->string(300)->null(),
            'leida' => $this->boolean()->notNull()->defaultValue(0),
            'leida_at' => $this->integer()->unsigned()->null(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_notif_usuario_leida', '{{%notificacion}}', ['usuario_id', 'leida']);
        $this->createIndex('idx_notif_created_at', '{{%notificacion}}', 'created_at');

        $this->addForeignKey(
            'fk_notificacion_usuario',
            '{{%notificacion}}',
            'usuario_id',
            '{{%usuario}}',
            'id',
            'CASCADE'
        );
    }

    private function crearTablaPlantillaNotificacion(): void
    {
        if ($this->db->schema->getTableSchema('{{%plantilla_notificacion}}', true) !== null) {
            return;
        }

        $this->createTable('{{%plantilla_notificacion}}', [
            'id' => $this->primaryKey()->unsigned(),
            'codigo' => $this->string(60)->notNull(),
            'canal' => "ENUM('email','interno','ambos') NOT NULL DEFAULT 'email'",
            'asunto' => $this->string(200)->notNull(),
            'cuerpo_html' => $this->text()->notNull(),
            'variables' => $this->text()->null(),
            'activo' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_plantilla_codigo', '{{%plantilla_notificacion}}', 'codigo', true);
    }

    private function crearTablaEmailLog(): void
    {
        if ($this->db->schema->getTableSchema('{{%email_log}}', true) !== null) {
            return;
        }

        $this->createTable('{{%email_log}}', [
            'id' => $this->primaryKey()->unsigned(),
            'destinatario' => $this->string(254)->notNull(),
            'asunto' => $this->string(200)->notNull(),
            'cuerpo_html' => $this->text()->notNull(),
            'plantilla' => $this->string(60)->null(),
            'exito' => $this->boolean()->notNull()->defaultValue(0),
            'error' => $this->text()->null(),
            'enviado_at' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_emaillog_destinatario', '{{%email_log}}', 'destinatario');
        $this->createIndex('idx_emaillog_exito', '{{%email_log}}', 'exito');
        $this->createIndex('idx_emaillog_enviado_at', '{{%email_log}}', 'enviado_at');
    }

    private function crearTablaPreferenciaNotificacion(): void
    {
        if ($this->db->schema->getTableSchema('{{%preferencia_notificacion}}', true) !== null) {
            return;
        }

        $this->createTable('{{%preferencia_notificacion}}', [
            'id' => $this->primaryKey()->unsigned(),
            'usuario_id' => $this->integer()->unsigned()->notNull(),
            'email_cita' => $this->boolean()->notNull()->defaultValue(1),
            'email_orden_estado' => $this->boolean()->notNull()->defaultValue(1),
            'interno_stock' => $this->boolean()->notNull()->defaultValue(1),
            'interno_orden' => $this->boolean()->notNull()->defaultValue(1),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uq_pref_notif_usuario', '{{%preferencia_notificacion}}', 'usuario_id', true);
        $this->addForeignKey(
            'fk_pref_notif_usuario',
            '{{%preferencia_notificacion}}',
            'usuario_id',
            '{{%usuario}}',
            'id',
            'CASCADE'
        );
    }

    private function seedPlantillasNotificacion(): void
    {
        $exists = (new \yii\db\Query())
            ->from('{{%plantilla_notificacion}}')
            ->count('*', $this->db);

        if ((int) $exists > 0) {
            return;
        }

        $now = time();
        $this->batchInsert('{{%plantilla_notificacion}}', ['codigo', 'canal', 'asunto', 'cuerpo_html', 'variables', 'activo', 'created_at', 'updated_at'], [
            [
                'cita.confirmacion',
                'email',
                'Confirmacion de cita en TOMAKO',
                '<p>Hola <strong>{{cliente_nombre}}</strong>, tu cita ha sido confirmada para el <strong>{{fecha}}</strong> a las <strong>{{hora}}</strong>.</p>',
                '["cliente_nombre","fecha","hora"]',
                1,
                $now,
                $now,
            ],
            [
                'cita.recordatorio_24h',
                'email',
                'Recordatorio: tu cita es manana',
                '<p>Hola <strong>{{cliente_nombre}}</strong>, te recordamos tu cita para manana <strong>{{fecha}}</strong> a las <strong>{{hora}}</strong>.</p>',
                '["cliente_nombre","fecha","hora"]',
                1,
                $now,
                $now,
            ],
            [
                'orden.lista_entrega',
                'ambos',
                'Tu vehiculo esta listo para retirar',
                '<p>Hola <strong>{{cliente_nombre}}</strong>, la orden <strong>{{codigo_orden}}</strong> para el vehiculo <strong>{{vehiculo}}</strong> esta lista para retiro.</p>',
                '["cliente_nombre","codigo_orden","vehiculo","total"]',
                1,
                $now,
                $now,
            ],
            [
                'orden.estado_actualizado',
                'email',
                'Actualizacion de tu orden {{codigo_orden}}',
                '<p>Hola <strong>{{cliente_nombre}}</strong>, tu orden <strong>{{codigo_orden}}</strong> cambio a estado <strong>{{estado}}</strong>.</p>',
                '["cliente_nombre","codigo_orden","estado","vehiculo"]',
                1,
                $now,
                $now,
            ],
        ]);
    }
}
