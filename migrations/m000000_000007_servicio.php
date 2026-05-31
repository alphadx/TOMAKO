<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Migración: tablas servicio e historial_precio con seed inicial.
 */
class m000000_000007_servicio extends Migration
{
    public function safeUp(): void
    {
        // ── Tabla servicio ──────────────────────────────────────────────────────
        $this->createTable('{{%servicio}}', [
            'id'                 => $this->primaryKey()->unsigned(),
            'codigo'             => $this->string(20)->notNull(),
            'nombre'             => $this->string(150)->notNull(),
            'descripcion'        => $this->text()->null(),
            'precio_base'        => $this->decimal(10, 2)->notNull()->defaultValue('0.00'),
            'duracion_estimada'  => $this->integer()->unsigned()->null()->comment('minutos'),
            'categoria_id'       => $this->integer()->unsigned()->notNull(),
            'status'             => $this->tinyInteger(1)->notNull()->defaultValue(1),
            'created_at'         => $this->integer()->unsigned()->null(),
            'updated_at'         => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('uk_servicio_codigo',    '{{%servicio}}', 'codigo', true);
        $this->createIndex('idx_servicio_categoria','{{%servicio}}', 'categoria_id');
        $this->createIndex('idx_servicio_status',   '{{%servicio}}', 'status');
        $this->addForeignKey(
            'fk_servicio_categoria',
            '{{%servicio}}', 'categoria_id',
            '{{%categoria}}', 'id',
            'RESTRICT'
        );

        // ── Tabla historial_precio ─────────────────────────────────────────────
        $this->createTable('{{%historial_precio}}', [
            'id'              => $this->primaryKey()->unsigned(),
            'servicio_id'     => $this->integer()->unsigned()->notNull(),
            'precio_anterior' => $this->decimal(10, 2)->notNull(),
            'precio_nuevo'    => $this->decimal(10, 2)->notNull(),
            'usuario_id'      => $this->integer()->unsigned()->null(),
            'motivo'          => $this->string(255)->null(),
            'created_at'      => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_historial_servicio', '{{%historial_precio}}', 'servicio_id');
        $this->addForeignKey(
            'fk_historial_servicio',
            '{{%historial_precio}}', 'servicio_id',
            '{{%servicio}}', 'id',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_historial_usuario',
            '{{%historial_precio}}', 'usuario_id',
            '{{%usuario}}', 'id',
            'SET NULL'
        );

        // ── Seed: servicios base ───────────────────────────────────────────────
        $now = time();
        // Obtenemos los IDs de categorías por nombre
        $catMecanica = (new \yii\db\Query())->select('id')->from('{{%categoria}}')->where(['nombre' => 'Mecánica'])->scalar();
        $catFreno    = (new \yii\db\Query())->select('id')->from('{{%categoria}}')->where(['nombre' => 'Frenos'])->scalar();
        $catAceite   = (new \yii\db\Query())->select('id')->from('{{%categoria}}')->where(['nombre' => 'Aceites y Lubricantes'])->scalar();
        $catElec     = (new \yii\db\Query())->select('id')->from('{{%categoria}}')->where(['nombre' => 'Electricidad'])->scalar();

        $this->batchInsert('{{%servicio}}',
            ['codigo', 'nombre', 'descripcion', 'precio_base', 'duracion_estimada', 'categoria_id', 'status', 'created_at', 'updated_at'],
            [
                ['S-0001', 'Cambio de aceite',          'Cambio de aceite de motor con filtro',       25000.00, 30,  $catAceite ?? 3, 1, $now, $now],
                ['S-0002', 'Revisión de frenos',         'Inspección y ajuste del sistema de frenos',  18000.00, 45,  $catFreno  ?? 2, 1, $now, $now],
                ['S-0003', 'Alineación y balanceo',      'Alineación de dirección y balanceo de ruedas',22000.00, 60,  $catMecanica ?? 1, 1, $now, $now],
                ['S-0004', 'Revisión eléctrica',         'Diagnóstico del sistema eléctrico',          15000.00, 40,  $catElec   ?? 4, 1, $now, $now],
                ['S-0005', 'Cambio de filtros',          'Cambio de filtros de aire, combustible y habitáculo', 12000.00, 20, $catAceite ?? 3, 1, $now, $now],
            ]
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_historial_servicio', '{{%historial_precio}}');
        $this->dropForeignKey('fk_historial_usuario',  '{{%historial_precio}}');
        $this->dropTable('{{%historial_precio}}');

        $this->dropForeignKey('fk_servicio_categoria', '{{%servicio}}');
        $this->dropTable('{{%servicio}}');
    }
}
