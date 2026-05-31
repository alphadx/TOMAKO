<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use yii\data\ActiveDataProvider;
use app\models\CierreCaja;
use app\models\Pago;

class CierreCajaService extends BaseService
{
    protected string $logCategoria = 'app.cierre_caja';

    public function abrirCaja(int $usuarioId, float $montoInicial): ?CierreCaja
    {
        return $this->executeInTransaction(function () use ($usuarioId, $montoInicial): CierreCaja {
            $abierta = CierreCaja::findOne([
                'usuario_id' => $usuarioId,
                'estado' => CierreCaja::ESTADO_ABIERTO,
            ]);

            if ($abierta !== null) {
                throw new ServiceException('Ya existe una caja abierta para este usuario.');
            }

            $cierre = new CierreCaja([
                'usuario_id' => $usuarioId,
                'fecha' => date('Y-m-d'),
                'monto_inicial' => round($montoInicial, 2),
                'estado' => CierreCaja::ESTADO_ABIERTO,
            ]);

            if (!$cierre->validate()) {
                throw new ServiceException(implode('; ', $cierre->getFirstErrors()));
            }

            $this->asegurar($cierre->save(false), 'No se pudo abrir la caja.');
            
            $this->asociarPagosPendientesAlCierre($cierre);
            
            $this->log("Caja abierta #{$cierre->id} por usuario #{$usuarioId}.");

            return $cierre;
        });
    }

    /**
     * Asocia los pagos realizados hoy que aún no tienen cierre de caja al nuevo cierre abierto.
     */
    private function asociarPagosPendientesAlCierre(CierreCaja $cierre): void
    {
        $inicio = strtotime($cierre->fecha . ' 00:00:00');
        $fin = strtotime($cierre->fecha . ' 23:59:59');

        Pago::updateAll(
            ['cierre_caja_id' => $cierre->id],
            [
                'and',
                ['in', 'estado', ['pagado', 'completado']],
                ['between', 'pagado_at', $inicio, $fin],
                ['cierre_caja_id' => null],
            ]
        );
    }

    public function cerrarCaja(int $cierreId, float $montoFinal): ?CierreCaja
    {
        return $this->executeInTransaction(function () use ($cierreId, $montoFinal): CierreCaja {
            $cierre = CierreCaja::findOne($cierreId);
            if ($cierre === null) {
                throw new ServiceException('Cierre de caja no encontrado.');
            }

            if ($cierre->estado !== CierreCaja::ESTADO_ABIERTO) {
                throw new ServiceException('La caja ya se encuentra cerrada.');
            }

            $totalPagos = (float) (Pago::find()
                ->where(['cierre_caja_id' => $cierre->id])
                ->andWhere(['in', 'estado', ['pagado', 'completado']])
                ->sum('monto') ?? 0.0);

            $esperado = round((float) $cierre->monto_inicial + $totalPagos, 2);
            $final = round($montoFinal, 2);

            $cierre->monto_esperado = $esperado;
            $cierre->monto_final = $final;
            $cierre->diferencia = round($final - $esperado, 2);
            $cierre->estado = CierreCaja::ESTADO_CERRADO;
            $cierre->closed_at = time();

            $this->asegurar($cierre->save(false), 'No se pudo cerrar la caja.');
            $this->log("Caja cerrada #{$cierre->id}. Diferencia: {$cierre->diferencia}");

            return $cierre;
        });
    }

    public function getCierreActual(int $usuarioId): ?CierreCaja
    {
        return CierreCaja::findOne([
            'usuario_id' => $usuarioId,
            'estado' => CierreCaja::ESTADO_ABIERTO,
        ]);
    }

    public function getCierresPorPeriodo(string $desde, string $hasta): ActiveDataProvider
    {
        $query = CierreCaja::find()
            ->where(['between', 'fecha', $desde, $hasta])
            ->with('usuario')
            ->orderBy(['fecha' => SORT_DESC, 'id' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * @return array<string, float>
     */
    public function getTotalesPorMetodo(string $fecha, ?int $cierreCajaId = null): array
    {
        $query = (new \yii\db\Query())
            ->select(['metodo' => 'COALESCE(mp.nombre, p.metodo_pago)', 'total' => 'SUM(p.monto)'])
            ->from(['p' => Pago::tableName()])
            ->leftJoin(['mp' => '{{%metodo_pago}}'], 'mp.id = p.metodo_pago_id')
            ->where(['in', 'p.estado', ['pagado', 'completado']]);

        if ($cierreCajaId !== null) {
            $query->andWhere(['p.cierre_caja_id' => $cierreCajaId]);
        } else {
            $inicio = strtotime($fecha . ' 00:00:00');
            $fin = strtotime($fecha . ' 23:59:59');
            $query->andWhere(['between', 'p.pagado_at', $inicio, $fin]);
        }

        $rows = $query->groupBy(['metodo'])->all();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['metodo']] = round((float) $row['total'], 2);
        }

        return $result;
    }
}
