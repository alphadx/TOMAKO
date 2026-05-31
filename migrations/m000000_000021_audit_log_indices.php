<?php

declare(strict_types=1);

/**
 * Migración: Crear índices para la tabla audit_log (performance optimization).
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000021_audit_log_indices extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%audit_log}}', true);
        if ($schema === null) {
            return;
        }

        // Índice compuesto: (entidad, registro_id, created_at) - para búsqueda por entidad
        if ($this->hasColumns($schema, ['entidad', 'registro_id', 'created_at']) && !$this->indexExists('audit_log', 'idx_audit_log_entidad_registro_fecha')) {
            $this->createIndex(
                'idx_audit_log_entidad_registro_fecha',
                '{{%audit_log}}',
                ['entidad', 'registro_id', 'created_at']
            );
        }

        // Índice compuesto: (usuario_id, created_at) - para búsqueda de actividades por usuario
        if ($this->hasColumns($schema, ['usuario_id', 'created_at']) && !$this->indexExists('audit_log', 'idx_audit_log_usuario_fecha')) {
            $this->createIndex(
                'idx_audit_log_usuario_fecha',
                '{{%audit_log}}',
                ['usuario_id', 'created_at']
            );
        }

        // Índice simple: accion - para filtrar por tipo de acción
        if ($this->hasColumns($schema, ['accion']) && !$this->indexExists('audit_log', 'idx_audit_log_accion')) {
            $this->createIndex(
                'idx_audit_log_accion',
                '{{%audit_log}}',
                'accion'
            );
        }

        // Índice simple: modulo - para filtrar por módulo
        if ($this->hasColumns($schema, ['modulo']) && !$this->indexExists('audit_log', 'idx_audit_log_modulo')) {
            $this->createIndex(
                'idx_audit_log_modulo',
                '{{%audit_log}}',
                'modulo'
            );
        }

        // Índice simple: created_at DESC - para ordenamiento cronológico
        if ($this->hasColumns($schema, ['created_at']) && !$this->indexExists('audit_log', 'idx_audit_log_created_at')) {
            $this->createIndex(
                'idx_audit_log_created_at',
                '{{%audit_log}}',
                'created_at'
            );
        }
    }

    public function safeDown(): void
    {
        // No destructivo.
    }

    private function hasColumns(\yii\db\TableSchema $schema, array $columns): bool
    {
        foreach ($columns as $column) {
            if (!isset($schema->columns[$column])) {
                return false;
            }
        }

        return true;
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
