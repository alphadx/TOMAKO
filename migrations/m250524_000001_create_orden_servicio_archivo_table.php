<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates the orden_servicio_archivo table for attaching photos and documents to work orders.
 * 
 * This migration supports HU-004: Adjuntar Fotos y Documentos a Órdenes
 */
class m250524_000001_create_orden_servicio_archivo_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%orden_servicio_archivo}}', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_servicio_id' => $this->integer()->unsigned()->notNull(),
            'tipo' => $this->string(20)->notNull()->comment('foto o documento'),
            'ruta_archivo' => $this->string(500)->notNull(),
            'ruta_thumbnail' => $this->string(500)->null()->comment('Ruta del thumbnail para fotos'),
            'nombre_original' => $this->string(255)->notNull(),
            'mime_type' => $this->string(100)->notNull(),
            'tamaño_bytes' => $this->bigInteger()->unsigned()->notNull(),
            'descripcion' => $this->string(500)->null(),
            'uploaded_by' => $this->integer()->unsigned()->null(),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Índices
        $this->createIndex('idx_osa_orden_servicio', '{{%orden_servicio_archivo}}', 'orden_servicio_id');
        $this->createIndex('idx_osa_tipo', '{{%orden_servicio_archivo}}', 'tipo');
        $this->createIndex('idx_osa_uploaded_by', '{{%orden_servicio_archivo}}', 'uploaded_by');
        $this->createIndex('idx_osa_created_at', '{{%orden_servicio_archivo}}', 'created_at');

        // Foreign keys
        $this->addForeignKey(
            'fk_osa_orden_servicio',
            '{{%orden_servicio_archivo}}',
            'orden_servicio_id',
            '{{%orden_servicio}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_osa_uploaded_by',
            '{{%orden_servicio_archivo}}',
            'uploaded_by',
            '{{%usuario}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_osa_uploaded_by', '{{%orden_servicio_archivo}}');
        $this->dropForeignKey('fk_osa_orden_servicio', '{{%orden_servicio_archivo}}');
        $this->dropTable('{{%orden_servicio_archivo}}');
    }
}
