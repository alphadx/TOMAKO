<?php

declare(strict_types=1);

namespace app\components\behaviors;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use app\models\AuditLog;
use ReflectionClass;
use Exception;

/**
 * Behavior de auditoría: registra automáticamente CREATE, UPDATE y DELETE
 * en la tabla audit_log para cualquier ActiveRecord que lo adjunte.
 *
 * Captura automáticamente:
 * - CREATE: Cuando se inserta un nuevo registro
 * - UPDATE: Cuando se modifica un registro existente (solo cambios reales)
 * - DELETE: Cuando se elimina un registro
 *
 * Uso en el modelo:
 * ```php
 * public function behaviors(): array
 * {
 *     return [
 *         'audit' => [
 *             'class' => AuditBehavior::class,
 *             'excludedAttributes' => ['updated_at'],
 *         ],
 *     ];
 * }
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class AuditBehavior extends Behavior
{
    /**
     * Campos que NO deben registrarse en el audit_log (ej: timestamps automáticos).
     *
     * @var string[]
     */
    public array $excludedAttributes = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Módulo que se registrará en los logs
     * Si no se especifica, se deduce del namespace del modelo
     */
    public ?string $modulo = null;

    /**
     * Cache de atributos anteriores capturados antes del UPDATE.
     * Necesario porque Yii2 actualiza _oldAttributes con los nuevos valores
     * después de ejecutar el UPDATE en BD, antes de disparar EVENT_AFTER_UPDATE.
     *
     * @var array|null
     */
    private ?array $_cachedOldAttributes = null;

    /**
     * {@inheritdoc}
     */
    public function events(): array
    {
        return [
            BaseActiveRecord::EVENT_AFTER_INSERT   => 'onAfterInsert',
            BaseActiveRecord::EVENT_BEFORE_UPDATE  => 'onBeforeUpdate',
            BaseActiveRecord::EVENT_AFTER_UPDATE   => 'onAfterUpdate',
            BaseActiveRecord::EVENT_BEFORE_DELETE  => 'onBeforeDelete',
        ];
    }

    /**
     * Captura los atributos anteriores ANTES de que Yii2 los sobrescriba.
     */
    public function onBeforeUpdate(): void
    {
        /** @var ActiveRecord $owner */
        $owner = $this->owner;
        $this->_cachedOldAttributes = $owner->getOldAttributes() ?: null;
    }

    /**
     * Registra la creación de un nuevo registro.
     */
    public function onAfterInsert(): void
    {
        $startTime = microtime(true);
        try {
            $this->saveAuditLog(
                AuditLog::ACTION_CREATE,
                null,  // Sin datos previos
                $this->getAttributesToLog()  // Todos los atributos nuevos
            );
            $this->recordDuration($startTime);
        } catch (Exception $e) {
            Yii::warning("Error en AuditBehavior::onAfterInsert: {$e->getMessage()}");
        }
    }

    /**
     * Registra la actualización de un registro existente.
     * Usa los old attributes cacheados en onBeforeUpdate().
     */
    public function onAfterUpdate(): void
    {
        $startTime = microtime(true);
        try {
            $oldAttributes = $this->_cachedOldAttributes ?: [];
            $newAttributes = $this->owner->attributes;
            $this->_cachedOldAttributes = null; // Limpiar cache

            // Filtrar cambios automáticos en ambos arrays
            $oldAttributesFiltered = $this->filterExcludedAttributes($oldAttributes);
            $newAttributesFiltered = $this->filterExcludedAttributes($newAttributes);
            
            if (empty($oldAttributesFiltered)) {
                return;  // Sin cambios reales
            }

            // Construir arrays de cambios solo con atributos que realmente cambiaron
            $previos = [];
            $nuevos = [];

            foreach ($oldAttributesFiltered as $attr => $oldValue) {
                if (array_key_exists($attr, $newAttributesFiltered)) {
                    $newValue = $newAttributesFiltered[$attr];
                    // Solo registrar si el valor cambió
                    if ($oldValue !== $newValue) {
                        $previos[$attr] = $oldValue;
                        $nuevos[$attr] = $newValue;
                    }
                }
            }

            if (!empty($previos)) {
                $this->saveAuditLog(
                    AuditLog::ACTION_UPDATE,
                    $previos,
                    $nuevos
                );
            }
            $this->recordDuration($startTime);
        } catch (Exception $e) {
            Yii::warning("Error en AuditBehavior::onAfterUpdate: {$e->getMessage()}");
        }
    }

    /**
     * Captura el estado antes de eliminar para registrar.
     */
    public function onBeforeDelete(): void
    {
        $startTime = microtime(true);
        try {
            $this->saveAuditLog(
                AuditLog::ACTION_DELETE,
                $this->getAttributesToLog(),  // Todos los datos antes de borrar
                null  // Sin datos nuevos
            );
            $this->recordDuration($startTime);
        } catch (Exception $e) {
            Yii::warning("Error en AuditBehavior::onBeforeDelete: {$e->getMessage()}");
        }
    }

    /**
     * Guardar log de auditoría en la tabla audit_log
     */
    private function saveAuditLog(string $accion, ?array $datosAnterior, ?array $datosNuevo): void
    {
        $auditLog = new AuditLog([
            'usuario_id'    => Yii::$app->has('user') && !Yii::$app->user->isGuest 
                ? (int) Yii::$app->user->id 
                : null,
            'accion'        => $accion,
            'modulo'        => $this->getModulo(),
            'entidad'       => $this->getEntidadNombre(),
            'registro_id'   => $this->getPrimaryKeyValue(),
            'datos_previos' => $datosAnterior ? json_encode($datosAnterior, JSON_UNESCAPED_UNICODE) : null,
            'datos_nuevos'  => $datosNuevo ? json_encode($datosNuevo, JSON_UNESCAPED_UNICODE) : null,
            'ip_address'    => $this->getClientIp(),
            'duracion_ms'   => 0,  // Se actualiza en siguiente fase
        ]);

        if (!$auditLog->save(false)) {  // false = sin validación (mejor performance)
            Yii::error('No se pudo guardar AuditLog: ' . json_encode($auditLog->errors));
        }
    }

    /**
     * Obtener nombre del módulo
     */
    private function getModulo(): string
    {
        if ($this->modulo !== null) {
            return $this->modulo;
        }

        // Deducir del namespace del modelo
        $refClass = new ReflectionClass($this->owner);
        $namespace = $refClass->getNamespaceName();
        $parts = explode('\\', $namespace);
        
        // Si está en app\models, módulo es genérico
        if (end($parts) === 'models') {
            return 'Core';
        }

        return end($parts);
    }

    /**
     * Obtener nombre de la entidad desde el modelo
     */
    private function getEntidadNombre(): string
    {
        $refClass = new ReflectionClass($this->owner);
        return $refClass->getShortName();
    }

    /**
     * Obtener atributos a registrar (excluyendo los habituales)
     */
    private function getAttributesToLog(): array
    {
        $attributes = $this->owner->attributes;
        return $this->filterExcludedAttributes($attributes);
    }

    /**
     * Filtrar atributos excluidos
     */
    private function filterExcludedAttributes(array $attributes): array
    {
        return array_filter(
            $attributes,
            fn(string $key): bool => !in_array($key, $this->excludedAttributes, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Obtener dirección IP del cliente
     */
    private function getClientIp(): ?string
    {
        try {
            if (Yii::$app->has('request')) {
                return Yii::$app->request->userIP;
            }
        } catch (Exception $e) {
            // Fallback
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * Obtiene el valor de la clave primaria del modelo.
     */
    private function getPrimaryKeyValue(): ?int
    {
        try {
            $pk = $this->owner->getPrimaryKey();
            if (is_array($pk)) {
                return (int) array_values($pk)[0];
            }
            return (int) $pk;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Registrar duración de la operación (Fase 12.2)
     */
    private function recordDuration(float $startTime): void
    {
        // TODO: Implementar medición de duración en Fase 12.2
    }
}
