<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración para crear la tabla de seguimientos post-servicio.
 */
class m260511_000002_create_seguimiento_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%seguimiento}}', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_servicio_id' => $this->integer()->unsigned()->notNull(),
            'cliente_id' => $this->integer()->unsigned()->notNull(),
            'tipo' => $this->string(20)->notNull()->defaultValue('llamada'),
            'estado' => $this->string(20)->notNull()->defaultValue('pendiente'),
            'fecha_programada' => $this->integer()->unsigned()->notNull(),
            'fecha_realizacion' => $this->integer()->unsigned()->null(),
            'realizado_por' => $this->integer()->unsigned()->null(),
            'resultado' => $this->text()->null(),
            'satisfaccion' => $this->smallInteger()->null()->comment('1-5 estrellas'),
            'observaciones' => $this->text()->null(),
            'nps_score' => $this->decimal(3, 1)->null()->comment('NPS score 0-10'),
            'recomendariamos' => $this->boolean()->null(),
            'created_at' => $this->integer()->unsigned()->null(),
            'updated_at' => $this->integer()->unsigned()->null(),
        ]);

        // Índices para mejorar rendimiento
        $this->createIndex('idx_seguimiento_orden_servicio_id', '{{%seguimiento}}', 'orden_servicio_id');
        $this->createIndex('idx_seguimiento_cliente_id', '{{%seguimiento}}', 'cliente_id');
        $this->createIndex('idx_seguimiento_estado', '{{%seguimiento}}', 'estado');
        $this->createIndex('idx_seguimiento_fecha_programada', '{{%seguimiento}}', 'fecha_programada');
        $this->createIndex('idx_seguimiento_realizado_por', '{{%seguimiento}}', 'realizado_por');
        
        // Índice compuesto para búsquedas frecuentes
        $this->createIndex('idx_seguimiento_pendientes', '{{%seguimiento}}', ['estado', 'fecha_programada']);

        // Llaves foráneas
        $this->addForeignKey(
            'fk_seguimiento_orden_servicio',
            '{{%seguimiento}}',
            'orden_servicio_id',
            '{{%orden_servicio}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk_seguimiento_cliente',
            '{{%seguimiento}}',
            'cliente_id',
            '{{%cliente}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );

        $this->addForeignKey(
            'fk_seguimiento_realizado_por',
            '{{%seguimiento}}',
            'realizado_por',
            '{{%usuario}}',
            'id',
            'SET NULL',
            'RESTRICT'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_seguimiento_realizado_por', '{{%seguimiento}}');
        $this->dropForeignKey('fk_seguimiento_cliente', '{{%seguimiento}}');
        $this->dropForeignKey('fk_seguimiento_orden_servicio', '{{%seguimiento}}');
        
        $this->dropIndex('idx_seguimiento_pendientes', '{{%seguimiento}}');
        $this->dropIndex('idx_seguimiento_realizado_por', '{{%seguimiento}}');
        $this->dropIndex('idx_seguimiento_fecha_programada', '{{%seguimiento}}');
        $this->dropIndex('idx_seguimiento_estado', '{{%seguimiento}}');
        $this->dropIndex('idx_seguimiento_cliente_id', '{{%seguimiento}}');
        $this->dropIndex('idx_seguimiento_orden_servicio_id', '{{%seguimiento}}');
        
        $this->dropTable('{{%seguimiento}}');
    }
}
