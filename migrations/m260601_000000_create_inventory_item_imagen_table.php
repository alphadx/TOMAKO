<?php

use yii\db\Migration;

class m260601_000000_create_inventory_item_imagen_table extends Migration
{
    public function safeUp()
    {
        $tableExists = $this->db->getTableSchema('{{%inventory_item_imagen}}') !== null;

        if (!$tableExists) {
            $this->createTable('{{%inventory_item_imagen}}', [
                'id'          => $this->primaryKey(),
                'item_id'     => $this->integer()->unsigned()->notNull(),
                'filename'    => $this->string(255)->notNull(),
                'filepath'    => $this->string(500)->notNull(),
                'is_default'  => $this->boolean()->notNull()->defaultValue(0),
                'is_active'   => $this->boolean()->notNull()->defaultValue(1),
                'created_at'  => $this->integer(),
                'updated_at'  => $this->integer(),
            ]);
        }

        // Index on item_id
        $idxExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'inventory_item_imagen' AND index_name = 'idx-inventory_item_imagen-item_id' LIMIT 1"
        )->queryOne() !== false;
        if (!$idxExists) {
            $this->createIndex('idx-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}', 'item_id');
        }

        // Ensure item_id column type matches inventory_item.id (int unsigned)
        $colType = $this->db->createCommand(
            "SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'inventory_item_imagen' AND column_name = 'item_id'"
        )->queryScalar();
        if ($colType && stripos($colType, 'int unsigned') === false) {
            $this->alterColumn('{{%inventory_item_imagen}}', 'item_id', $this->integer()->unsigned()->notNull());
        }

        // Foreign key
        $fkExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'inventory_item_imagen' AND constraint_name = 'fk-inventory_item_imagen-item_id' AND constraint_type = 'FOREIGN KEY' LIMIT 1"
        )->queryOne() !== false;
        if (!$fkExists) {
            $this->addForeignKey(
                'fk-inventory_item_imagen-item_id',
                '{{%inventory_item_imagen}}',
                'item_id',
                '{{%inventory_item}}',
                'id',
                'CASCADE'
            );
        }

        // qr_code column on inventory_item
        $hasQrCode = $this->db->createCommand(
            "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'inventory_item' AND column_name = 'qr_code' LIMIT 1"
        )->queryOne() !== false;
        if (!$hasQrCode) {
            $this->addColumn('{{%inventory_item}}', 'qr_code', $this->string(100)->null());
        }

        // Unique index on qr_code
        $qrIdxExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'inventory_item' AND index_name = 'idx-inventory_item-qr_code' LIMIT 1"
        )->queryOne() !== false;
        if (!$qrIdxExists) {
            $this->createIndex('idx-inventory_item-qr_code', '{{%inventory_item}}', 'qr_code', true);
        }
    }

    public function safeDown()
    {
        // Drop FK and index if they exist
        $fkExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'inventory_item_imagen' AND constraint_name = 'fk-inventory_item_imagen-item_id' AND constraint_type = 'FOREIGN KEY' LIMIT 1"
        )->queryOne() !== false;
        if ($fkExists) {
            $this->dropForeignKey('fk-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}');
        }

        $idxExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'inventory_item_imagen' AND index_name = 'idx-inventory_item_imagen-item_id' LIMIT 1"
        )->queryOne() !== false;
        if ($idxExists) {
            $this->dropIndex('idx-inventory_item_imagen-item_id', '{{%inventory_item_imagen}}');
        }

        $this->dropTable('{{%inventory_item_imagen}}');

        // Drop qr_code unique index
        $qrIdxExists = $this->db->createCommand(
            "SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'inventory_item' AND index_name = 'idx-inventory_item-qr_code' LIMIT 1"
        )->queryOne() !== false;
        if ($qrIdxExists) {
            $this->dropIndex('idx-inventory_item-qr_code', '{{%inventory_item}}');
        }

        // Drop qr_code column
        $hasQrCode = $this->db->createCommand(
            "SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'inventory_item' AND column_name = 'qr_code' LIMIT 1"
        )->queryOne() !== false;
        if ($hasQrCode) {
            $this->dropColumn('{{%inventory_item}}', 'qr_code');
        }
    }
}
