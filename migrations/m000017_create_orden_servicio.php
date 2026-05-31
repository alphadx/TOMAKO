<?php

use yii\db\Migration;

/**
 * Creates orden_servicio table
 * Main work order table with unique JOB-NNN code
 */
class m000017_create_orden_servicio extends Migration
{
    public function safeUp()
    {
        $this->createTable('orden_servicio', [
            'id' => $this->primaryKey()->unsigned(),
            'codigo' => $this->string(10)->notNull()->unique()->comment('JOB-NNN format'),
            'cliente_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'vehiculo_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'cita_id' => $this->integer()->null()->append('UNSIGNED')->comment('Related appointment if created from cita'),
            'estado' => $this->enum('estado', [
                'abierto',
                'en_progreso',
                'esperando_repuestos',
                'listo_para_entrega',
                'entregada',
                'cancelada'
            ])->defaultValue('abierto')->notNull(),
            'prioridad' => $this->enum('prioridad', [
                'baja',
                'normal',
                'alta',
                'urgente'
            ])->defaultValue('normal')->notNull(),
            'total' => $this->decimal(10, 2)->defaultValue(0)->notNull(),
            'notas_generales' => $this->text()->null(),
            'opened_at' => $this->timestamp()->null(),
            'closed_at' => $this->timestamp()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Foreign keys
        $this->addForeignKey(
            'fk_orden_servicio_cliente_id',
            'orden_servicio',
            'cliente_id',
            'cliente',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_orden_servicio_vehiculo_id',
            'orden_servicio',
            'vehiculo_id',
            'vehiculo',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_orden_servicio_cita_id',
            'orden_servicio',
            'cita_id',
            'cita',
            'id',
            'SET NULL',
            'CASCADE'
        );

        // Indices for common queries
        $this->createIndex('idx_orden_servicio_estado', 'orden_servicio', 'estado');
        $this->createIndex('idx_orden_servicio_cliente_id', 'orden_servicio', 'cliente_id');
        $this->createIndex('idx_orden_servicio_vehiculo_id', 'orden_servicio', 'vehiculo_id');
        $this->createIndex('idx_orden_servicio_estado_prioridad', 'orden_servicio', ['estado', 'prioridad']);
        $this->createIndex('idx_orden_servicio_created_at', 'orden_servicio', 'created_at');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_orden_servicio_cita_id', 'orden_servicio');
        $this->dropForeignKey('fk_orden_servicio_vehiculo_id', 'orden_servicio');
        $this->dropForeignKey('fk_orden_servicio_cliente_id', 'orden_servicio');
        $this->dropTable('orden_servicio');
    }
}
