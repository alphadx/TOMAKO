<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea las tablas inventory_item e inventory_movement.
 */
class m000000_000010_inventario extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%inventory_item}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'sku'             => $this->string(20)->notNull()->unique(),
            'nombre'          => $this->string(150)->notNull(),
            'descripcion'     => $this->text()->null(),
            'categoria_id'    => $this->integer()->unsigned()->notNull(),
            'precio_unitario' => $this->decimal(10, 2)->notNull()->defaultValue(0.00),
            'cantidad'        => $this->integer()->notNull()->defaultValue(0),
            'stock_minimo'    => $this->integer()->notNull()->defaultValue(0),
            'stock_maximo'    => $this->integer()->null(),
            'unidad'          => $this->string(20)->null()->defaultValue('unidad'),
            'ubicacion'       => $this->string(100)->null(),
            'foto_path'       => $this->string(255)->null(),
            'status'          => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'      => $this->integer()->unsigned()->null(),
            'updated_at'      => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_invitem_categoria', '{{%inventory_item}}', 'categoria_id');
        $this->createIndex('idx_invitem_sku',       '{{%inventory_item}}', 'sku');
        $this->createIndex('idx_invitem_status',    '{{%inventory_item}}', 'status');

        $this->addForeignKey(
            'fk_invitem_categoria',
            '{{%inventory_item}}', 'categoria_id',
            '{{%categoria}}',      'id',
            'RESTRICT'
        );

        $this->createTable('{{%inventory_movement}}', [
            'id'                => $this->primaryKey()->unsigned(),
            'item_id'           => $this->integer()->unsigned()->notNull(),
            'tipo'              => "ENUM('entrada','salida','ajuste') NOT NULL",
            'cantidad_delta'    => $this->integer()->notNull(),
            'cantidad_anterior' => $this->integer()->notNull(),
            'cantidad_nueva'    => $this->integer()->notNull(),
            'usuario_id'        => $this->integer()->unsigned()->null(),
            'referencia'        => $this->string(255)->null()->comment('orden_id o motivo del ajuste'),
            'created_at'        => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_invmov_item',       '{{%inventory_movement}}', 'item_id');
        $this->createIndex('idx_invmov_tipo',       '{{%inventory_movement}}', 'tipo');
        $this->createIndex('idx_invmov_created_at', '{{%inventory_movement}}', 'created_at');

        $this->addForeignKey(
            'fk_invmov_item',
            '{{%inventory_movement}}', 'item_id',
            '{{%inventory_item}}',     'id',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_invmov_usuario',
            '{{%inventory_movement}}', 'usuario_id',
            '{{%usuario}}',            'id',
            'SET NULL'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_invmov_usuario',  '{{%inventory_movement}}');
        $this->dropForeignKey('fk_invmov_item',     '{{%inventory_movement}}');
        $this->dropTable('{{%inventory_movement}}');

        $this->dropForeignKey('fk_invitem_categoria', '{{%inventory_item}}');
        $this->dropTable('{{%inventory_item}}');
    }
}
