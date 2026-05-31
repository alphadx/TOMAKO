<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: tabla login_attempt para rastreo de intentos de acceso.
 */
class m000000_000004_usuario_login_attempts extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%login_attempt}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'ip'         => $this->string(45)->notNull(),
            'email'      => $this->string(150)->null(),
            'exitoso'    => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_ip_created', '{{%login_attempt}}', ['ip', 'created_at']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%login_attempt}}');
    }
}
