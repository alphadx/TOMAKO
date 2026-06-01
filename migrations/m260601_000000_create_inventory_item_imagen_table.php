<?php

use yii\db\Migration;

class m260601_000000_create_inventory_item_imagen_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%inventory_item_imagen}}', [
            'id'          => $this->primaryKey(),
            'item_id'     => $this->bigInteger()->notNull(),
            'filename'    => $this->string(255)->notNull(),
            'filepath'    => $this->string(500)->notNull(),
            'is_default'  => $this->boolean()->notNull()->defaultValue(0),
            'is_active'   => $this->boolean()->notNull()->defaultValue(1),
            'created_at'  => $this->integer(),
            'updated_at'  => $this->integer(),
        ]);

        $this->createIndex('idx-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}', 'item_id');
        $this->addForeignKey(
            'fk-inventory_item_imagen-item_id',
            '{{%inventory_item_imagen}}',
            'item_id',
            '{{%inventory_item}}',
            'id',
            'CASCADE'
        );

        $this->addColumn('{{%inventory_item}}', 'qr_code', $this->string(100)->null());
        $this->createIndex('idx-inventory_item-qr_code', '{{%inventory_item}}', 'qr_code', true);
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}');
        $this->dropIndex('idx-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}');
        $this->dropTable('{{%inventory_item_imagen}}');

        $this->dropIndex('idx-inventory_item-qr_code', '{{%inventory_item}}');
        $this->dropColumn('{{%inventory_item}}', 'qr_code');
    }
}