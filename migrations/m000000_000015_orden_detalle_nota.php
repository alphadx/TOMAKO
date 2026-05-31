<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Agrega columna nota al detalle de orden de servicio.
 */
class m000000_000015_orden_detalle_nota extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%orden_servicio_detalle}}', 'nota', $this->string(500)->null()->after('subtotal'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%orden_servicio_detalle}}', 'nota');
    }
}
