<?php

declare(strict_types=1);

/**
 * Migración base RBAC: tablas rol, permiso y rol_permiso.
 *
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m000000_000001_base_rbac extends Migration
{
    public function safeUp(): void
    {
        // Tabla de roles
        $this->createTable('{{%rol}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'nombre'      => $this->string(60)->notNull()->unique(),
            'descripcion' => $this->string(255)->null(),
            'activo'      => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'  => $this->integer()->unsigned()->null(),
            'updated_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Tabla de permisos
        $this->createTable('{{%permiso}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'nombre'      => $this->string(100)->notNull()->unique(),
            'descripcion' => $this->string(255)->null(),
            'modulo'      => $this->string(60)->null(),
            'created_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Tabla pivote rol-permiso
        $this->createTable('{{%rol_permiso}}', [
            'rol_id'     => $this->integer()->unsigned()->notNull(),
            'permiso_id' => $this->integer()->unsigned()->notNull(),
            'PRIMARY KEY ([[rol_id]], [[permiso_id]])',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addForeignKey(
            'fk_rol_permiso_rol',
            '{{%rol_permiso}}', 'rol_id',
            '{{%rol}}', 'id',
            'CASCADE', 'CASCADE'
        );
        $this->addForeignKey(
            'fk_rol_permiso_permiso',
            '{{%rol_permiso}}', 'permiso_id',
            '{{%permiso}}', 'id',
            'CASCADE', 'CASCADE'
        );

        $this->createIndex('idx_permiso_modulo', '{{%permiso}}', 'modulo');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_rol_permiso_permiso', '{{%rol_permiso}}');
        $this->dropForeignKey('fk_rol_permiso_rol', '{{%rol_permiso}}');
        $this->dropTable('{{%rol_permiso}}');
        $this->dropTable('{{%permiso}}');
        $this->dropTable('{{%rol}}');
    }
}
