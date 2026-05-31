<?php

declare(strict_types=1);

/**
 * Migración base Auth: tablas usuario, sesion, parametro_sistema e idioma.
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000002_base_auth extends Migration
{
    public function safeUp(): void
    {
        // Tabla de idiomas
        $this->createTable('{{%idioma}}', [
            'id'        => $this->primaryKey()->unsigned(),
            'codigo'    => $this->string(10)->notNull()->unique(),
            'nombre'    => $this->string(60)->notNull(),
            'activo'    => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'es_defecto'=> $this->tinyInteger(1)->notNull()->defaultValue(0),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Tabla de parámetros del sistema (clave-valor)
        $this->createTable('{{%parametro_sistema}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'clave'       => $this->string(100)->notNull()->unique(),
            'valor'       => $this->text()->null(),
            'tipo'        => $this->string(20)->notNull()->defaultValue('string'),
            'descripcion' => $this->string(255)->null(),
            'editable'    => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'updated_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Tabla de usuarios
        $this->createTable('{{%usuario}}', [
            'id'             => $this->primaryKey()->unsigned(),
            'username'       => $this->string(60)->notNull()->unique(),
            'email'          => $this->string(150)->notNull()->unique(),
            'password_hash'  => $this->string(255)->notNull(),
            'auth_key'       => $this->string(32)->null(),
            'password_reset_token' => $this->string(255)->null()->unique(),
            'rol_id'         => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'nombre'         => $this->string(100)->null(),
            'apellido'       => $this->string(100)->null(),
            'activo'         => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'ultimo_login'   => $this->integer()->unsigned()->null(),
            'created_at'     => $this->integer()->unsigned()->null(),
            'updated_at'     => $this->integer()->unsigned()->null(),
            'deleted_at'     => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey(
            'fk_usuario_rol',
            '{{%usuario}}', 'rol_id',
            '{{%rol}}', 'id',
            'RESTRICT', 'CASCADE'
        );

        // Tabla de sesiones activas
        $this->createTable('{{%sesion}}', [
            'id'         => $this->bigPrimaryKey()->unsigned(),
            'usuario_id' => $this->integer()->unsigned()->notNull(),
            'token'      => $this->string(128)->notNull()->unique(),
            'ip'         => $this->string(45)->null(),
            'user_agent' => $this->string(255)->null(),
            'activa'     => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'expires_at' => $this->integer()->unsigned()->null(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey(
            'fk_sesion_usuario',
            '{{%sesion}}', 'usuario_id',
            '{{%usuario}}', 'id',
            'CASCADE', 'CASCADE'
        );

        $this->createIndex('idx_sesion_usuario_activa', '{{%sesion}}', ['usuario_id', 'activa']);
        $this->createIndex('idx_usuario_email', '{{%usuario}}', 'email');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_sesion_usuario', '{{%sesion}}');
        $this->dropForeignKey('fk_usuario_rol', '{{%usuario}}');
        $this->dropTable('{{%sesion}}');
        $this->dropTable('{{%usuario}}');
        $this->dropTable('{{%parametro_sistema}}');
        $this->dropTable('{{%idioma}}');
    }
}
