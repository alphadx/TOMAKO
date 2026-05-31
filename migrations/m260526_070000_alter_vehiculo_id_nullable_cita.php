<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que modifica vehiculo_id para aceptar NULL
 * y permite citas con patente temporal (sin vehículo registrado).
 */
class m260526_070000_alter_vehiculo_id_nullable_cita extends Migration
{
    public function safeUp(): void
    {
        // Modificar vehiculo_id para aceptar NULL
        $this->alterColumn('{{%cita}}', 'vehiculo_id', $this->integer()->unsigned()->null());
    }

    public function safeDown(): void
    {
        // Revertir a NOT NULL (asegurándose de que no haya valores NULL primero)
        $this->alterColumn('{{%cita}}', 'vehiculo_id', $this->integer()->unsigned()->notNull());
    }
}
