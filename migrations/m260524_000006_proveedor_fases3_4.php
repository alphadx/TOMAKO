<?php

use yii\db\Migration;

/**
 * Migración para Fase 3 y 4 de HU-015
 * 
 * Crea:
 * - Tabla proveedor_producto: Relación productos por proveedor
 * - Tabla evaluacion_proveedor: Evaluaciones de desempeño de proveedores
 */
class m260524_000006_proveedor_fases3_4 extends Migration
{
    public function safeUp()
    {
        // ── Tabla proveedor_producto (Fase 3) ────────────────────────────────────
        // Relaciona proveedores con sus productos/repuestos ofrecidos
        $this->createTable('{{%proveedor_producto}}', [
            'id' => $this->primaryKey()->unsigned(),
            'proveedor_id' => $this->integer()->unsigned()->notNull()->comment('Proveedor que ofrece el producto'),
            'inventory_item_id' => $this->integer()->unsigned()->notNull()->comment('Repuesto/inventario asociado'),
            'codigo_proveedor' => $this->string(50)->comment('Código del producto en el catálogo del proveedor'),
            'precio_compra' => $this->decimal(10, 2)->comment('Precio de compra unitario'),
            'tiempo_entrega_dias' => $this->integer()->comment('Tiempo de entrega en días para este producto'),
            'stock_minimo_sugerido' => $this->integer()->defaultValue(0)->comment('Stock mínimo sugerido'),
            'activo' => $this->boolean()->defaultValue(true)->comment('Indica si el producto está activo'),
            'observaciones' => $this->text()->comment('Observaciones adicionales'),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Fecha de actualización'),
        ], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Productos ofrecidos por proveedores'");

        // Índices para proveedor_producto
        $this->createIndex('idx_proveedor_producto_proveedor', '{{%proveedor_producto}}', 'proveedor_id');
        $this->createIndex('idx_proveedor_producto_inventory', '{{%proveedor_producto}}', 'inventory_item_id');
        $this->createIndex('idx_proveedor_producto_activo', '{{%proveedor_producto}}', 'activo');

        // Foreign keys para proveedor_producto
        $this->addForeignKey('fk_proveedor_producto_proveedor', '{{%proveedor_producto}}', 'proveedor_id', '{{%proveedor}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_proveedor_producto_inventory', '{{%proveedor_producto}}', 'inventory_item_id', '{{%inventory_item}}', 'id', 'CASCADE', 'CASCADE');


        // ── Tabla evaluacion_proveedor (Fase 4) ─────────────────────────────────
        // Registro de evaluaciones de desempeño de proveedores
        $this->createTable('{{%evaluacion_proveedor}}', [
            'id' => $this->primaryKey()->unsigned(),
            'proveedor_id' => $this->integer()->unsigned()->notNull()->comment('Proveedor evaluado'),
            'orden_compra_id' => $this->integer()->unsigned()->null()->comment('Orden de compra asociada (opcional)'),
            'fecha_evaluacion' => $this->date()->notNull()->comment('Fecha de la evaluación'),
            'periodo_mes' => $this->integer()->comment('Mes de evaluación (1-12)'),
            'periodo_anio' => $this->integer()->comment('Año de evaluación'),
            
            // Métricas de desempeño (escala 1-5)
            'puntualidad' => $this->smallInteger()->comment('Puntualidad en entregas (1-5)'),
            'calidad_producto' => $this->smallInteger()->comment('Calidad de productos recibidos (1-5)'),
            'atencion_servicio' => $this->smallInteger()->comment('Atención y servicio post-venta (1-5)'),
            'precio competitividad' => $this->smallInteger()->comment('Competitividad de precios (1-5)'),
            'flexibilidad' => $this->smallInteger()->comment('Flexibilidad en pedidos urgentes (1-5)'),
            
            'puntaje_total' => $this->decimal(4, 2)->comment('Puntaje total calculado (0-25)'),
            'puntaje_promedio' => $this->decimal(3, 2)->comment('Puntaje promedio (0-5)'),
            
            'comentarios' => $this->text()->comment('Comentarios adicionales'),
            'evaluado_por' => $this->integer()->unsigned()->comment('Usuario que realizó la evaluación'),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP')->comment('Fecha de creación'),
            'updated_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')->comment('Fecha de actualización'),
        ], "ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Evaluaciones de desempeño de proveedores'");

        // Índices para evaluacion_proveedor
        $this->createIndex('idx_evaluacion_proveedor_proveedor', '{{%evaluacion_proveedor}}', 'proveedor_id');
        $this->createIndex('idx_evaluacion_proveedor_orden', '{{%evaluacion_proveedor}}', 'orden_compra_id');
        $this->createIndex('idx_evaluacion_proveedor_fecha', '{{%evaluacion_proveedor}}', 'fecha_evaluacion');
        $this->createIndex('idx_evaluacion_proveedor_periodo', '{{%evaluacion_proveedor}}', ['periodo_mes', 'periodo_anio']);

        // Foreign keys para evaluacion_proveedor
        $this->addForeignKey('fk_evaluacion_proveedor_proveedor', '{{%evaluacion_proveedor}}', 'proveedor_id', '{{%proveedor}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_evaluacion_proveedor_orden', '{{%evaluacion_proveedor}}', 'orden_compra_id', '{{%orden_compra}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_evaluacion_proveedor_evaluado_por', '{{%evaluacion_proveedor}}', 'evaluado_por', '{{%usuario}}', 'id', 'SET NULL', 'CASCADE');


        // ── Agregar columna de último puntaje a tabla proveedor ─────────────────
        // Para acceso rápido al puntaje actual
        if (!$this->columnExists('{{%proveedor}}', 'ultimo_puntaje_evaluacion')) {
            $this->addColumn('{{%proveedor}}', 'ultimo_puntaje_evaluacion', 
                $this->decimal(3, 2)->null()->comment('Último puntaje de evaluación (0-5)')->after('calificacion'));
        }
        
        // Índice para filtrar por puntaje
        $this->createIndex('idx_proveedor_ultimo_puntaje', '{{%proveedor}}', 'ultimo_puntaje_evaluacion');
    }

    /**
     * Verifica si una columna existe en una tabla
     * @param string $tableName Nombre de la tabla
     * @param string $columnName Nombre de la columna
     * @return bool true si la columna existe, false en caso contrario
     */
    private function columnExists($tableName, $columnName)
    {
        $tableSchema = $this->db->getTableSchema($tableName, true);
        return $tableSchema !== null && isset($tableSchema->columns[$columnName]);
    }

    public function safeDown()
    {
        // Eliminar columna agregada a proveedor
        if ($this->columnExists('{{%proveedor}}', 'ultimo_puntaje_evaluacion')) {
            $this->dropIndex('idx_proveedor_ultimo_puntaje', '{{%proveedor}}');
            $this->dropColumn('{{%proveedor}}', 'ultimo_puntaje_evaluacion');
        }

        // Eliminar foreign keys de evaluacion_proveedor
        $this->dropForeignKey('fk_evaluacion_proveedor_evaluado_por', '{{%evaluacion_proveedor}}');
        $this->dropForeignKey('fk_evaluacion_proveedor_orden', '{{%evaluacion_proveedor}}');
        $this->dropForeignKey('fk_evaluacion_proveedor_proveedor', '{{%evaluacion_proveedor}}');
        
        // Eliminar índices de evaluacion_proveedor
        $this->dropIndex('idx_evaluacion_proveedor_periodo', '{{%evaluacion_proveedor}}');
        $this->dropIndex('idx_evaluacion_proveedor_fecha', '{{%evaluacion_proveedor}}');
        $this->dropIndex('idx_evaluacion_proveedor_orden', '{{%evaluacion_proveedor}}');
        $this->dropIndex('idx_evaluacion_proveedor_proveedor', '{{%evaluacion_proveedor}}');
        
        // Eliminar tabla evaluacion_proveedor
        $this->dropTable('{{%evaluacion_proveedor}}');

        // Eliminar foreign keys de proveedor_producto
        $this->dropForeignKey('fk_proveedor_producto_inventory', '{{%proveedor_producto}}');
        $this->dropForeignKey('fk_proveedor_producto_proveedor', '{{%proveedor_producto}}');
        
        // Eliminar índices de proveedor_producto
        $this->dropIndex('idx_proveedor_producto_activo', '{{%proveedor_producto}}');
        $this->dropIndex('idx_proveedor_producto_inventory', '{{%proveedor_producto}}');
        $this->dropIndex('idx_proveedor_producto_proveedor', '{{%proveedor_producto}}');
        
        // Eliminar tabla proveedor_producto
        $this->dropTable('{{%proveedor_producto}}');
    }
}
