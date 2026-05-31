<?php

declare(strict_types=1);

/**
 * Migración: Crear/actualizar tabla audit_log para registro inmutable de cambios.
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000019_create_audit_log extends Migration
{
    public function safeUp(): void
    {
        $tableSchema = $this->db->getTableSchema('{{%audit_log}}', true);

        if ($tableSchema === null) {
            $this->createTable('{{%audit_log}}', [
                'id' => $this->bigPrimaryKey()->unsigned(),
                'usuario_id' => $this->integer()->unsigned()->null()->comment('FK a usuario (nullable para logs del sistema)'),
                'accion' => $this->string(20)->notNull()->comment('Tipo de acción (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, EXPORT, ROLLBACK)'),
                'modulo' => $this->string(60)->notNull()->comment('Módulo afectado (ej: Clientes, Servicios)'),
                'entidad' => $this->string(100)->notNull()->comment('Nombre de la entidad/tabla (ej: Cliente, Servicio)'),
                'registro_id' => $this->bigInteger()->unsigned()->null()->comment('ID del registro afectado'),
                'datos_previos' => $this->json()->null()->comment('Estado anterior en JSON'),
                'datos_nuevos' => $this->json()->null()->comment('Estado nuevo en JSON'),
                'ip_address' => $this->string(45)->null()->comment('IP del usuario (soporta IPv6)'),
                'duracion_ms' => $this->integer()->unsigned()->defaultValue(0)->comment('Duración de operación en ms'),
                'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Timestamp de creación'),
            ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Registro inmutable de auditoría"');

            $this->addForeignKey(
                'fk_audit_log_usuario',
                '{{%audit_log}}',
                'usuario_id',
                '{{%usuario}}',
                'id',
                'SET NULL',
                'CASCADE'
            );

            return;
        }

        // Upgrade de esquema legacy cuando la tabla ya existe.
        if (!$this->columnExists('audit_log', 'modulo')) {
            $this->addColumn('{{%audit_log}}', 'modulo', $this->string(60)->null()->comment('Módulo afectado (ej: Clientes, Servicios)'));
        }
        if (!$this->columnExists('audit_log', 'entidad')) {
            $this->addColumn('{{%audit_log}}', 'entidad', $this->string(100)->null()->comment('Nombre de la entidad/tabla (ej: Cliente, Servicio)'));
        }
        if (!$this->columnExists('audit_log', 'datos_previos')) {
            $this->addColumn('{{%audit_log}}', 'datos_previos', $this->json()->null()->comment('Estado anterior en JSON'));
        }
        if (!$this->columnExists('audit_log', 'datos_nuevos')) {
            $this->addColumn('{{%audit_log}}', 'datos_nuevos', $this->json()->null()->comment('Estado nuevo en JSON'));
        }
        if (!$this->columnExists('audit_log', 'ip_address')) {
            $this->addColumn('{{%audit_log}}', 'ip_address', $this->string(45)->null()->comment('IP del usuario (soporta IPv6)'));
        }
        if (!$this->columnExists('audit_log', 'duracion_ms')) {
            $this->addColumn('{{%audit_log}}', 'duracion_ms', $this->integer()->unsigned()->defaultValue(0)->comment('Duración de operación en ms'));
        }

        if ($this->columnExists('audit_log', 'tabla')) {
            $this->execute("UPDATE {{%audit_log}} SET entidad = COALESCE(NULLIF(entidad, ''), tabla)");
        }
        if ($this->columnExists('audit_log', 'ip')) {
            $this->execute("UPDATE {{%audit_log}} SET ip_address = COALESCE(NULLIF(ip_address, ''), ip)");
        }
        if ($this->columnExists('audit_log', 'cambios')) {
            $this->execute('UPDATE {{%audit_log}} SET datos_nuevos = COALESCE(datos_nuevos, cambios)');
        }

        $tableSchema = $this->db->getTableSchema('{{%audit_log}}', true);
        $createdAtType = strtolower((string) ($tableSchema->columns['created_at']->dbType ?? ''));

        // Normalizar created_at legacy (int UNIX timestamp) a datetime.
        if (str_contains($createdAtType, 'int') && !$this->columnExists('audit_log', 'created_at_tmp')) {
            $this->addColumn('{{%audit_log}}', 'created_at_tmp', $this->dateTime()->null()->comment('Migración temporal de created_at'));
            $this->execute('UPDATE {{%audit_log}} SET created_at_tmp = FROM_UNIXTIME(created_at) WHERE created_at IS NOT NULL');
            $this->dropColumn('{{%audit_log}}', 'created_at');
            $this->renameColumn('{{%audit_log}}', 'created_at_tmp', 'created_at');
        }

        $this->alterColumn('{{%audit_log}}', 'created_at', $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP')->comment('Timestamp de creación'));
        $this->alterColumn('{{%audit_log}}', 'accion', $this->string(20)->notNull()->comment('Tipo de acción (CREATE, UPDATE, DELETE, LOGIN, LOGOUT, EXPORT, ROLLBACK)'));
        $this->alterColumn('{{%audit_log}}', 'modulo', $this->string(60)->notNull()->defaultValue('legacy')->comment('Módulo afectado (ej: Clientes, Servicios)'));
        $this->alterColumn('{{%audit_log}}', 'entidad', $this->string(100)->notNull()->defaultValue('LegacyAudit')->comment('Nombre de la entidad/tabla (ej: Cliente, Servicio)'));

        if ($this->columnExists('audit_log', 'registro_id')) {
            $this->alterColumn('{{%audit_log}}', 'registro_id', $this->bigInteger()->unsigned()->null()->comment('ID del registro afectado'));
        }

        if (!$this->foreignKeyExists('fk_audit_log_usuario')) {
            $this->addForeignKey(
                'fk_audit_log_usuario',
                '{{%audit_log}}',
                'usuario_id',
                '{{%usuario}}',
                'id',
                'SET NULL',
                'CASCADE'
            );
        }
    }

    public function safeDown(): void
    {
        // Esta migración puede haber transformado un esquema existente.
        // No se aplica rollback destructivo para evitar pérdida de auditoría.
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $schema = $this->db->getTableSchema('{{%' . $tableName . '}}', true);
        return $schema !== null && isset($schema->columns[$columnName]);
    }

    private function foreignKeyExists(string $fkName): bool
    {
        $result = $this->db->createCommand(
            'SELECT COUNT(1) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = :fk AND CONSTRAINT_TYPE = "FOREIGN KEY"',
            [':fk' => $fkName]
        )->queryScalar();

        return (int) $result > 0;
    }
}
