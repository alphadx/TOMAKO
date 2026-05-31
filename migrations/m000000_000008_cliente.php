<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: tabla cliente.
 */
class m000000_000008_cliente extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%cliente}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'nombre'     => $this->string(150)->notNull(),
            'email'      => $this->string(150)->null(),
            'telefono'   => $this->string(25)->null(),
            'direccion'  => $this->text()->null(),
            'rut'        => $this->string(15)->null(),
            'status'     => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'notas'      => $this->text()->null(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_cliente_email',  '{{%cliente}}', 'email', true);
        $this->createIndex('idx_cliente_status','{{%cliente}}', 'status');
        $this->createIndex('idx_cliente_nombre','{{%cliente}}', 'nombre');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%cliente}}');
    }
}
