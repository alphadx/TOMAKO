<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Modelo ArchiveLog - Archivo de Registros de Auditoría Históricos.
 * 
 * Este modelo mapea la tabla {{%archive_log}} y proporciona almacenamiento
 * de logs antiguos (> 365 días) para optimizar performance en la tabla
 * principal audit_log, manteniendo histórico completo con acceso rápido.
 *
 * @property int|null         $id                    ID único (BigInt)
 * @property int              $audit_log_id          FK a audit_log (original)
 * @property int|null         $usuario_id            FK a usuario (desnormalizado)
 * @property string           $accion                Acción: CREATE, UPDATE, DELETE, LOGIN, LOGOUT, EXPORT, ROLLBACK
 * @property string           $modulo                Módulo afectado
 * @property string           $entidad               Nombre de la entidad/tabla
 * @property int|null         $registro_id           ID del registro afectado
 * @property string|null      $datos_previos        Estado anterior (JSON)
 * @property string|null      $datos_nuevos         Estado nuevo (JSON)
 * @property string|null      $ip_address            IP del usuario
 * @property int              $duracion_ms           Duración de la operación
 * @property string           $original_created_at   Fecha original en audit_log
 * @property string           $archivado_at          Fecha cuando fue archivado
 * 
 * @property AuditLog|null    $auditLog              Relación belongsTo AuditLog
 * @property Usuario|null     $usuario               Relación belongsTo Usuario
 */
class ArchiveLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%archive_log}}';
    }

    public function rules(): array
    {
        return [
            // Campos requeridos
            [['audit_log_id', 'accion', 'modulo', 'entidad', 'original_created_at'], 'required'],
            
            // Validación de tipos
            [['audit_log_id', 'usuario_id', 'registro_id', 'duracion_ms'], 'integer'],
            [['accion'], 'in', 'range' => [
                AuditLog::ACTION_CREATE,
                AuditLog::ACTION_UPDATE,
                AuditLog::ACTION_DELETE,
                AuditLog::ACTION_LOGIN,
                AuditLog::ACTION_LOGOUT,
                AuditLog::ACTION_EXPORT,
                AuditLog::ACTION_ROLLBACK,
            ]],
            [['modulo', 'entidad'], 'string', 'max' => 100],
            [['ip_address'], 'string', 'max' => 45],
            [['datos_previos', 'datos_nuevos'], 'safe'],
            
            // Validaciones de FK
            [['audit_log_id'], 'exist', 'skipOnError' => true, 'targetClass' => AuditLog::class, 'targetAttribute' => ['audit_log_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'                  => 'ID',
            'audit_log_id'        => 'Log Original',
            'usuario_id'          => 'Usuario',
            'accion'              => 'Acción',
            'modulo'              => 'Módulo',
            'entidad'             => 'Entidad',
            'registro_id'         => 'ID Registro',
            'datos_previos'       => 'Datos Previos',
            'datos_nuevos'        => 'Datos Nuevos',
            'ip_address'          => 'Dirección IP',
            'duracion_ms'         => 'Duración (ms)',
            'original_created_at' => 'Fecha Original',
            'archivado_at'        => 'Archivado',
        ];
    }

    /**
     * Relación: belongsTo AuditLog
     */
    public function getAuditLog()
    {
        return $this->hasOne(AuditLog::class, ['id' => 'audit_log_id']);
    }

    /**
     * Relación: belongsTo Usuario
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
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
     * Scope: Encuentre registros archivados por usuario
     */
    public static function findByUsuario(?int $usuarioId)
    {
        return static::find()->where(['usuario_id' => $usuarioId]);
    }

    /**
     * Scope: Encuentre registros por entidad archivada
     */
    public static function findByEntidad(string $entidad)
    {
        return static::find()->where(['entidad' => $entidad]);
    }

    /**
     * Scope: Encuentre registros archivados en rango de fechas
     */
    public static function findByDateRange(string $desde, string $hasta)
    {
        return static::find()
            ->where(['>=', 'original_created_at', $desde])
            ->andWhere(['<=', 'original_created_at', $hasta]);
    }
}
