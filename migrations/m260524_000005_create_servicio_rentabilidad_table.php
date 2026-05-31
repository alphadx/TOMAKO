<?php

use yii\db\Migration;

/**
 * Migración para crear tabla de rentabilidad por servicio (HU-023).
 */
class m260524_000005_create_servicio_rentabilidad_table extends Migration
{
    public function safeUp(): void
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%servicio_rentabilidad}}', [
            'id' => $this->primaryKey()->unsigned(),
            'servicio_id' => $this->integer()->unsigned()->notNull(),
            'periodo' => $this->string(7)->notNull()->comment('Formato YYYY-MM'),
            'total_ordenes' => $this->integer()->defaultValue(0),
            'ingreso_total' => $this->decimal(12, 2)->defaultValue(0.00),
            'costo_servicio' => $this->decimal(12, 2)->defaultValue(0.00),
            'costo_repuestos' => $this->decimal(12, 2)->defaultValue(0.00),
            'costo_mano_obra' => $this->decimal(12, 2)->defaultValue(0.00),
            'overhead' => $this->decimal(12, 2)->defaultValue(0.00),
            'costo_total' => $this->decimal(12, 2)->defaultValue(0.00),
            'utilidad_bruta' => $this->decimal(12, 2)->defaultValue(0.00),
            'margen_porcentaje' => $this->decimal(5, 2)->defaultValue(0.00),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ], $tableOptions);

        // Índices
        $this->createIndex('idx_servicio_rentabilidad_servicio', '{{%servicio_rentabilidad}}', 'servicio_id');
        $this->createIndex('idx_servicio_rentabilidad_periodo', '{{%servicio_rentabilidad}}', 'periodo');
        $this->createIndex('idx_servicio_rentabilidad_unique', '{{%servicio_rentabilidad}}', ['servicio_id', 'periodo'], true);

        // Foreign key
        $this->addForeignKey(
            'fk_servicio_rentabilidad_servicio',
            '{{%servicio_rentabilidad}}',
            'servicio_id',
            '{{%servicio}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_servicio_rentabilidad_servicio', '{{%servicio_rentabilidad}}');
        $this->dropIndex('idx_servicio_rentabilidad_unique', '{{%servicio_rentabilidad}}');
        $this->dropIndex('idx_servicio_rentabilidad_periodo', '{{%servicio_rentabilidad}}');
        $this->dropIndex('idx_servicio_rentabilidad_servicio', '{{%servicio_rentabilidad}}');
        $this->dropTable('{{%servicio_rentabilidad}}');
    }
}
