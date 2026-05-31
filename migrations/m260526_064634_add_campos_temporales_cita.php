<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que agrega los campos para identificación temporal y patente temporal
 * a la tabla cita (para clientes/vehículos sin registro).
 */
class m260526_064634_add_campos_temporales_cita extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%cita}}', 'patente_temporal', $this->string(50)->null()->after('vehiculo_id'));
        $this->addColumn('{{%cita}}', 'cliente_identificacion_temp', $this->string(50)->null()->after('patente_temporal'));
        $this->addColumn('{{%cita}}', 'cliente_tipo_ident_temp', $this->string(10)->null()->defaultValue('RUN')->after('cliente_identificacion_temp'));
        
        $this->createIndex('idx_cita_patente_temp', '{{%cita}}', 'patente_temporal');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_cita_patente_temp', '{{%cita}}');
        $this->dropColumn('{{%cita}}', 'cliente_tipo_ident_temp');
        $this->dropColumn('{{%cita}}', 'cliente_identificacion_temp');
        $this->dropColumn('{{%cita}}', 'patente_temporal');
    }
}
