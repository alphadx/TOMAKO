<?php

declare(strict_types=1);

use yii\db\Migration;
use yii\db\Schema;

/**
 * Migración para crear tabla de preferencias de dashboard por usuario.
 * Permite personalizar qué widgets mostrar y su orden.
 */
class m260511_000001_create_dashboard_preferences extends Migration
{
    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->getDriverName() === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        if (!$this->db->getTableSchema('{{%dashboard_preference}}', true)) {
            $this->createTable('{{%dashboard_preference}}', [
                'id' => Schema::TYPE_BIGPK,
                'user_id' => Schema::TYPE_INTEGER . ' UNSIGNED NOT NULL',
                'widget_id' => Schema::TYPE_STRING . '(100) NOT NULL',
                'is_visible' => Schema::TYPE_SMALLINT . ' DEFAULT 1',
                'sort_order' => Schema::TYPE_INTEGER . ' DEFAULT 0',
                'is_collapsed' => Schema::TYPE_SMALLINT . ' DEFAULT 0',
                'created_at' => Schema::TYPE_INTEGER,
                'updated_at' => Schema::TYPE_INTEGER,
            ], $tableOptions);

            $this->createIndex('idx_dashboard_preference_user', '{{%dashboard_preference}}', ['user_id']);
            $this->createIndex('idx_dashboard_preference_widget', '{{%dashboard_preference}}', ['widget_id']);
            $this->createIndex('idx_dashboard_preference_unique', '{{%dashboard_preference}}', ['user_id', 'widget_id'], true);

            $this->addForeignKey('fk_dashboard_preference_user', '{{%dashboard_preference}}', 'user_id', '{{%usuario}}', 'id', 'CASCADE', 'CASCADE');
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_dashboard_preference_user', '{{%dashboard_preference}}');
        $this->dropTable('{{%dashboard_preference}}');
    }
}
