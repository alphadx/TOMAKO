<?php

declare(strict_types=1);

/**
 * Migración base AuditLog: tabla de auditoría inmutable.
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000003_base_audit extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%audit_log}}', [
            'id'          => $this->bigPrimaryKey()->unsigned(),
            'tabla'       => $this->string(100)->notNull(),
            'registro_id' => $this->integer()->unsigned()->notNull(),
            'accion'      => $this->string(20)->notNull(), // CREATE, UPDATE, DELETE
            'usuario_id'  => $this->integer()->unsigned()->null(),
            'ip'          => $this->string(45)->null(),
            'cambios'     => $this->text()->null(),   // JSON: {campo: [antes, despues]}
            'created_at'  => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_audit_tabla_registro', '{{%audit_log}}', ['tabla', 'registro_id']);
        $this->createIndex('idx_audit_usuario',        '{{%audit_log}}', 'usuario_id');
        $this->createIndex('idx_audit_created_at',     '{{%audit_log}}', 'created_at');
        $this->createIndex('idx_audit_accion',         '{{%audit_log}}', 'accion');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%audit_log}}');
    }
}
