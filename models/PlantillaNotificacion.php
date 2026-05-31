<?php
declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

class PlantillaNotificacion extends ActiveRecord
{
    public const CANAL_EMAIL = 'email';
    public const CANAL_INTERNO = 'interno';
    public const CANAL_AMBOS = 'ambos';

    public static function tableName(): string
    {
        return '{{%plantilla_notificacion}}';
    }

    public function rules(): array
    {
        return [
            [['codigo', 'canal', 'asunto', 'cuerpo_html'], 'required'],
            [['cuerpo_html', 'variables'], 'string'],
            [['activo', 'created_at', 'updated_at'], 'integer'],
            [['codigo'], 'string', 'max' => 60],
            [['asunto'], 'string', 'max' => 200],
            [['codigo'], 'unique'],
            [['canal'], 'in', 'range' => [self::CANAL_EMAIL, self::CANAL_INTERNO, self::CANAL_AMBOS]],
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

    public static function findActivaOFail(string $codigo): self
    {
        $model = self::findOne(['codigo' => $codigo, 'activo' => 1]);
        if ($model === null) {
            throw new \RuntimeException("Plantilla no encontrada: {$codigo}");
        }

        return $model;
    }

    /**
     * @param array<string, string> $data
     * @return array{asunto: string, cuerpo: string}
     */
    public function render(array $data): array
    {
        $asunto = $this->asunto;
        $cuerpo = $this->cuerpo_html;
        $variables = $this->getVariablesArray();

        foreach ($variables as $variable) {
            $value = htmlspecialchars((string) ($data[$variable] ?? ''), ENT_QUOTES, 'UTF-8');
            $asunto = str_replace('{{' . $variable . '}}', $value, $asunto);
            $cuerpo = str_replace('{{' . $variable . '}}', $value, $cuerpo);
        }

        return ['asunto' => $asunto, 'cuerpo' => $cuerpo];
    }

    /**
     * @return string[]
     */
    public function getVariablesArray(): array
    {
        $decoded = json_decode((string) $this->variables, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded)));
    }
}
