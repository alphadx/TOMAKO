<?php

declare(strict_types=1);

/**
 * Migración: Crear tabla archive_log para almacenamiento de logs históricos.
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000020_create_archive_log extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%archive_log}}', true) === null) {
            $this->createTable('{{%archive_log}}', [
                'id' => $this->bigPrimaryKey()->unsigned(),
                'audit_log_id' => $this->bigInteger()->unsigned()->notNull()->comment('FK a audit_log'),
                'usuario_id' => $this->integer()->unsigned()->null()->comment('Usuario asociado (desnormalizado para búsqueda)'),
                'accion' => $this->string(20)->notNull()->comment('Tipo de acción (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, EXPORT, ROLLBACK)'),
                'modulo' => $this->string(60)->notNull(),
                'entidad' => $this->string(100)->notNull(),
                'registro_id' => $this->bigInteger()->unsigned()->null(),
                'datos_previos' => $this->json()->null(),
                'datos_nuevos' => $this->json()->null(),
                'ip_address' => $this->string(45)->null(),
                'duracion_ms' => $this->integer()->unsigned()->defaultValue(0),
                'original_created_at' => $this->dateTime()->notNull()->comment('Fecha original en audit_log'),
                'archivado_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de archivado'),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Archivo de logs históricos de auditoría"');
        }

        // Foreign key a audit_log
        if (!$this->foreignKeyExists('fk_archive_log_audit_log')) {
            $this->addForeignKey(
                'fk_archive_log_audit_log',
                '{{%archive_log}}', 'audit_log_id',
                '{{%audit_log}}', 'id',
                'CASCADE', 'CASCADE'
            );
        }

        // Índice sobre archivado_at para búsqueda de logs históricos
        if (!$this->indexExists('archive_log', 'idx_archive_log_archivado_at')) {
            $this->createIndex(
                'idx_archive_log_archivado_at',
                '{{%archive_log}}',
                'archivado_at'
            );
        }
    }

    public function safeDown(): void
    {
        // No destructivo por protección de histórico.
    }

    private function foreignKeyExists(string $fkName): bool
    {
        $result = $this->db->createCommand(
            'SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = :fk AND CONSTRAINT_TYPE = "FOREIGN KEY"',
            [':fk' => $fkName]
        )->queryScalar();

        return (int) $result > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = $this->db->createCommand(
            'SELECT COUNT(1) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index',
            [':table' => $tableName, ':index' => $indexName]
        )->queryScalar();

        return (int) $result > 0;
    }
}
