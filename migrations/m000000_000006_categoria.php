<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: tabla categoria con soporte para jerarquía y seed inicial.
 */
class m000000_000006_categoria extends Migration
{
    public function safeUp(): void
    {
        $existing = $this->db->schema->getTableSchema('{{%categoria}}', true);
        if ($existing !== null) {
            $this->dropTable('{{%categoria}}');
        }

        $this->createTable('{{%categoria}}', [
            'id'          => $this->primaryKey()->unsigned(),
            'nombre'      => $this->string(100)->notNull(),
            'descripcion' => $this->text()->null(),
            'padre_id'    => $this->integer()->unsigned()->null(),
            'tipo'        => "ENUM('servicio','insumo','ambos') NOT NULL DEFAULT 'ambos'",
            'icono'       => $this->string(50)->null(),
            'color'       => $this->string(7)->null(),
            'orden'       => $this->integer()->notNull()->defaultValue(0),
            'status'      => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'  => $this->integer()->unsigned()->null(),
            'updated_at'  => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_categoria_nombre', '{{%categoria}}', 'nombre', true);
        $this->createIndex('idx_padre_id', '{{%categoria}}', 'padre_id');
        $this->createIndex('idx_tipo_status', '{{%categoria}}', ['tipo', 'status']);
        $this->addForeignKey(
            'fk_categoria_padre',
            '{{%categoria}}', 'padre_id',
            '{{%categoria}}', 'id',
            'RESTRICT', 'CASCADE'
        );

        // Seed: categorías base
        $now = time();
        $this->batchInsert('{{%categoria}}',
            ['nombre', 'descripcion', 'padre_id', 'tipo', 'icono', 'color', 'orden', 'status', 'created_at', 'updated_at'],
            [
                ['Mecánica',              'Servicios mecánicos generales',       null, 'servicio', 'wrench', '#3498db', 1, 1, $now, $now],
                ['Frenos',                'Revisión y reparación de frenos',     null, 'servicio', 'brake', '#e74c3c', 2, 1, $now, $now],
                ['Aceites y Lubricantes', 'Cambios de aceite y lubricantes',     null, 'ambos',    'oil', '#f39c12', 3, 1, $now, $now],
                ['Electricidad',          'Sistema eléctrico del vehículo',      null, 'servicio', 'bolt', '#f1c40f', 4, 1, $now, $now],
                ['Carrocería',            'Reparación y pintura de carrocería',  null, 'servicio', 'car', '#2ecc71', 5, 1, $now, $now],
                ['Refrigeración',         'Sistema de refrigeración del motor',  null, 'servicio', 'snowflake', '#1abc9c', 6, 1, $now, $now],
            ]
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_categoria_padre', '{{%categoria}}');
        $this->dropTable('{{%categoria}}');
    }
}
