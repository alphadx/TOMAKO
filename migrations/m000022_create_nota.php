<?php

use yii\db\Migration;

/**
 * Creates nota table
 * Notes and observations on work orders
 */
class m000021_create_nota extends Migration
{
    public function safeUp()
    {
        $this->createTable('nota', [
            'id' => $this->primaryKey()->unsigned(),
            'orden_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'usuario_id' => $this->integer()->notNull()->append('UNSIGNED'),
            'texto' => $this->text()->notNull(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Foreign keys
        $this->addForeignKey(
            'fk_nota_orden_id',
            'nota',
            'orden_id',
            'orden_servicio',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_nota_usuario_id',
            'nota',
            'usuario_id',
            'usuario',
            'id',
            'RESTRICT',
            'CASCADE'
        );

        // Indices
        $this->createIndex('idx_nota_orden_id', 'nota', 'orden_id');
        $this->createIndex('idx_nota_usuario_id', 'nota', 'usuario_id');
        $this->createIndex('idx_nota_created_at', 'nota', 'created_at');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_nota_usuario_id', 'nota');
        $this->dropForeignKey('fk_nota_orden_id', 'nota');
        $this->dropTable('nota');
    }
}
