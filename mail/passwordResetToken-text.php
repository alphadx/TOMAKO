<?php
/** @var app\models\User $user */

use yii\helpers\Url;

$resetUrl = Url::to(['/site/reset-password', 'token' => $user->password_reset_token], true);
?>
Hola <?= $user->getFullName() ?>,

Recibimos una solicitud para restablecer su contraseña en TOMAKO.

Abra este enlace para definir una nueva contraseña:
<?= $resetUrl ?>

Este enlace expirará automáticamente.
