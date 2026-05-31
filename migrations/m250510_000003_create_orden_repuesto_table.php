<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migration para crear tabla intermedia orden_servicio_repuesto (HU-013)
 * Permite relacionar repuestos/insumos con órdenes de servicio
 */
class m250510_000003_create_orden_repuesto_table extends Migration
{
    public function safeUp(): void
    {
        // Obtenemos la definición exacta de la columna 'id' de las tablas padre para evitar incompatibilidades (Signed/Unsigned, Int/BigInt)
        $db = $this->db;
        $schema = $db->schema;

        // Inspeccionamos la columna 'id' de orden_servicio
        $ordenTable = $schema->getTableSchema('{{%orden_servicio}}');
        $ordenIdType = $ordenTable->getColumn('id')->dbType; // Ej: "int(11)" o "bigint(20) unsigned"
        $ordenIdAllowNull = $ordenTable->getColumn('id')->allowNull;

        // Inspeccionamos la columna 'id' de inventory_item
        $itemTable = $schema->getTableSchema('{{%inventory_item}}');
        $itemIdType = $itemTable->getColumn('id')->dbType;
        $itemIdAllowNull = $itemTable->getColumn('id')->allowNull;

        $this->createTable('{{%orden_servicio_repuesto}}', [
            'id' => $this->primaryKey(),
            'orden_id' => "$ordenIdType" . ($ordenIdAllowNull ? ' NULL' : ' NOT NULL'),
            'repuesto_id' => "$itemIdType" . ($itemIdAllowNull ? ' NULL' : ' NOT NULL'),
            'cantidad' => $this->integer()->notNull()->defaultValue(1),
            'precio_unitario_aplicado' => $this->decimal(10, 2)->notNull(),
            'subtotal' => $this->decimal(10, 2)->notNull(),
            'nota' => $this->string(500)->null(),
            'created_at' => $this->integer(),
            'updated_at' => $this->integer(),
        ]);

        // Índices para performance
        $this->createIndex('idx_osr_orden', '{{%orden_servicio_repuesto}}', 'orden_id');
        $this->createIndex('idx_osr_repuesto', '{{%orden_servicio_repuesto}}', 'repuesto_id');

        // Foreign keys
        $this->addForeignKey('fk_osr_orden', '{{%orden_servicio_repuesto}}', 'orden_id', '{{%orden_servicio}}', 'id', 'CASCADE', 'RESTRICT');
        $this->addForeignKey('fk_osr_repuesto', '{{%orden_servicio_repuesto}}', 'repuesto_id', '{{%inventory_item}}', 'id', 'RESTRICT', 'RESTRICT');
    }

    private function getDbColumnType(string $dbType, bool $notNull): \yii\db\ColumnSchemaBuilder
    {
        $typeMatch = [];
        if (preg_match('/^(\w+)(?:\((\d+(?:,\s*\d+)?)\))?\s*(unsigned)?\s*/i', $dbType, $typeMatch)) {
            $type = $typeMatch[1];
            $length = $typeMatch[2] ?? null;
            $isUnsigned = isset($typeMatch[3]) && !empty(trim($typeMatch[3]));

            $column = $this->$type();
            if ($length) {
                $params = array_map('trim', explode(',', $length));
                $column = $column->{$params[0]};
                if (isset($params[1])) {
                    $column = $column->{$params[1]};
                }
            }
            if ($isUnsigned) {
                $column = $column->unsigned();
            }
            if ($notNull) {
                $column = $column->notNull();
            }
            return $column;
        }

        throw new \yii\base\InvalidConfigException("Tipo de columna no reconocido: {$dbType}");
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_osr_repuesto', '{{%orden_servicio_repuesto}}');
        $this->dropForeignKey('fk_osr_orden', '{{%orden_servicio_repuesto}}');

        $this->dropIndex('idx_osr_repuesto', '{{%orden_servicio_repuesto}}');
        $this->dropIndex('idx_osr_orden', '{{%orden_servicio_repuesto}}');

        $this->dropTable('{{%orden_servicio_repuesto}}');
    }
}