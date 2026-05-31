<?php
/** @var app\models\User $user */

use yii\helpers\Url;

$resetUrl = Url::to(['/site/reset-password', 'token' => $user->password_reset_token], true);
?>
<p>Hola <?= htmlspecialchars($user->getFullName(), ENT_QUOTES, 'UTF-8') ?>,</p>
<p>Recibimos una solicitud para restablecer su contraseña en TOMAKO.</p>
<p>
    <a href="<?= htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') ?>">Haga clic aquí para restablecer su contraseña</a>
</p>
<p>Este enlace expirará automáticamente.</p>
