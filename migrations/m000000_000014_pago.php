<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración que crea la tabla del módulo 10 – Pagos:
 * pago: registro de pagos asociados a órdenes de servicio.
 */
class m000000_000014_pago extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%pago}}', [
            'id'             => $this->primaryKey()->unsigned(),
            'orden_id'       => $this->integer()->unsigned()->notNull(),
            'usuario_id'     => $this->integer()->unsigned()->null(),
            'monto'          => $this->decimal(10, 2)->notNull(),
            'metodo_pago'    => "ENUM('efectivo','tarjeta_debito','tarjeta_credito','transferencia','otro') NOT NULL DEFAULT 'efectivo'",
            'referencia'     => $this->string(100)->null()->comment('Número de comprobante, voucher, etc.'),
            'estado'         => "ENUM('pendiente','pagado','anulado') NOT NULL DEFAULT 'pendiente'",
            'notas'          => $this->text()->null(),
            'pagado_at'      => $this->integer()->unsigned()->null(),
            'created_at'     => $this->integer()->unsigned()->null(),
            'updated_at'     => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_pago_orden',   '{{%pago}}', 'orden_id');
        $this->createIndex('idx_pago_estado',  '{{%pago}}', 'estado');
        $this->createIndex('idx_pago_usuario', '{{%pago}}', 'usuario_id');

        $this->addForeignKey(
            'fk_pago_orden',
            '{{%pago}}', 'orden_id',
            '{{%orden_servicio}}', 'id',
            'RESTRICT'
        );
        $this->addForeignKey(
            'fk_pago_usuario',
            '{{%pago}}', 'usuario_id',
            '{{%usuario}}', 'id',
            'SET NULL'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_pago_usuario', '{{%pago}}');
        $this->dropForeignKey('fk_pago_orden',   '{{%pago}}');
        $this->dropTable('{{%pago}}');
    }
}
