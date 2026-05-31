<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración vacía para la carga de datos maestros de marcas y modelos.
 * 
 * NOTA IMPORTANTE: La carga de datos se ha movido a DatabaseInitService 
 * para permitir sincronización manual e idempotente desde el panel de administración.
 * 
 * Esta migración se mantiene solo por compatibilidad con el historial de migraciones,
 * pero no ejecuta ninguna operación de inserción de datos.
 * 
 * Para cargar los datos maestros de marcas y modelos, utilice el botón de
 * "Sincronización de Datos Maestros" en el panel de administración.
 */
class m250524_000023_seed_marcas_modelos_chile extends Migration
{
    public function safeUp(): void
    {
        // No operation - data seeding has been moved to DatabaseInitService
        // This ensures idempotent manual synchronization via admin panel
    }

    public function safeDown(): void
    {
        echo "Esta migración no contiene operaciones reversibles.\n";
    }
}
