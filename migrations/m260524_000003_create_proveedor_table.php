<?php

use yii\db\Migration;

/**
 * Clase migración para crear la tabla de proveedores
 * Implementación HU-015 Fase 1: Registro básico de proveedores
 */
class m260524_000003_create_proveedor_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%proveedor}}', [
            'id' => $this->primaryKey()->unsigned(),
            'nombre' => $this->string(150)->notNull()->comment('Nombre o razón social del proveedor'),
            'rut' => $this->string(20)->unique()->comment('RUT/NIF del proveedor'),
            'email' => $this->string(100)->unique()->comment('Correo electrónico de contacto'),
            'telefono' => $this->string(20)->comment('Teléfono de contacto'),
            'celular' => $this->string(20)->comment('Celular de contacto'),
            'direccion' => $this->string(200)->comment('Dirección fiscal/comercial'),
            'ciudad' => $this->string(100)->comment('Ciudad'),
            'region' => $this->string(100)->comment('Región/Estado'),
            'pais' => $this->string(50)->defaultValue('Chile')->comment('País'),
            'codigo_postal' => $this->string(20)->comment('Código postal'),
            'sitio_web' => $this->string(100)->comment('Sitio web'),
            'persona_contacto' => $this->string(100)->comment('Nombre de persona de contacto'),
            'cargo_contacto' => $this->string(100)->comment('Cargo de la persona de contacto'),
            'categoria' => $this->string(50)->comment('Categoría del proveedor (Repuestos, Herramientas, Servicios, etc.)'),
            'tiempo_entrega_promedio' => $this->integer()->comment('Tiempo promedio de entrega en días'),
            'calificacion' => $this->decimal(3, 2)->defaultValue(0.00)->comment('Calificación promedio (0-5)'),
            'activo' => $this->boolean()->defaultValue(true)->comment('Indica si el proveedor está activo'),
            'observaciones' => $this->text()->comment('Observaciones adicionales'),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Fecha de actualización'),
            'created_by' => $this->integer()->unsigned()->comment('Usuario que creó el registro'),
            'updated_by' => $this->integer()->unsigned()->comment('Usuario que actualizó el registro'),
        ], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Proveedores de repuestos y servicios'");

        // Índices para mejorar rendimiento en búsquedas
        $this->createIndex('idx_proveedor_nombre', '{{%proveedor}}', 'nombre');
        $this->createIndex('idx_proveedor_categoria', '{{%proveedor}}', 'categoria');
        $this->createIndex('idx_proveedor_activo', '{{%proveedor}}', 'activo');
        $this->createIndex('idx_proveedor_calificacion', '{{%proveedor}}', 'calificacion');
        
        // Foreign key para usuario creador/actualizador (referencia a tabla usuario)
        $this->addForeignKey('fk_proveedor_created_by', '{{%proveedor}}', 'created_by', '{{%usuario}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_proveedor_updated_by', '{{%proveedor}}', 'updated_by', '{{%usuario}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_proveedor_updated_by', '{{%proveedor}}');
        $this->dropForeignKey('fk_proveedor_created_by', '{{%proveedor}}');
        $this->dropIndex('idx_proveedor_calificacion', '{{%proveedor}}');
        $this->dropIndex('idx_proveedor_activo', '{{%proveedor}}');
        $this->dropIndex('idx_proveedor_categoria', '{{%proveedor}}');
        $this->dropIndex('idx_proveedor_nombre', '{{%proveedor}}');
        $this->dropTable('{{%proveedor}}');
    }
}
