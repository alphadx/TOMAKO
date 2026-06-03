<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * Modelo de preferencias de dashboard por usuario.
 * 
 * @property int $id
 * @property int $user_id
 * @property string $widget_id
 * @property int $is_visible
 * @property int $sort_order
 * @property int $is_collapsed
 * @property int|null $created_at
 * @property int|null $updated_at
 * @property-read User $user
 */
class DashboardPreference extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%dashboard_preference}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'widget_id'], 'required'],
            [['user_id', 'sort_order', 'is_visible', 'is_collapsed', 'created_at', 'updated_at'], 'integer'],
            [['widget_id'], 'string', 'max' => 100],
            [['is_visible', 'is_collapsed'], 'default', 'value' => 1],
            [['sort_order'], 'default', 'value' => 0],
            [['user_id', 'widget_id'], 'unique', 'targetAttribute' => ['user_id', 'widget_id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'user_id' => 'Usuario',
            'widget_id' => 'Widget',
            'is_visible' => 'Visible',
            'sort_order' => 'Orden',
            'is_collapsed' => 'Colapsado',
            'created_at' => 'Creado',
            'updated_at' => 'Actualizado',
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

    /**
     * Obtiene las preferencias del usuario actual.
     * 
     * @return array<string, array{is_visible: bool, sort_order: int, is_collapsed: bool}>
     */
    public static function getUserPreferences(?int $userId = null): array
    {
        if ($userId === null) {
            $userId = Yii::$app->user->id;
        }

        if ($userId === null) {
            return [];
        }

        $preferences = self::find()
            ->where(['user_id' => $userId])
            ->orderBy(['sort_order' => SORT_ASC])
            ->asArray()
            ->all();

        $result = [];
        foreach ($preferences as $pref) {
            $result[$pref['widget_id']] = [
                'is_visible' => (bool) $pref['is_visible'],
                'sort_order' => (int) $pref['sort_order'],
                'is_collapsed' => (bool) $pref['is_collapsed'],
            ];
        }

        return $result;
    }

    /**
     * Actualiza las preferencias de widgets para un usuario.
     * 
     * @param int $userId ID del usuario
     * @param array $widgets Array de widgets con su configuración
     * @return bool
     */
    public static function updatePreferences(int $userId, array $widgets): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Eliminar preferencias existentes
            self::deleteAll(['user_id' => $userId]);

            // Insertar nuevas preferencias
            foreach ($widgets as $index => $widget) {
                $pref = new self();
                $pref->user_id = $userId;
                $pref->widget_id = $widget['id'];
                $pref->is_visible = $widget['visible'] ? 1 : 0;
                $pref->sort_order = $index;
                $pref->is_collapsed = $widget['collapsed'] ?? 0;
                
                if (!$pref->save()) {
                    $transaction->rollBack();
                    return false;
                }
            }

            $transaction->commit();
            return true;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::error('Error al actualizar preferencias de dashboard: ' . $e->getMessage(), 'app.dashboard');
            return false;
        }
    }

    /**
     * Obtiene la lista completa de widgets disponibles para el dashboard.
     * 
     * @return array<string, array{id: string, title: string, category: string}>
     */
    public static function getAvailableWidgets(): array
    {
        return [
            // KPIs principales
            'kpi_servicios_activos' => ['id' => 'kpi_servicios_activos', 'title' => 'Servicios Activos', 'category' => 'KPIs Principales'],
            'kpi_citas_hoy' => ['id' => 'kpi_citas_hoy', 'title' => 'Citas Hoy', 'category' => 'KPIs Principales'],
            'kpi_stock_bajo' => ['id' => 'kpi_stock_bajo', 'title' => 'Stock Bajo', 'category' => 'KPIs Principales'],
            'kpi_ingresos_mes' => ['id' => 'kpi_ingresos_mes', 'title' => 'Ingresos Mes', 'category' => 'KPIs Principales'],
            'kpi_trabajos_listos' => ['id' => 'kpi_trabajos_listos', 'title' => 'Trabajos Listos', 'category' => 'KPIs Principales'],
            'kpi_clientes_nuevos' => ['id' => 'kpi_clientes_nuevos', 'title' => 'Clientes Nuevos', 'category' => 'KPIs Principales'],
            'kpi_valor_inventario' => ['id' => 'kpi_valor_inventario', 'title' => 'Valor Inventario', 'category' => 'KPIs Principales'],
            
            // Indicadores adicionales (6-10)
            'kpi_tasa_entrega_tiempo' => ['id' => 'kpi_tasa_entrega_tiempo', 'title' => 'Entregas a Tiempo', 'category' => 'Indicadores Avanzados'],
            'kpi_tiempo_promedio_resolucion' => ['id' => 'kpi_tiempo_promedio_resolucion', 'title' => 'Tiempo Promedio Resolución', 'category' => 'Indicadores Avanzados'],
            'kpi_tasa_cancelacion' => ['id' => 'kpi_tasa_cancelacion', 'title' => 'Tasa Cancelación', 'category' => 'Indicadores Avanzados'],
            'kpi_rotacion_inventario' => ['id' => 'kpi_rotacion_inventario', 'title' => 'Rotación Inventario', 'category' => 'Indicadores Avanzados'],
            'kpi_tasa_no_show' => ['id' => 'kpi_tasa_no_show', 'title' => 'Ausentismo Citas', 'category' => 'Indicadores Avanzados'],
            'kpi_ingreso_promedio_orden' => ['id' => 'kpi_ingreso_promedio_orden', 'title' => 'Ingreso Promedio Orden', 'category' => 'Indicadores Avanzados'],
            'kpi_tasa_retencion_clientes' => ['id' => 'kpi_tasa_retencion_clientes', 'title' => 'Retención Clientes', 'category' => 'Indicadores Avanzados'],
            'kpi_productividad_tecnico' => ['id' => 'kpi_productividad_tecnico', 'title' => 'Productividad Técnico', 'category' => 'Indicadores Avanzados'],
            'kpi_tasa_quiebre_stock' => ['id' => 'kpi_tasa_quiebre_stock', 'title' => 'Quiebre de Stock', 'category' => 'Indicadores Avanzados'],
            
            // Indicadores avanzados (11-15)
            'kpi_frecuencia_servicio_vehiculo' => ['id' => 'kpi_frecuencia_servicio_vehiculo', 'title' => 'Frecuencia Servicio Vehículo', 'category' => 'Indicadores Avanzados'],
            'kpi_margen_bruto_servicio' => ['id' => 'kpi_margen_bruto_servicio', 'title' => 'Margen Bruto Servicio', 'category' => 'Indicadores Avanzados'],
            'kpi_tasa_morosidad' => ['id' => 'kpi_tasa_morosidad', 'title' => 'Tasa de Morosidad', 'category' => 'Indicadores Avanzados'],
            'kpi_ocupacion_agenda' => ['id' => 'kpi_ocupacion_agenda', 'title' => 'Ocupación Agenda', 'category' => 'Indicadores Avanzados'],
            
            // Widgets
            'widget_citas_hoy' => ['id' => 'widget_citas_hoy', 'title' => 'Lista Citas Hoy', 'category' => 'Widgets'],
            'widget_ordenes_prioritarias' => ['id' => 'widget_ordenes_prioritarias', 'title' => 'Órdenes Prioritarias', 'category' => 'Widgets'],
            'widget_ordenes_activas' => ['id' => 'widget_ordenes_activas', 'title' => 'Órdenes Activas', 'category' => 'Widgets'],
            'widget_alertas_stock' => ['id' => 'widget_alertas_stock', 'title' => 'Alertas de Stock', 'category' => 'Widgets'],
            'widget_accesos_rapidos' => ['id' => 'widget_accesos_rapidos', 'title' => 'Accesos Rápidos', 'category' => 'Widgets'],
        ];
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
