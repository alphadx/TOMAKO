<?php

use yii\db\Migration;

/**
 * Creates asignacion_orden table
 * Technician assignments to work orders
 */
class m000019_create_asignacion_orden extends Migration
{
    public function safeUp()
    {
        $this->createTable('asignacion_orden', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'tecnico_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Foreign keys
        $this->addForeignKey(
            'fk_asignacion_orden_id',
            'asignacion_orden',
            'orden_id',
            'orden_servicio',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_asignacion_tecnico_id',
            'asignacion_orden',
            'tecnico_id',
            'tecnico',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // Unique constraint: don't assign same technician twice
        $this->createIndex(
            'idx_asignacion_orden_tecnico_unique',
            'asignacion_orden',
            ['orden_id', 'tecnico_id'],
            true  // unique
        );

        // Indices
        $this->createIndex('idx_asignacion_orden_id', 'asignacion_orden', 'orden_id');
        $this->createIndex('idx_asignacion_tecnico_id', 'asignacion_orden', 'tecnico_id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_asignacion_tecnico_id', 'asignacion_orden');
        $this->dropForeignKey('fk_asignacion_orden_id', 'asignacion_orden');
        $this->dropTable('asignacion_orden');
    }
}
