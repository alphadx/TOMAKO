<?php
declare(strict_types=1);

namespace app\components\behaviors;

use Yii;
use yii\base\Behavior;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\base\ActionEvent;

/**
 * AccessControlBehavior: verifica permisos granulares por acción.
 * 
 * Este behavior permite verificar permisos específicos del tipo "modulo.accion"
 * antes de ejecutar cada acción del controlador.
 * 
 * Uso en el controlador:
 * ```php
 * public function behaviors(): array
 * {
 *     return [
 *         'accessControl' => [
 *             'class' => AccessControlBehavior::class,
 *             'permisoBase' => 'orden', // nombre del módulo
 *         ],
 *     ];
 * }
 * ```
 * 
 * @author ID3.CL
 * @since 1.0.0
 */
class AccessControlBehavior extends Behavior
{
    /**
     * @var string Nombre base del módulo para construir el permiso (ej: 'orden', 'cita')
     */
    public string $permisoBase = '';

    /**
     * @var array Mapeo de acciones a permisos específicos.
     * Ejemplo: ['create' => 'crear', 'update' => 'editar']
     * Si no se define, se usa el nombre de la acción como sufijo.
     */
    public array $actionMap = [];

    /**
     * @var bool Permitir acceso si el permisoBase está vacío (por defecto true para retrocompatibilidad)
     */
    public bool $allowIfEmpty = true;

    /**
     * Lista de acciones que no requieren verificación de permisos.
     */
    public array $exceptActions = [];

    /**
     * @inheritdoc
     */
    public function events(): array
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction',
        ];
    }

    /**
     * Maneja el evento beforeAction para verificar permisos.
     * 
     * @param ActionEvent $event
     * @return bool
     * @throws ForbiddenHttpException
     */
    public function beforeAction(ActionEvent $event): bool
    {
        $actionId = $event->action->id;

        // Omitir verificación para acciones en exceptActions
        if (in_array($actionId, $this->exceptActions, true)) {
            return true;
        }

        // Si no hay permiso base definido y allowIfEmpty es true, permitir acceso
        if (empty($this->permisoBase)) {
            return $this->allowIfEmpty;
        }

        // Determinar el sufijo del permiso según el mapeo o nombre de acción
        $suffix = $this->actionMap[$actionId] ?? $actionId;
        
        // Construir nombre completo del permiso (ej: 'orden.crear')
        $permisoNombre = strtolower(trim("{$this->permisoBase}.{$suffix}"));

        // Verificar si el usuario tiene el permiso
        $user = Yii::$app->user->identity;
        
        if (!$user) {
            // Usuario no autenticado - redirigir al login
            Yii::$app->user->loginRequired();
            return false;
        }

        // Verificar permiso usando el método canAccess del modelo User
        if (!$user->canAccess($permisoNombre)) {
            throw new ForbiddenHttpException(
                Yii::t('app', 'No tiene permisos para realizar esta acción. Permiso requerido: {permission}', [
                    'permission' => $permisoNombre,
                ])
            );
        }

        return true;
    }

    /**
     * Configura el comportamiento con reglas dinámicas.
     * 
     * @param string $permisoBase Nombre del módulo
     * @param array $actionMap Mapeo de acciones a permisos
     * @param array $exceptActions Acciones excluidas de verificación
     * @return self
     */
    public function configure(string $permisoBase, array $actionMap = [], array $exceptActions = []): self
    {
        $this->permisoBase = $permisoBase;
        $this->actionMap = $actionMap;
        $this->exceptActions = $exceptActions;
        return $this;
    }
}
