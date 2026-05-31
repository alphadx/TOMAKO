<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea las tablas cita y cita_servicio (Módulo 8 – Citas).
 */
class m000000_000012_cita extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%cita}}', [
            'id'               => $this->primaryKey()->unsigned(),
            'cliente_id'       => $this->integer()->unsigned()->notNull(),
            'vehiculo_id'      => $this->integer()->unsigned()->notNull(),
            'fecha'            => $this->date()->notNull(),
            'hora_inicio'      => $this->time()->notNull(),
            'hora_fin'         => $this->time()->notNull(),
            'estado'           => "ENUM('pendiente','confirmada','en_progreso','completada','cancelada','no_show') NOT NULL DEFAULT 'pendiente'",
            'notas'            => $this->text()->null(),
            'orden_servicio_id'=> $this->integer()->unsigned()->null(),
            'created_at'       => $this->integer()->unsigned()->null(),
            'updated_at'       => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_cita_cliente',  '{{%cita}}', 'cliente_id');
        $this->createIndex('idx_cita_vehiculo', '{{%cita}}', 'vehiculo_id');
        $this->createIndex('idx_cita_fecha',    '{{%cita}}', 'fecha');
        $this->createIndex('idx_cita_estado',   '{{%cita}}', 'estado');

        $this->addForeignKey('fk_cita_cliente',  '{{%cita}}', 'cliente_id',  '{{%cliente}}',  'id', 'RESTRICT');
        $this->addForeignKey('fk_cita_vehiculo', '{{%cita}}', 'vehiculo_id', '{{%vehiculo}}', 'id', 'RESTRICT');

        $this->createTable('{{%cita_servicio}}', [
            'cita_id'     => $this->integer()->unsigned()->notNull(),
            'servicio_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addPrimaryKey('pk_cita_servicio', '{{%cita_servicio}}', ['cita_id', 'servicio_id']);
        $this->addForeignKey('fk_cs_cita',     '{{%cita_servicio}}', 'cita_id',     '{{%cita}}',     'id', 'CASCADE');
        $this->addForeignKey('fk_cs_servicio', '{{%cita_servicio}}', 'servicio_id', '{{%servicio}}', 'id', 'RESTRICT');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_cs_servicio', '{{%cita_servicio}}');
        $this->dropForeignKey('fk_cs_cita',     '{{%cita_servicio}}');
        $this->dropTable('{{%cita_servicio}}');

        $this->dropForeignKey('fk_cita_vehiculo', '{{%cita}}');
        $this->dropForeignKey('fk_cita_cliente',  '{{%cita}}');
        $this->dropTable('{{%cita}}');
    }
}
