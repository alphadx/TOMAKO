<?php

use yii\db\Migration;

/**
 * Creates orden_servicio_detalle table
 * Line items for services in work orders
 */
class m000018_create_orden_servicio_detalle extends Migration
{
    public function safeUp()
    {
        $this->createTable('orden_servicio_detalle', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'servicio_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'cantidad' => $this->integer()->defaultValue(1)->notNull()->append('UNSIGNED'),
            'precio_unitario' => $this->decimal(10, 2)->notNull(),
            'subtotal' => $this->decimal(10, 2)->notNull()->comment('cantidad * precio_unitario'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Foreign keys
        $this->addForeignKey(
            'fk_orden_detalle_orden_id',
            'orden_servicio_detalle',
            'orden_id',
            'orden_servicio',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_orden_detalle_servicio_id',
            'orden_servicio_detalle',
            'servicio_id',
            'servicio',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // Indices
        $this->createIndex('idx_orden_detalle_orden_id', 'orden_servicio_detalle', 'orden_id');
        $this->createIndex('idx_orden_detalle_servicio_id', 'orden_servicio_detalle', 'servicio_id');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_orden_detalle_servicio_id', 'orden_servicio_detalle');
        $this->dropForeignKey('fk_orden_detalle_orden_id', 'orden_servicio_detalle');
        $this->dropTable('orden_servicio_detalle');
    }
}
