<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración para agregar campo ultima_mantencion_at a la tabla vehiculo (HU-003, HU-007)
 * Permite registrar cuándo fue la última mantención del vehículo para calcular próximas mantenciones
 */
class m250511_000001_add_ultima_mantencion_at_to_vehiculo extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%vehiculo}}', 'ultima_mantencion_at', $this->integer()->unsigned()->null());
        
        // Índice para consultas por fecha de mantención
        $this->createIndex('idx_vehiculo_ultima_mantencion', '{{%vehiculo}}', 'ultima_mantencion_at');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_vehiculo_ultima_mantencion', '{{%vehiculo}}');
        $this->dropColumn('{{%vehiculo}}', 'ultima_mantencion_at');
    }
}
