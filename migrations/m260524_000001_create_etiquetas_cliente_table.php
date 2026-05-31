<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: tablas para sistema de etiquetas de clientes (HU-006).
 * Crea las tablas {{%etiqueta}} y {{%cliente_etiqueta}}.
 */
class m260524_000001_create_etiquetas_cliente_table extends Migration
{
    public function safeUp(): void
    {
        // Tabla etiqueta
        $this->createTable('{{%etiqueta}}', [
            'id'          => $this->integer()->unsigned()->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'nombre'      => $this->string(50)->notNull(),
            'color'       => $this->string(20)->notNull(),
            'descripcion' => $this->text()->null(),
            'status'      => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'  => $this->integer()->unsigned()->null(),
            'updated_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_etiqueta_nombre', '{{%etiqueta}}', 'nombre', true);
        $this->createIndex('idx_etiqueta_status', '{{%etiqueta}}', 'status');

        // Tabla cliente_etiqueta (relación muchos-a-muchos)
        $this->createTable('{{%cliente_etiqueta}}', [
            'id'          => $this->integer()->unsigned()->notNull()->append('AUTO_INCREMENT PRIMARY KEY'),
            'cliente_id'  => $this->integer()->unsigned()->notNull(),
            'etiqueta_id' => $this->integer()->unsigned()->notNull(),
            'notas'       => $this->text()->null(),
            'created_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_cliente_etiqueta', '{{%cliente_etiqueta}}', ['cliente_id', 'etiqueta_id'], true);
        $this->createIndex('idx_cliente_etiqueta_cliente', '{{%cliente_etiqueta}}', 'cliente_id');
        $this->createIndex('idx_cliente_etiqueta_etiqueta', '{{%cliente_etiqueta}}', 'etiqueta_id');

        // Llaves foráneas
        $this->addForeignKey(
            'fk_cliente_etiqueta_cliente',
            '{{%cliente_etiqueta}}',
            'cliente_id',
            '{{%cliente}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_cliente_etiqueta_etiqueta',
            '{{%cliente_etiqueta}}',
            'etiqueta_id',
            '{{%etiqueta}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Agregar campos adicionales a la tabla cliente
        $this->addColumn('{{%cliente}}', 'cumpleanos', $this->date()->null()->after('rut'));
        $this->addColumn('{{%cliente}}', 'fuente', $this->string(50)->null()->after('cumpleanos'));
        $this->addColumn('{{%cliente}}', 'preferencias', $this->text()->null()->after('fuente'));

        // Seed inicial de etiquetas comunes
        $now = time();
        $this->batchInsert('{{%etiqueta}}', ['nombre', 'color', 'descripcion', 'created_at', 'updated_at'], [
            ['VIP', 'danger', 'Clientes de alto valor', $now, $now],
            ['Frecuente', 'warning', 'Clientes con visitas frecuentes', $now, $now],
            ['Nuevo', 'info', 'Cliente nuevo (primer servicio)', $now, $now],
            ['Flota', 'primary', 'Cliente corporativo / flota', $now, $now],
            ['Garantía', 'success', 'En período de garantía', $now, $now],
            ['Moroso', 'dark', 'Con pagos pendientes', $now, $now],
        ]);
    }

    public function safeDown(): void
    {
        // Eliminar campos adicionales de cliente
        $this->dropColumn('{{%cliente}}', 'preferencias');
        $this->dropColumn('{{%cliente}}', 'fuente');
        $this->dropColumn('{{%cliente}}', 'cumpleanos');

        // Eliminar llaves foráneas
        $this->dropForeignKey('fk_cliente_etiqueta_etiqueta', '{{%cliente_etiqueta}}');
        $this->dropForeignKey('fk_cliente_etiqueta_cliente', '{{%cliente_etiqueta}}');

        // Eliminar índices
        $this->dropIndex('idx_cliente_etiqueta_etiqueta', '{{%cliente_etiqueta}}');
        $this->dropIndex('idx_cliente_etiqueta_cliente', '{{%cliente_etiqueta}}');
        $this->dropIndex('uk_cliente_etiqueta', '{{%cliente_etiqueta}}');

        $this->dropIndex('idx_etiqueta_status', '{{%etiqueta}}');
        $this->dropIndex('uk_etiqueta_nombre', '{{%etiqueta}}');

        // Eliminar tablas
        $this->dropTable('{{%cliente_etiqueta}}');
        $this->dropTable('{{%etiqueta}}');
    }
}
