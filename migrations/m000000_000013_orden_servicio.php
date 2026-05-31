<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea las tablas del módulo 9 – Órdenes de Servicio:
 * orden_servicio, orden_servicio_detalle, asignacion_orden, orden_nota, orden_estado_log.
 */
class m000000_000013_orden_servicio extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%orden_servicio}}', [
            'id'             => $this->primaryKey()->unsigned(),
            'codigo'         => $this->string(20)->notNull()->unique(),
            'cliente_id'     => $this->integer()->unsigned()->notNull(),
            'vehiculo_id'    => $this->integer()->unsigned()->notNull(),
            'cita_id'        => $this->integer()->unsigned()->null(),
            'estado'         => "ENUM('abierto','en_progreso','esperando_repuestos','listo_para_entrega','entregada','cancelada') NOT NULL DEFAULT 'abierto'",
            'prioridad'      => "ENUM('baja','normal','alta','urgente') NOT NULL DEFAULT 'normal'",
            'total'          => $this->decimal(10, 2)->notNull()->defaultValue(0.00),
            'notas_generales'=> $this->text()->null(),
            'opened_at'      => $this->integer()->unsigned()->null(),
            'closed_at'      => $this->integer()->unsigned()->null(),
            'created_at'     => $this->integer()->unsigned()->null(),
            'updated_at'     => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_os_cliente',  '{{%orden_servicio}}', 'cliente_id');
        $this->createIndex('idx_os_vehiculo', '{{%orden_servicio}}', 'vehiculo_id');
        $this->createIndex('idx_os_estado',   '{{%orden_servicio}}', 'estado');
        $this->createIndex('idx_os_codigo',   '{{%orden_servicio}}', 'codigo');

        $this->addForeignKey('fk_os_cliente',  '{{%orden_servicio}}', 'cliente_id',  '{{%cliente}}',  'id', 'RESTRICT');
        $this->addForeignKey('fk_os_vehiculo', '{{%orden_servicio}}', 'vehiculo_id', '{{%vehiculo}}', 'id', 'RESTRICT');
        $this->addForeignKey('fk_os_cita',     '{{%orden_servicio}}', 'cita_id',     '{{%cita}}',     'id', 'SET NULL');

        $this->createTable('{{%orden_servicio_detalle}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'orden_id'        => $this->integer()->unsigned()->notNull(),
            'servicio_id'     => $this->integer()->unsigned()->notNull(),
            'cantidad'        => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'precio_unitario' => $this->decimal(10, 2)->notNull(),
            'subtotal'        => $this->decimal(10, 2)->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_osd_orden', '{{%orden_servicio_detalle}}', 'orden_id');
        $this->addForeignKey('fk_osd_orden',    '{{%orden_servicio_detalle}}', 'orden_id',    '{{%orden_servicio}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_osd_servicio', '{{%orden_servicio_detalle}}', 'servicio_id', '{{%servicio}}',       'id', 'RESTRICT');

        $this->createTable('{{%asignacion_orden}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'orden_id'    => $this->integer()->unsigned()->notNull(),
            'tecnico_id'  => $this->integer()->unsigned()->notNull(),
            'asignado_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_ao_orden_tecnico', '{{%asignacion_orden}}', ['orden_id', 'tecnico_id'], true);
        $this->addForeignKey('fk_ao_orden',   '{{%asignacion_orden}}', 'orden_id',   '{{%orden_servicio}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_ao_tecnico', '{{%asignacion_orden}}', 'tecnico_id', '{{%tecnico}}',        'id', 'RESTRICT');

        $this->createTable('{{%orden_nota}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'orden_id'   => $this->integer()->unsigned()->notNull(),
            'usuario_id' => $this->integer()->unsigned()->null(),
            'texto'      => $this->text()->notNull(),
            'created_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_on_orden', '{{%orden_nota}}', 'orden_id');
        $this->addForeignKey('fk_on_orden',   '{{%orden_nota}}', 'orden_id',   '{{%orden_servicio}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_on_usuario', '{{%orden_nota}}', 'usuario_id', '{{%usuario}}',        'id', 'SET NULL');

        $this->createTable('{{%orden_estado_log}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'orden_id'        => $this->integer()->unsigned()->notNull(),
            'estado_anterior' => $this->string(30)->null(),
            'estado_nuevo'    => $this->string(30)->notNull(),
            'usuario_id'      => $this->integer()->unsigned()->null(),
            'comentario'      => $this->text()->null(),
            'created_at'      => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_oel_orden', '{{%orden_estado_log}}', 'orden_id');
        $this->addForeignKey('fk_oel_orden', '{{%orden_estado_log}}', 'orden_id', '{{%orden_servicio}}', 'id', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_oel_orden', '{{%orden_estado_log}}');
        $this->dropTable('{{%orden_estado_log}}');

        $this->dropForeignKey('fk_on_usuario', '{{%orden_nota}}');
        $this->dropForeignKey('fk_on_orden',   '{{%orden_nota}}');
        $this->dropTable('{{%orden_nota}}');

        $this->dropForeignKey('fk_ao_tecnico', '{{%asignacion_orden}}');
        $this->dropForeignKey('fk_ao_orden',   '{{%asignacion_orden}}');
        $this->dropTable('{{%asignacion_orden}}');

        $this->dropForeignKey('fk_osd_servicio', '{{%orden_servicio_detalle}}');
        $this->dropForeignKey('fk_osd_orden',    '{{%orden_servicio_detalle}}');
        $this->dropTable('{{%orden_servicio_detalle}}');

        $this->dropForeignKey('fk_os_cita',     '{{%orden_servicio}}');
        $this->dropForeignKey('fk_os_vehiculo', '{{%orden_servicio}}');
        $this->dropForeignKey('fk_os_cliente',  '{{%orden_servicio}}');
        $this->dropTable('{{%orden_servicio}}');
    }
}
