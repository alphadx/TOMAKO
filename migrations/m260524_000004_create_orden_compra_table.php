<?php

use yii\db\Migration;

/**
 * Migración para crear tablas de órdenes de compra (HU-015 Fase 2)
 * 
 * Crea:
 * - Tabla orden_compra: Cabecera de órdenes de compra a proveedores
 * - Tabla orden_compra_item: Items/detalles de cada orden de compra
 */
class m260524_000004_create_orden_compra_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Crear tabla orden_compra
        $this->createTable('{{%orden_compra}}', [
            'id' => $this->primaryKey()->unsigned(),
            'numero_orden' => $this->string(50)->unique()->comment('Número de orden de compra'),
            'proveedor_id' => $this->integer()->unsigned()->notNull()->comment('Proveedor asociado'),
            'fecha_emision' => $this->date()->notNull()->comment('Fecha de emisión de la orden'),
            'fecha_entrega_esperada' => $this->date()->comment('Fecha esperada de entrega'),
            'fecha_entrega_real' => $this->date()->comment('Fecha real de entrega'),
            'estado' => $this->string(20)->notNull()->defaultValue('borrador')->comment('Estado: borrador, enviada, recibida_parcial, recibida_completo, cancelada'),
            'total_monto' => $this->decimal(10, 2)->defaultValue(0.00)->comment('Monto total de la orden'),
            'observaciones' => $this->text()->comment('Observaciones adicionales'),
            'created_by' => $this->integer()->unsigned()->comment('Usuario que creó la orden'),
            'updated_by' => $this->integer()->unsigned()->comment('Usuario que actualizó la orden'),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Fecha de actualización'),
        ], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Órdenes de compra a proveedores'");

        // Crear tabla orden_compra_item
        $this->createTable('{{%orden_compra_item}}', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_compra_id' => $this->integer()->unsigned()->notNull()->comment('Orden de compra asociada'),
            'inventory_item_id' => $this->integer()->unsigned()->null()->comment('Repuesto/inventario asociado (opcional)'),
            'descripcion' => $this->string(255)->notNull()->comment('Descripción del item'),
            'cantidad' => $this->integer()->notNull()->defaultValue(1)->comment('Cantidad solicitada'),
            'cantidad_recibida' => $this->integer()->defaultValue(0)->comment('Cantidad recibida'),
            'precio_unitario' => $this->decimal(10, 2)->notNull()->comment('Precio unitario del item'),
            'subtotal' => $this->decimal(10, 2)->comment('Subtotal (cantidad * precio_unitario)'),
            'observaciones' => $this->text()->comment('Observaciones del item'),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Fecha de actualización'),
        ], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Items de órdenes de compra'");

        // Índices para mejorar rendimiento
        $this->createIndex('idx_orden_compra_numero', '{{%orden_compra}}', 'numero_orden');
        $this->createIndex('idx_orden_compra_proveedor', '{{%orden_compra}}', 'proveedor_id');
        $this->createIndex('idx_orden_compra_estado', '{{%orden_compra}}', 'estado');
        $this->createIndex('idx_orden_compra_fecha_emision', '{{%orden_compra}}', 'fecha_emision');
        
        $this->createIndex('idx_orden_compra_item_orden', '{{%orden_compra_item}}', 'orden_compra_id');
        $this->createIndex('idx_orden_compra_item_inventory', '{{%orden_compra_item}}', 'inventory_item_id');

        // Foreign keys para orden_compra
        $this->addForeignKey('fk_orden_compra_proveedor', '{{%orden_compra}}', 'proveedor_id', '{{%proveedor}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_orden_compra_created_by', '{{%orden_compra}}', 'created_by', '{{%usuario}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_orden_compra_updated_by', '{{%orden_compra}}', 'updated_by', '{{%usuario}}', 'id', 'SET NULL', 'CASCADE');

        // Foreign keys para orden_compra_item
        $this->addForeignKey('fk_orden_compra_item_orden', '{{%orden_compra_item}}', 'orden_compra_id', '{{%orden_compra}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_orden_compra_item_inventory', '{{%orden_compra_item}}', 'inventory_item_id', '{{%inventory_item}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Eliminar foreign keys de orden_compra_item
        $this->dropForeignKey('fk_orden_compra_item_inventory', '{{%orden_compra_item}}');
        $this->dropForeignKey('fk_orden_compra_item_orden', '{{%orden_compra_item}}');
        
        // Eliminar foreign keys de orden_compra
        $this->dropForeignKey('fk_orden_compra_updated_by', '{{%orden_compra}}');
        $this->dropForeignKey('fk_orden_compra_created_by', '{{%orden_compra}}');
        $this->dropForeignKey('fk_orden_compra_proveedor', '{{%orden_compra}}');
        
        // Eliminar índices
        $this->dropIndex('idx_orden_compra_item_inventory', '{{%orden_compra_item}}');
        $this->dropIndex('idx_orden_compra_item_orden', '{{%orden_compra_item}}');
        $this->dropIndex('idx_orden_compra_fecha_emision', '{{%orden_compra}}');
        $this->dropIndex('idx_orden_compra_estado', '{{%orden_compra}}');
        $this->dropIndex('idx_orden_compra_proveedor', '{{%orden_compra}}');
        $this->dropIndex('idx_orden_compra_numero', '{{%orden_compra}}');
        
        // Eliminar tablas
        $this->dropTable('{{%orden_compra_item}}');
        $this->dropTable('{{%orden_compra}}');
    }
}
