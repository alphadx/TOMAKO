<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea las tablas especialidad, tecnico y certificacion.
 * Incluye seed de 3 especialidades iniciales.
 */
class m000000_000011_tecnico extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%especialidad}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'nombre'      => $this->string(100)->notNull()->unique(),
            'descripcion' => $this->text()->null(),
            'status'      => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createTable('{{%tecnico}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'nombre'          => $this->string(100)->notNull(),
            'apellido'        => $this->string(100)->notNull(),
            'rut'             => $this->string(15)->null(),
            'email'           => $this->string(150)->null()->unique(),
            'telefono'        => $this->string(25)->null(),
            'especialidad_id' => $this->integer()->unsigned()->null(),
            'costo_hora'      => $this->decimal(10, 2)->null()->defaultValue(0.00),
            'foto_path'       => $this->string(255)->null(),
            'status'          => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'      => $this->integer()->unsigned()->null(),
            'updated_at'      => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_tecnico_especialidad', '{{%tecnico}}', 'especialidad_id');
        $this->createIndex('idx_tecnico_status',       '{{%tecnico}}', 'status');

        $this->addForeignKey(
            'fk_tecnico_especialidad',
            '{{%tecnico}}',      'especialidad_id',
            '{{%especialidad}}', 'id',
            'SET NULL'
        );

        $this->createTable('{{%certificacion}}', [
            'id'               => $this->primaryKey()->unsigned(),
            'tecnico_id'       => $this->integer()->unsigned()->notNull(),
            'titulo'           => $this->string(150)->notNull(),
            'entidad_emisora'  => $this->string(100)->null(),
            'fecha_emision'    => $this->date()->null(),
            'fecha_vencimiento'=> $this->date()->null(),
            'created_at'       => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_cert_tecnico', '{{%certificacion}}', 'tecnico_id');

        $this->addForeignKey(
            'fk_cert_tecnico',
            '{{%certificacion}}', 'tecnico_id',
            '{{%tecnico}}',       'id',
            'CASCADE'
        );

        // Seed: 3 especialidades iniciales
        $now = time();
        $this->batchInsert('{{%especialidad}}', ['nombre', 'descripcion', 'status', 'created_at'], [
            ['Mecánica General',           'Diagnóstico y reparación mecánica general de vehículos.',         1, $now],
            ['Electricidad Automotriz',    'Diagnóstico y reparación de sistemas eléctricos y electrónicos.', 1, $now],
            ['Pintura y Carrocería',       'Trabajos de pintura, enderezado y reparación de carrocería.',     1, $now],
        ]);
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_cert_tecnico',         '{{%certificacion}}');
        $this->dropTable('{{%certificacion}}');

        $this->dropForeignKey('fk_tecnico_especialidad', '{{%tecnico}}');
        $this->dropTable('{{%tecnico}}');
        $this->dropTable('{{%especialidad}}');
    }
}
