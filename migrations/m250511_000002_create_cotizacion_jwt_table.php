<?php

declare(strict_types=1);

/**
 * Migración para crear la tabla de validación de cotizaciones JWT.
 * 
 * Esta tabla almacena el hash único y el JWT completo para validación de cotizaciones.
 * 
 * @author ID3.CL
 * @since 1.0.0
 */

use yii\db\Migration;

class m250511_000002_create_cotizacion_jwt_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%cotizacion_jwt}}', [
            'id'         => $this->primaryKey()->unsigned(),
            'hash'       => $this->string(64)->notNull()->unique(),
            'jwt'        => $this->text()->notNull(),
            'raiz_url'   => $this->string(255)->notNull(),
            'usado'      => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'created_at' => $this->integer()->unsigned()->notNull(),
            'expires_at' => $this->integer()->unsigned()->null(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Índices para búsquedas rápidas
        $this->createIndex('idx_cotizacion_jwt_hash', '{{%cotizacion_jwt}}', 'hash');
        $this->createIndex('idx_cotizacion_jwt_usado', '{{%cotizacion_jwt}}', ['usado', 'expires_at']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%cotizacion_jwt}}');
    }
}
