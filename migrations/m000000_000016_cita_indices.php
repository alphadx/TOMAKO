<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Índices de rendimiento para módulo de citas.
 */
class m000000_000016_cita_indices extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex(
            'idx_cita_fecha_inicio_fin',
            '{{%cita}}',
            ['fecha', 'hora_inicio', 'hora_fin']
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_cita_fecha_inicio_fin', '{{%cita}}');
    }
}
