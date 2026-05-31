<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea la tabla vehiculo.
 */
class m000000_000009_vehiculo extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%vehiculo}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'patente'    => $this->string(10)->notNull()->unique(),
            'marca'      => $this->string(60)->notNull(),
            'modelo'     => $this->string(60)->notNull(),
            'anio'       => 'SMALLINT UNSIGNED NOT NULL',
            'vin'        => $this->string(17)->null(),
            'cliente_id' => $this->integer()->unsigned()->notNull(),
            'ultimo_km'  => $this->integer()->unsigned()->null()->defaultValue(0),
            'foto_path'  => $this->string(255)->null(),
            'status'     => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_vehiculo_cliente', '{{%vehiculo}}', 'cliente_id');
        $this->createIndex('idx_vehiculo_patente', '{{%vehiculo}}', 'patente');
        $this->createIndex('idx_vehiculo_status',  '{{%vehiculo}}', 'status');

        $this->addForeignKey(
            'fk_vehiculo_cliente',
            '{{%vehiculo}}', 'cliente_id',
            '{{%cliente}}',  'id',
            'RESTRICT', 'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_vehiculo_cliente', '{{%vehiculo}}');
        $this->dropTable('{{%vehiculo}}');
    }
}
