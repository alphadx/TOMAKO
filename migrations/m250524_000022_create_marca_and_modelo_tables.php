<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea las tablas marca y modelo para normalizar datos de vehículos.
 * 
 * Esta migración:
 * - Crea tabla marca con nombre único
 * - Crea tabla modelo relacionado con marca
 * - Agrega índices para rendimiento
 * - Migra datos existentes desde vehiculo.marca y vehiculo.modelo
 */
class m250524_000022_create_marca_and_modelo_tables extends Migration
{
    public function safeUp(): void
    {
        // Crear tabla marca
        $this->createTable('{{%marca}}', [
            'id' => $this->primaryKey()->unsigned(),
            'nombre' => $this->string(60)->notNull()->unique(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_marca_nombre', '{{%marca}}', 'nombre');

        // Crear tabla modelo
        $this->createTable('{{%modelo}}', [
            'id' => $this->primaryKey()->unsigned(),
            'marca_id' => $this->integer()->unsigned()->notNull(),
            'nombre' => $this->string(60)->notNull(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_modelo_marca', '{{%modelo}}', 'marca_id');
        $this->createIndex('idx_modelo_nombre', '{{%modelo}}', 'nombre');
        $this->createIndex('idx_modelo_marca_nombre', '{{%modelo}}', ['marca_id', 'nombre'], true);

        $this->addForeignKey(
            'fk_modelo_marca',
            '{{%modelo}}', 'marca_id',
            '{{%marca}}', 'id',
            'CASCADE', 'CASCADE'
        );

        // Migrar datos existentes desde vehiculo
        $this->migrarDatosExistentes();

        // Agregar columnas de clave foránea a vehiculo (temporalmente NULL)
        $this->addColumn('{{%vehiculo}}', 'marca_id', $this->integer()->unsigned()->null()->after('marca'));
        $this->addColumn('{{%vehiculo}}', 'modelo_id', $this->integer()->unsigned()->null()->after('modelo'));

        // Actualizar referencias en vehiculo
        $this->actualizarReferenciasVehiculos();

        // Crear índices
        $this->createIndex('idx_vehiculo_marca', '{{%vehiculo}}', 'marca_id');
        $this->createIndex('idx_vehiculo_modelo', '{{%vehiculo}}', 'modelo_id');

        // Agregar foreign keys
        $this->addForeignKey(
            'fk_vehiculo_marca',
            '{{%vehiculo}}', 'marca_id',
            '{{%marca}}', 'id',
            'SET NULL', 'CASCADE'
        );

        $this->addForeignKey(
            'fk_vehiculo_modelo',
            '{{%vehiculo}}', 'modelo_id',
            '{{%modelo}}', 'id',
            'SET NULL', 'CASCADE'
        );
    }

    /**
     * Migra marcas y modelos únicos desde los vehículos existentes.
     */
    private function migrarDatosExistentes(): void
    {
        // Obtener marcas únicas
        $marcas = $this->db->createCommand("
            SELECT DISTINCT UPPER(TRIM(marca)) as nombre 
            FROM {{%vehiculo}} 
            WHERE marca IS NOT NULL AND marca != ''
            ORDER BY nombre
        ")->queryAll();

        $marcaMap = [];
        foreach ($marcas as $marca) {
            $nombre = $marca['nombre'];
            $this->insert('{{%marca}}', [
                'nombre' => $nombre,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            $marcaMap[$nombre] = $this->db->getLastInsertID('{{%marca}}');
        }

        // Obtener modelos únicos por marca
        $modelos = $this->db->createCommand("
            SELECT UPPER(TRIM(marca)) as marca, UPPER(TRIM(modelo)) as modelo
            FROM {{%vehiculo}} 
            WHERE marca IS NOT NULL AND marca != '' 
              AND modelo IS NOT NULL AND modelo != ''
            GROUP BY marca, modelo
            ORDER BY marca, modelo
        ")->queryAll();

        foreach ($modelos as $modelo) {
            $marcaNombre = $modelo['marca'];
            $modeloNombre = $modelo['modelo'];
            
            if (isset($marcaMap[$marcaNombre])) {
                $this->insert('{{%modelo}}', [
                    'marca_id' => $marcaMap[$marcaNombre],
                    'nombre' => $modeloNombre,
                    'created_at' => time(),
                    'updated_at' => time(),
                ]);
            }
        }
    }

    /**
     * Actualiza las referencias marca_id y modelo_id en la tabla vehiculo.
     */
    private function actualizarReferenciasVehiculos(): void
    {
        $vehiculos = $this->db->createCommand("
            SELECT id, UPPER(TRIM(marca)) as marca, UPPER(TRIM(modelo)) as modelo
            FROM {{%vehiculo}}
            WHERE marca IS NOT NULL AND marca != ''
        ")->queryAll();

        foreach ($vehiculos as $vehiculo) {
            $marcaId = $this->db->createCommand("
                SELECT id FROM {{%marca}} WHERE nombre = :nombre
            ", [':nombre' => $vehiculo['marca']])->queryScalar();

            $modeloId = null;
            if ($marcaId && !empty($vehiculo['modelo'])) {
                $modeloId = $this->db->createCommand("
                    SELECT id FROM {{%modelo}} 
                    WHERE marca_id = :marcaId AND nombre = :nombre
                ", [
                    ':marcaId' => $marcaId,
                    ':nombre' => $vehiculo['modelo']
                ])->queryScalar();
            }

            if ($marcaId || $modeloId) {
                $this->update('{{%vehiculo}}', [
                    'marca_id' => $marcaId,
                    'modelo_id' => $modeloId,
                ], ['id' => $vehiculo['id']]);
            }
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_vehiculo_modelo', '{{%vehiculo}}');
        $this->dropForeignKey('fk_vehiculo_marca', '{{%vehiculo}}');
        $this->dropIndex('idx_vehiculo_modelo', '{{%vehiculo}}');
        $this->dropIndex('idx_vehiculo_marca', '{{%vehiculo}}');
        
        $this->dropColumn('{{%vehiculo}}', 'modelo_id');
        $this->dropColumn('{{%vehiculo}}', 'marca_id');
        
        $this->dropForeignKey('fk_modelo_marca', '{{%modelo}}');
        $this->dropTable('{{%modelo}}');
        $this->dropTable('{{%marca}}');
    }
}
