<?php
declare(strict_types=1);
namespace app\controllers;

use Yii;
use app\models\Cita;
use app\models\Cliente;
use app\models\InventoryItem;
use app\models\OrdenServicio;
use app\models\Tecnico;
use app\models\User;
use app\models\Vehiculo;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\ContactForm;
use app\components\services\AuthService;

/**
 * SiteController: acciones públicas y de autenticación.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class SiteController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only'  => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow'   => true,
                        'roles'   => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                    'change-language' => ['post'],
                ],
            ],
        ];
    }

    public function actions(): array
    {
        return [
            'error'   => ['class' => 'yii\web\ErrorAction'],
            'captcha' => [
                'class'           => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /** Página principal */
    public function actionIndex(): string
    {
        $stats = [
            'clientesActivos'   => $this->safeCount(Cliente::class, ['status' => 1]),
            'vehiculosActivos'  => $this->safeCount(Vehiculo::class, ['status' => 1]),
            'tecnicosActivos'   => $this->safeCount(Tecnico::class, ['status' => 1]),
            'ordenesAbiertas'   => $this->safeCount(OrdenServicio::class, ['estado' => ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega']]),
            'citasHoy'          => $this->safeCount(Cita::class, ['fecha' => date('Y-m-d')]),
            'inventarioCritico' => $this->safeInventarioCritico(),
        ];

        $proximasCitas = [];
        try {
            $proximasCitas = Cita::find()
                ->alias('ci')
                ->joinWith(['cliente cl', 'vehiculo vh'])
                ->andWhere(['>=', 'ci.fecha', date('Y-m-d')])
                ->andWhere(['in', 'ci.estado', ['pendiente', 'confirmada', 'en_progreso']])
                ->orderBy(['ci.fecha' => SORT_ASC, 'ci.hora_inicio' => SORT_ASC])
                ->limit(6)
                ->all();
        } catch (\Throwable $e) {
            Yii::warning('No se pudieron cargar próximas citas: ' . $e->getMessage(), 'app.dashboard');
        }

        $ordenesPrioritarias = [];
        try {
            $ordenesPrioritarias = OrdenServicio::find()
                ->alias('os')
                ->joinWith(['cliente cl', 'vehiculo vh'])
                ->where(['in', 'os.estado', ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega']])
                ->orderBy([
                    new Expression("FIELD(os.prioridad, 'urgente', 'alta', 'normal', 'baja')"),
                    'os.updated_at' => SORT_DESC,
                ])
                ->limit(6)
                ->all();
        } catch (\Throwable $e) {
            Yii::warning('No se pudieron cargar órdenes prioritarias: ' . $e->getMessage(), 'app.dashboard');
        }

        $quickLinks = [
            ['icon' => '📅', 'title' => 'Nueva Cita', 'description' => 'Agenda una atención para un cliente.', 'url' => ['/cita/create']],
            ['icon' => '🔧', 'title' => 'Nueva Orden', 'description' => 'Abre una orden de servicio para diagnóstico o reparación.', 'url' => ['/orden/create']],
            ['icon' => '👥', 'title' => 'Nuevo Cliente', 'description' => 'Registra un cliente y su información de contacto.', 'url' => ['/cliente/create']],
            ['icon' => '🚗', 'title' => 'Nuevo Vehículo', 'description' => 'Asocia un vehículo al cliente y su historial inicial.', 'url' => ['/vehiculo/create']],
            ['icon' => '📦', 'title' => 'Inventario', 'description' => 'Revisa stock y realiza ajustes de existencias.', 'url' => ['/inventario/index']],
            ['icon' => '👨‍🔧', 'title' => 'Técnicos', 'description' => 'Gestiona disponibilidad y especialidades del equipo.', 'url' => ['/tecnico/index']],
        ];

        return $this->render('index', [
            'stats'               => $stats,
            'proximasCitas'       => $proximasCitas,
            'ordenesPrioritarias' => $ordenesPrioritarias,
            'quickLinks'          => $quickLinks,
        ]);
    }

    private function safeCount(string $modelClass, array $condition = []): ?int
    {
        try {
            $query = $modelClass::find();
            if ($condition !== []) {
                $query->andWhere($condition);
            }
            return (int) $query->count('*');
        } catch (\Throwable $e) {
            Yii::warning(sprintf('No se pudo contar %s: %s', $modelClass, $e->getMessage()), 'app.dashboard');
            return null;
        }
    }

    private function safeInventarioCritico(): ?int
    {
        try {
            return (int) InventoryItem::find()
                ->alias('ii')
                ->where(['ii.status' => 1])
                ->andWhere(new Expression('ii.cantidad <= ii.stock_minimo'))
                ->count('*');
        } catch (\Throwable $e) {
            Yii::warning('No se pudo calcular inventario crítico: ' . $e->getMessage(), 'app.dashboard');
            return null;
        }
    }

    /**
     * Acción de login. Utiliza AuthService para autenticación real.
     */
    public function actionLogin(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $rateLimit = $this->consumeLoginRequestQuota();
        if ($rateLimit['isLimited']) {
            Yii::$app->response->statusCode = 429;
            return $this->render('login', [
                'email'             => '',
                'rememberMe'        => false,
                'error'             => Yii::t('app', 'Demasiadas solicitudes desde su IP. Espere {seconds} segundos.', ['seconds' => $rateLimit['remainingSeconds']]),
                'blockedRemaining'  => 0,
                'systemOnline'      => $this->isSystemOnline(),
            ]);
        }

        $email      = '';
        $password   = '';
        $rememberMe = false;
        $error      = null;

        if (Yii::$app->request->get('timeout') === '1') {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Su sesión expiró por inactividad. Inicie sesión nuevamente.'));
        }

        $authService = new AuthService();
        $bloqueoInfo = $authService->getBloqueoInfoPorIp(Yii::$app->request->userIP ?? '0.0.0.0');
        if ($bloqueoInfo['isBlocked']) {
            $error = Yii::t('app', 'Bloqueo temporal activo. Tiempo restante: {seconds} segundos.', ['seconds' => $bloqueoInfo['remainingSeconds']]);
        }

        if (Yii::$app->request->isPost) {
            $post       = Yii::$app->request->post();
            $email      = trim($post['email'] ?? '');
            $password   = $post['password'] ?? '';
            $rememberMe = !empty($post['rememberMe']);

            if ($authService->login($email, $password, $rememberMe)) {
                $returnUrl = Yii::$app->user->getReturnUrl();
                if (is_string($returnUrl) && $returnUrl !== '') {
                    $returnHost = parse_url($returnUrl, PHP_URL_HOST);
                    $requestHost = Yii::$app->request->hostName;
                    $returnPath = parse_url($returnUrl, PHP_URL_PATH) ?? '';

                    $isExternalHost = $returnHost === 'localhost'
                        || ($returnHost !== null && $returnHost !== $requestHost);

                    // Rutas de API/AJAX que nunca deben ser returnUrl (p.ej. polling de notificaciones).
                    $isApiPath = (bool) preg_match(
                        '#^/(notificaciones/contador|notificaciones/marcar|notificacion/contador-json|notificacion/marcar)#',
                        $returnPath
                    );

                    if ($isExternalHost || $isApiPath) {
                        Yii::$app->user->setReturnUrl(['/site/index']);
                    }
                }

                return $this->goBack(Yii::$app->homeUrl);
            }

            $error = $authService->getPrimerError();
            $bloqueoInfo = $authService->getBloqueoInfoPorIp(Yii::$app->request->userIP ?? '0.0.0.0');
        }

        return $this->render('login', [
            'email'             => $email,
            'rememberMe'        => $rememberMe,
            'error'             => $error,
            'blockedRemaining'  => $bloqueoInfo['remainingSeconds'],
            'systemOnline'      => $this->isSystemOnline(),
        ]);
    }

    /**
     * Acción de logout. Utiliza AuthService.
     */
    public function actionLogout(): Response
    {
        $authService = new AuthService();
        $authService->logout();
        
        // Limpiar cualquier dato de sesión restante
        Yii::$app->session->destroy();
        
        return $this->redirect(['/login']);
    }

    public function actionRequestPasswordReset(): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'login';
        $error = null;
        $success = null;

        if (Yii::$app->request->isPost) {
            $email = strtolower(trim((string) Yii::$app->request->post('email', '')));
            $user = User::findByEmail($email);

            if ($user !== null && $user->activo === 1) {
                $user->generatePasswordResetToken();
                $user->save(false, ['password_reset_token', 'updated_at']);

                try {
                    Yii::$app->mailer
                        ->compose('passwordResetToken', ['user' => $user])
                        ->setTo($user->email)
                        ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
                        ->setSubject('Recuperación de contraseña - TOMAKO')
                        ->send();
                } catch (\Throwable $e) {
                    Yii::warning('No se pudo enviar correo de recuperación: ' . $e->getMessage(), 'app.auth');
                }
            }

            // Respuesta genérica por seguridad.
            $success = 'Si el correo existe, recibirá un enlace de recuperación en breve.';
        }

        return $this->render('request-password-reset', [
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function actionResetPassword(string $token): Response|string
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $this->layout = 'login';
        $error = null;
        $success = null;

        if ($token === '') {
            throw new BadRequestHttpException('Token inválido.');
        }

        if (Yii::$app->request->isPost) {
            $newPassword = (string) Yii::$app->request->post('new_password', '');
            $confirmPassword = (string) Yii::$app->request->post('confirm_password', '');

            if ($newPassword !== $confirmPassword) {
                $error = 'Las contraseñas no coinciden.';
            } elseif (strlen($newPassword) < 10) {
                $error = 'Mínimo 10 caracteres.';
            } else {
                $authService = new AuthService();
                if ($authService->resetPasswordByToken($token, $newPassword)) {
                    $success = 'Su contraseña fue restablecida correctamente. Ya puede iniciar sesión.';
                } else {
                    $error = $authService->getPrimerError();
                }
            }
        }

        return $this->render('reset-password', [
            'token' => $token,
            'error' => $error,
            'success' => $success,
        ]);
    }

    public function actionChangeLanguage(): Response
    {
        $lang = (string) Yii::$app->request->post('lang', Yii::$app->params['defaultLanguage'] ?? 'es-CL');
        $supported = Yii::$app->params['supportedLanguages'] ?? ['es-CL'];

        if (!in_array($lang, $supported, true)) {
            $lang = Yii::$app->params['defaultLanguage'] ?? 'es-CL';
        }

        Yii::$app->language = $lang;
        Yii::$app->session->set('appLanguage', $lang);

        return $this->goBack(Yii::$app->homeUrl);
    }

    /** Página de contacto */
    public function actionContact(): Response|string
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');
            return $this->refresh();
        }
        return $this->render('contact', ['model' => $model]);
    }

    /** Página Acerca de */
    public function actionAbout(): string
    {
        return $this->render('about');
    }

    private function consumeLoginRequestQuota(): array
    {
        $ip = Yii::$app->request->userIP ?? '0.0.0.0';
        $bucket = (int) floor(time() / 60);
        $key = 'login_rl_' . md5($ip . '_' . $bucket);
        $cache = Yii::$app->cache;

        $current = (int) $cache->get($key);
        $current++;
        $cache->set($key, $current, 65);

        $remainingSeconds = 60 - (time() % 60);

        return [
            'isLimited' => $current > 20,
            'remainingSeconds' => $remainingSeconds,
        ];
    }

    private function isSystemOnline(): bool
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->queryScalar();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
