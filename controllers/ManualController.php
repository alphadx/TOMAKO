<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\db\Query;
use yii\filters\AccessControl;
use yii\web\ForbiddenHttpException;

class ManualController extends BaseController
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $this->ensureManualAccess();

        return $this->render('index');
    }

    private function ensureManualAccess(): void
    {
        $identity = Yii::$app->user->identity;
        if ($identity === null) {
            return;
        }

        if ((int) $identity->rol_id === 1) {
            return;
        }

        $query = (new Query())
            ->from('{{%rol_permiso}} rp')
            ->innerJoin('{{%permiso}} p', 'p.id = rp.permiso_id')
            ->where(['rp.rol_id' => (int) $identity->rol_id]);

        // Si el rol no tiene permisos configurados, mantenemos el comportamiento permisivo del sistema.
        $hasAnyPermission = (clone $query)->exists();
        if (!$hasAnyPermission) {
            return;
        }

        $hasPermission = (clone $query)
            ->andWhere(['in', 'p.nombre', ['manual.view', 'manual.ver']])
            ->exists();

        if (!$hasPermission) {
            throw new ForbiddenHttpException('No tiene permisos para acceder al manual de usuario.');
        }
    }
}
