<?php

use yii\db\Migration;

/**
 * Creates checklist_item table
 * Pre-delivery checklist items for work orders
 */
class m000023_create_checklist_item extends Migration
{
    public function safeUp()
    {
        $this->createTable('checklist_item', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'item' => $this->string(255)->notNull()->comment('Descripción del item de verificación'),
            'completado' => $this->boolean()->defaultValue(false)->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Foreign key
        $this->addForeignKey(
            'fk_checklist_orden_id',
            'checklist_item',
            'orden_id',
            'orden_servicio',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Indices
        $this->createIndex('idx_checklist_orden_id', 'checklist_item', 'orden_id');
        $this->createIndex('idx_checklist_completado', 'checklist_item', 'completado');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_checklist_orden_id', 'checklist_item');
        $this->dropTable('checklist_item');
    }
}
