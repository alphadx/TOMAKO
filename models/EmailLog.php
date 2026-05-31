<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

class EmailLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%email_log}}';
    }

    public function rules(): array
    {
        return [
            [['destinatario', 'asunto', 'cuerpo_html', 'enviado_at'], 'required'],
            [['cuerpo_html', 'error'], 'string'],
            [['exito', 'enviado_at', 'created_at', 'updated_at'], 'integer'],
            [['destinatario'], 'email'],
            [['destinatario'], 'string', 'max' => 254],
            [['asunto'], 'string', 'max' => 200],
            [['plantilla'], 'string', 'max' => 60],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
