<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\base\InvalidConfigException;

/**
 * Modelo AuditLog - Registro Inmutable de Auditoría.
 * 
 * Este modelo mapea la tabla {{%audit_log}} y proporciona registro inmutable
 * de todas las operaciones críticas del sistema (CREATE, UPDATE, DELETE, LOGIN, etc).
 * 
 * IMPORTANTE: Este modelo NO usa AuditBehavior para evitar recursión infinita.
 * Los registros se crean automáticamente desde otros modelos via AuditBehavior.
 *
 * @property int|null         $id              ID único (BigInt)
 * @property int|null         $usuario_id      FK a usuario (nullable)
 * @property string           $accion          Acción: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, EXPORT, ROLLBACK
 * @property string           $modulo          Módulo afectado (ej: Clientes, Servicios)
 * @property string           $entidad         Nombre de la entidad/tabla (ej: Cliente, Servicio)
 * @property int|null         $registro_id     ID del registro afectado
 * @property string|null      $datos_previos   Estado anterior (JSON)
 * @property string|null      $datos_nuevos    Estado nuevo (JSON)
 * @property string|null      $ip_address      IP del usuario
 * @property int              $duracion_ms     Duración de la operación en ms
 * @property string           $created_at      Timestamp de creación (DATETIME)
 * 
 * @property array            $datos_previos_array  Datos previos decodificados
 * @property array            $datos_nuevos_array   Datos nuevos decodificados
 * @property Usuario|null     $usuario        Relación belongsTo Usuario
 */
class AuditLog extends ActiveRecord
{
    // Enumeración de acciones
    public const ACTION_CREATE = 'CREATE';
    public const ACTION_UPDATE = 'UPDATE';
    public const ACTION_DELETE = 'DELETE';
    public const ACTION_LOGIN = 'LOGIN';
    public const ACTION_LOGOUT = 'LOGOUT';
    public const ACTION_EXPORT = 'EXPORT';
    public const ACTION_ROLLBACK = 'ROLLBACK';

    public static function tableName(): string
    {
        return '{{%audit_log}}';
    }

    public function rules(): array
    {
        return [
            // Campos requeridos
            [['accion', 'modulo', 'entidad'], 'required'],
            
            // Validación de tipos
            [['usuario_id', 'registro_id', 'duracion_ms'], 'integer'],
            [['accion'], 'in', 'range' => [
                self::ACTION_CREATE,
                self::ACTION_UPDATE,
                self::ACTION_DELETE,
                self::ACTION_LOGIN,
                self::ACTION_LOGOUT,
                self::ACTION_EXPORT,
                self::ACTION_ROLLBACK,
            ]],
            [['modulo', 'entidad'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['datos_previos', 'datos_nuevos'], 'safe'],
            
            // Validaciones específicas
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'usuario_id'     => 'Usuario',
            'accion'         => 'Acción',
            'modulo'         => 'Módulo',
            'entidad'        => 'Entidad',
            'registro_id'    => 'ID Registro',
            'datos_previos'  => 'Datos Previos',
            'datos_nuevos'   => 'Datos Nuevos',
            'ip_address'     => 'Dirección IP',
            'duracion_ms'    => 'Duración (ms)',
            'created_at'     => 'Creado',
        ];
    }

    /**
     * Relación: belongsTo Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(User::class, ['id' => 'usuario_id']);
    }

    /**
     * Obtener datos previos como array (decodificar JSON)
     */
    public function getDatos_previos_array(): ?array
    {
        if ($this->datos_previos === null) {
            return null;
        }
        return json_decode($this->datos_previos, true) ?: [];
    }

    /**
     * Obtener datos nuevos como array (decodificar JSON)
     */
    public function getDatos_nuevos_array(): ?array
    {
        if ($this->datos_nuevos === null) {
            return null;
        }
        return json_decode($this->datos_nuevos, true) ?: [];
    }

    /**
     * Prevenir modificación de registros de auditoría.
     * Solo se permite INSERT, no UPDATE ni DELETE.
     */
    public function beforeSave($insert): bool
    {
        if (!$insert) {
            throw new InvalidConfigException('Los registros de auditoría no pueden ser modificados (inmutables).');
        }
        return parent::beforeSave($insert);
    }

    /**
     * Prevenir eliminación de registros de auditoría.
     */
    public function beforeDelete(): bool
    {
        throw new InvalidConfigException('Los registros de auditoría no pueden ser eliminados (inmutables).');
    }

    /**
     * Scope: Encuentre registros por usuario
     */
    public static function findByUsuario(?int $usuarioId)
    {
        return static::find()->where(['usuario_id' => $usuarioId]);
    }

    /**
     * Scope: Encuentre registros por entidad
     */
    public static function findByEntidad(string $entidad)
    {
        return static::find()->where(['entidad' => $entidad]);
    }

    /**
     * Scope: Encuentre registros por acción
     */
    public static function findByAccion(string $accion)
    {
        return static::find()->where(['accion' => $accion]);
    }

    /**
     * Scope: Encuentre registros recientes (últimos X registros)
     */
    public static function findRecent(int $limite = 100)
    {
        return static::find()
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limite);
    }
}
