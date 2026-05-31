<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Adds checklist_item table for pre-delivery verification
 * Table was missing from m000000_000013; added as follow-up
 */
class m000000_000017_add_checklist_item extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%checklist_item}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'orden_id'   => $this->integer()->unsigned()->notNull(),
            'item'       => $this->string(255)->notNull(),
            'completado' => $this->boolean()->notNull()->defaultValue(false),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_ci_orden', '{{%checklist_item}}', 'orden_id');
        $this->createIndex('idx_ci_completado', '{{%checklist_item}}', 'completado');

        $this->addForeignKey(
            'fk_ci_orden',
            '{{%checklist_item}}',
            'orden_id',
            '{{%orden_servicio}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_ci_orden', '{{%checklist_item}}');
        $this->dropTable('{{%checklist_item}}');
    }
}
