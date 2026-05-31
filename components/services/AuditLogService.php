<?php

declare(strict_types=1);

namespace app\components\services;

use app\models\AuditLog;
use app\models\search\AuditLogSearch;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use Yii;

/**
 * Servicio de Auditoría - Lógica de negocio para AuditLog
 *
 * Proporciona métodos para:
 * - Obtener listados con filtros
 * - Calcular diffs (cambios)
 * - Búsquedas avanzadas
 * - Exportaciones
 * - Archivado de logs antiguos
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class AuditLogService
{
    /**
     * Obtener listado de auditoría con filtros
     */
    public function getListado(array $filters = []): ActiveDataProvider
    {
        $search = new AuditLogSearch();
        return $search->search($filters);
    }

    /**
     * Obtener un registro de auditoría por ID
     */
    public function getDetalle(int $id): ?AuditLog
    {
        return AuditLog::findOne($id);
    }

    /**
     * Calcular diff (cambios) entre datos previos y nuevos
     *
     * Retorna array con estructura:
     * [
     *     'previos'  => [...],
     *     'nuevos'   => [...],
     *     'cambios'  => [
     *         'campo1' => ['anterior' => 'valor1', 'nuevo' => 'valorNuevo', 'cambió' => true],
     *         'campo2' => [...],
     *     ]
     * ]
     */
    public function getDiff(int $auditLogId): ?array
    {
        $log = $this->getDetalle($auditLogId);
        if (!$log) {
            return null;
        }

        $previos = $log->datos_previos_array ?? [];
        $nuevos = $log->datos_nuevos_array ?? [];

        // Construir array de cambios detallados por campo
        $cambios = [];
        $todosCampos = array_unique(array_merge(array_keys($previos), array_keys($nuevos)));

        foreach ($todosCampos as $campo) {
            $anterior = $previos[$campo] ?? '(no existía)';
            $nuevo = $nuevos[$campo] ?? '(eliminado)';
            
            $cambios[$campo] = [
                'anterior' => $anterior,
                'nuevo' => $nuevo,
                'cambió' => $anterior !== $nuevo,
                'tipo' => $this->detectarTipoCampo($anterior, $nuevo),
            ];
        }

        return [
            'log_id' => $auditLogId,
            'accion' => $log->accion,
            'entidad' => $log->entidad,
            'fecha' => $log->created_at,
            'usuario' => $log->usuario ? $log->usuario->nombre : 'Sistema',
            'previos' => $previos,
            'nuevos' => $nuevos,
            'cambios' => $cambios,
        ];
    }

    /**
     * Búsqueda de texto en datos_previos y datos_nuevos
     */
    public function searchByValue(string $valor, array $filtros = []): ActiveDataProvider
    {
        $search = new AuditLogSearch();
        $search->buscar_valor = $valor;
        
        // Aplicar filtros adicionales
        foreach ($filtros as $key => $value) {
            if (property_exists($search, $key)) {
                $search->$key = $value;
            }
        }

        return $search->search([]);
    }

    /**
     * Obtener historial de cambios de una entidad específica
     */
    public function getHistorialEntidad(string $entidad, int $registroId): ActiveDataProvider
    {
        $query = AuditLog::find()
            ->where(['entidad' => $entidad, 'registro_id' => $registroId])
            ->orderBy(['created_at' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 100],
        ]);
    }

    /**
     * Obtener timline (cronología) de cambios
     */
    public function getTimeline(string $entidad, int $registroId): array
    {
        $logs = AuditLog::find()
            ->where(['entidad' => $entidad, 'registro_id' => $registroId])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        $timeline = [];
        foreach ($logs as $log) {
            $timeline[] = [
                'fecha' => $log->created_at,
                'accion' => $log->accion,
                'usuario' => $log->usuario ? $log->usuario->nombre : 'Sistema',
                'ip' => $log->ip_address,
                'duracion_ms' => $log->duracion_ms,
                'cambios_resumen' => $this->getResumenCambios($log),
            ];
        }

        return $timeline;
    }

    /**
     * Exportar logs a CSV
     */
    public function exportarCsv(array $filtros = []): string
    {
        $logs = AuditLog::find();

        // Aplicar filtros
        if (!empty($filtros['usuario_id'])) {
            $logs->andWhere(['usuario_id' => $filtros['usuario_id']]);
        }
        if (!empty($filtros['entidad'])) {
            $logs->andWhere(['entidad' => $filtros['entidad']]);
        }
        if (!empty($filtros['accion'])) {
            $logs->andWhere(['accion' => $filtros['accion']]);
        }
        if (!empty($filtros['fecha_desde'])) {
            $logs->andWhere(['>=', 'created_at', $filtros['fecha_desde']]);
        }
        if (!empty($filtros['fecha_hasta'])) {
            $logs->andWhere(['<=', 'created_at', $filtros['fecha_hasta']]);
        }

        $logs->orderBy(['created_at' => SORT_DESC])->limit(10000);
        $audits = $logs->all();

        // Construir CSV
        $csv = "ID,Usuario,Acción,Módulo,Entidad,Registro ID,IP Address,Duración (ms),Fecha Creación,Datos Previos,Datos Nuevos\r\n";

        foreach ($audits as $log) {
            $usuario = $log->usuario ? $log->usuario->nombre : 'Sistema';
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%d,%s,\"%s\",\"%s\"\r\n",
                $log->id,
                $usuario,
                $log->accion,
                $log->modulo,
                $log->entidad,
                $log->registro_id ?? '',
                $log->ip_address ?? '',
                $log->duracion_ms,
                $log->created_at,
                str_replace('"', '""', $log->datos_previos ?? ''),
                str_replace('"', '""', $log->datos_nuevos ?? '')
            );
        }

        return $csv;
    }

    /**
     * Archivar logs antiguos (> N días) a la tabla archive_log
     */
    public function archivarAntiguos(int $dias = 365): int
    {
        $fecha = date('Y-m-d H:i:s', strtotime("-$dias days"));
        
        $logsParchivar = AuditLog::find()
            ->where(['<', 'created_at', $fecha])
            ->all();

        $cantidad = 0;

        foreach ($logsParchivar as $log) {
            $archiveLog = new \app\models\ArchiveLog([
                'audit_log_id' => $log->id,
                'usuario_id' => $log->usuario_id,
                'accion' => $log->accion,
                'modulo' => $log->modulo,
                'entidad' => $log->entidad,
                'registro_id' => $log->registro_id,
                'datos_previos' => $log->datos_previos,
                'datos_nuevos' => $log->datos_nuevos,
                'ip_address' => $log->ip_address,
                'duracion_ms' => $log->duracion_ms,
                'original_created_at' => $log->created_at,
            ]);

            if ($archiveLog->save(false)) {
                // Eliminar del audit_log original (si está permitido)
                // Por ahora solo archiva, no elimina
                $cantidad++;
            }
        }

        Yii::info("Archivados $cantidad logs de auditoría de más de $dias días");
        return $cantidad;
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public function getEstadisticas(): array
    {
        return [
            'total_logs' => AuditLog::find()->count(),
            'por_accion' => $this->contarPorAccion(),
            'por_entidad' => $this->contarPorEntidad(),
            'por_usuario' => $this->contarPorUsuario(),
            'ultimos_7_dias' => $this->contarUltimos7Dias(),
        ];
    }

    /**
     * Contar logs por tipo de acción
     */
    private function contarPorAccion(): array
    {
        $resultados = Yii::$app->db->createCommand(
            'SELECT accion, COUNT(*) as cantidad FROM {{%audit_log}} GROUP BY accion'
        )->queryAll();

        $resultado = [];
        foreach ($resultados as $row) {
            $resultado[$row['accion']] = (int) $row['cantidad'];
        }
        return $resultado;
    }

    /**
     * Contar logs por entidad
     */
    private function contarPorEntidad(): array
    {
        $resultados = Yii::$app->db->createCommand(
            'SELECT entidad, COUNT(*) as cantidad FROM {{%audit_log}} GROUP BY entidad ORDER BY cantidad DESC LIMIT 10'
        )->queryAll();

        $resultado = [];
        foreach ($resultados as $row) {
            $resultado[$row['entidad']] = (int) $row['cantidad'];
        }
        return $resultado;
    }

    /**
     * Contar logs por usuario
     */
    private function contarPorUsuario(): array
    {
        $resultados = Yii::$app->db->createCommand(
            'SELECT usuario_id, COUNT(*) as cantidad FROM {{%audit_log}} 
             WHERE usuario_id IS NOT NULL 
             GROUP BY usuario_id 
             ORDER BY cantidad DESC 
             LIMIT 10'
        )->queryAll();

        $resultado = [];
        foreach ($resultados as $row) {
            $resultado[$row['usuario_id']] = (int) $row['cantidad'];
        }
        return $resultado;
    }

    /**
     * Contar logs de los últimos 7 días
     */
    private function contarUltimos7Dias(): int
    {
        $fecha = date('Y-m-d H:i:s', strtotime('-7 days'));
        return AuditLog::find()
            ->where(['>=', 'created_at', $fecha])
            ->count();
    }

    /**
     * Detectar el tipo de campo (string, número, fecha)
     */
    private function detectarTipoCampo($anterior, $nuevo): string
    {
        if (is_numeric($anterior) || is_numeric($nuevo)) {
            return 'número';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $anterior) || preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $nuevo)) {
            return 'fecha';
        }
        return 'texto';
    }

    /**
     * Obtener resumen de cambios para un log
     */
    private function getResumenCambios(AuditLog $log): string
    {
        if ($log->accion === AuditLog::ACTION_CREATE) {
            return 'Creado';
        }
        if ($log->accion === AuditLog::ACTION_DELETE) {
            return 'Eliminado';
        }
        if ($log->accion === AuditLog::ACTION_UPDATE) {
            $previos = $log->datos_previos_array ?? [];
            $nuevos = $log->datos_nuevos_array ?? [];
            
            $cambios = [];
            foreach ($previos as $campo => $valor) {
                if (($nuevos[$campo] ?? null) !== $valor) {
                    $cambios[] = $campo;
                }
            }

            return 'Modificados: ' . implode(', ', $cambios);
        }

        return $log->accion;
    }
}
