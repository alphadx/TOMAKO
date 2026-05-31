<?php

use yii\db\Migration;

/**
 * Creates plantilla_checklist and plantilla_checklist_item tables
 * Templates for checklists by service type (HU-028)
 */
class m260524_000002_create_plantillas_checklist extends Migration
{
    public function safeUp()
    {
        // Create plantilla_checklist table
        $this->createTable('{{%plantilla_checklist}}', [
            'id' => $this->integer()->unsigned()->notNull()->append('AUTO_INCREMENT PRIMARY KEY')->comment('ID de la plantilla'),
            'servicio_id' => $this->integer()->unsigned()->notNull()->comment('ID del servicio asociado'),
            'nombre' => $this->string(150)->notNull()->comment('Nombre descriptivo de la plantilla'),
            'descripcion' => $this->string(500)->null()->comment('Descripción opcional'),
            'activa' => $this->boolean()->defaultValue(true)->notNull()->comment('Estado de la plantilla'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Create plantilla_checklist_item table
        $this->createTable('{{%plantilla_checklist_item}}', [
            'id' => $this->integer()->unsigned()->notNull()->append('AUTO_INCREMENT PRIMARY KEY')->comment('ID del item'),
            'plantilla_id' => $this->integer()->unsigned()->notNull()->comment('ID de la plantilla padre'),
            'descripcion' => $this->string(255)->notNull()->comment('Descripción del item a verificar'),
            'orden' => $this->integer()->defaultValue(0)->comment('Orden de visualización'),
            'obligatorio' => $this->boolean()->defaultValue(false)->notNull()->comment('Indica si es obligatorio'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Foreign keys
        $this->addForeignKey(
            'fk_plantilla_checklist_servicio_id',
            '{{%plantilla_checklist}}',
            'servicio_id',
            '{{%servicio}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_plantilla_checklist_item_plantilla_id',
            '{{%plantilla_checklist_item}}',
            'plantilla_id',
            '{{%plantilla_checklist}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // Indices
        $this->createIndex('idx_plantilla_checklist_servicio_id', '{{%plantilla_checklist}}', 'servicio_id');
        $this->createIndex('idx_plantilla_checklist_activa', '{{%plantilla_checklist}}', 'activa');
        $this->createIndex('idx_plantilla_checklist_item_plantilla_id', '{{%plantilla_checklist_item}}', 'plantilla_id');
        $this->createIndex('idx_plantilla_checklist_item_orden', '{{%plantilla_checklist_item}}', 'orden');

        // Insert sample data - plantilla para mantenimiento básico
        $this->insert('{{%plantilla_checklist}}', [
            'servicio_id' => 1,
            'nombre' => 'Checklist Mantenimiento Básico',
            'descripcion' => 'Items estándar para mantenimiento preventivo',
            'activa' => true,
        ]);

        // Obtener el ID de la plantilla insertada
        $plantillaId = (new \yii\db\Query())
            ->select('id')
            ->from('{{%plantilla_checklist}}')
            ->where(['nombre' => 'Checklist Mantenimiento Básico'])
            ->scalar();

        // Items para mantenimiento básico
        $items = [
            ['descripcion' => 'Verificar nivel de aceite de motor', 'orden' => 1, 'obligatorio' => true],
            ['descripcion' => 'Revisar nivel de líquido de frenos', 'orden' => 2, 'obligatorio' => true],
            ['descripcion' => 'Comprobar nivel de refrigerante', 'orden' => 3, 'obligatorio' => true],
            ['descripcion' => 'Inspeccionar estado de correas', 'orden' => 4, 'obligatorio' => false],
            ['descripcion' => 'Verificar presión de neumáticos', 'orden' => 5, 'obligatorio' => true],
            ['descripcion' => 'Revisar estado de escobillas limpiaparabrisas', 'orden' => 6, 'obligatorio' => false],
            ['descripcion' => 'Comprobar funcionamiento de luces', 'orden' => 7, 'obligatorio' => true],
            ['descripcion' => 'Inspeccionar filtro de aire', 'orden' => 8, 'obligatorio' => false],
        ];

        foreach ($items as $item) {
            $this->insert('{{%plantilla_checklist_item}}', [
                'plantilla_id' => $plantillaId,
                'descripcion' => $item['descripcion'],
                'orden' => $item['orden'],
                'obligatorio' => $item['obligatorio'],
            ]);
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_plantilla_checklist_item_plantilla_id', '{{%plantilla_checklist_item}}');
        $this->dropForeignKey('fk_plantilla_checklist_servicio_id', '{{%plantilla_checklist}}');
        
        $this->dropTable('{{%plantilla_checklist_item}}');
        $this->dropTable('{{%plantilla_checklist}}');
    }
}
