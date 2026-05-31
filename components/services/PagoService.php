<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use app\models\Pago;
use app\models\OrdenServicio;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * PagoService: lógica de negocio para pagos de órdenes de servicio.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class PagoService extends BaseService
{
    protected string $logCategoria = 'app.pago';

    /**
     * Registra un nuevo pago para una orden de servicio.
     *
     * @param array $data Atributos del pago.
     * @return Pago|null
     */
    public function registrar(array $data): ?Pago
    {
        return $this->executeInTransaction(function () use ($data): Pago {
            $pago = new Pago();
            $pago->setAttributes($data);
            $pago->usuario_id = Yii::$app->user->id;

            if (empty($pago->estado)) {
                $pago->estado = 'pagado';
            }

            if (in_array($pago->estado, ['pagado', 'completado'], true) && empty($pago->pagado_at)) {
                $pago->pagado_at = time();
            }

            $saldoPendiente = $this->getSaldoPendiente((int) $pago->orden_id);
            if ((float) $pago->monto > $saldoPendiente + 0.0001) {
                throw new ServiceException('El monto excede el saldo pendiente de la orden.');
            }

            if (!$pago->validate()) {
                throw new ServiceException(implode('; ', $pago->getFirstErrors()));
            }

            $cierreCajaService = new CierreCajaService();
            $cierreActual = $cierreCajaService->getCierreActual((int) $pago->usuario_id);
            if ($cierreActual !== null) {
                $pago->cierre_caja_id = $cierreActual->id;
            }

            $this->asegurar($pago->save(false), 'Error al guardar el pago.');

            $this->log("Pago #{$pago->id} registrado para orden #{$pago->orden_id} — \${$pago->monto}");
            return $pago;
        });
    }

    /**
     * Marca un pago como pagado y registra la fecha/hora.
     *
     * @param int $pagoId
     * @return Pago|null
     */
    public function confirmar(int $pagoId): ?Pago
    {
        return $this->executeInTransaction(function () use ($pagoId): Pago {
            $pago = Pago::findOne($pagoId);
            if ($pago === null) {
                throw new ServiceException('Pago no encontrado.');
            }
            if ($pago->estado !== 'pendiente') {
                throw new ServiceException('Solo se pueden confirmar pagos en estado pendiente.');
            }

            $pago->estado    = 'pagado';
            $pago->pagado_at = time();
            $this->asegurar($pago->save(false), 'Error al confirmar el pago.');

            $this->log("Pago #{$pago->id} confirmado.");
            return $pago;
        });
    }

    /**
     * Anula un pago existente.
     *
     * @param int $pagoId
     * @return Pago|null
     */
    public function anular(int $pagoId): ?Pago
    {
        return $this->anularConMotivo($pagoId, null);
    }

    public function anularConMotivo(int $pagoId, ?string $motivo): ?Pago
    {
        return $this->executeInTransaction(function () use ($pagoId, $motivo): Pago {
            $pago = Pago::findOne($pagoId);
            if ($pago === null) {
                throw new ServiceException('Pago no encontrado.');
            }
            if ($pago->estado === 'anulado') {
                throw new ServiceException('El pago ya se encuentra anulado.');
            }

            $pago->estado = 'anulado';
            $pago->anulado_motivo = $motivo !== null && $motivo !== '' ? $motivo : null;
            $this->asegurar($pago->save(false), 'Error al anular el pago.');

            $this->log("Pago #{$pago->id} anulado.");
            return $pago;
        });
    }

    /**
     * KPIs para el índice de pagos.
     *
     * @return array{total_cobrado: float, pagos_hoy: int, pagos_pendientes: int, pagos_anulados: int}
     */
    public function getKpis(): array
    {
        $hoyInicio = mktime(0, 0, 0);
        $hoyFin    = mktime(23, 59, 59);

        return [
            'total_cobrado'    => (float) Pago::find()->where(['estado' => 'pagado'])->sum('monto') ?? 0.0,
            'pagos_hoy'        => (int)   Pago::find()->where(['estado' => 'pagado'])->andWhere(['between', 'pagado_at', $hoyInicio, $hoyFin])->count(),
            'pagos_pendientes' => (int)   Pago::find()->where(['estado' => 'pendiente'])->count(),
            'pagos_anulados'   => (int)   Pago::find()->where(['estado' => 'anulado'])->count(),
        ];
    }

    /**
     * Suma de montos pagados de una orden específica.
     */
    public function totalPagadoPorOrden(int $ordenId): float
    {
        return (float) Pago::find()
            ->where(['orden_id' => $ordenId])
            ->andWhere(['in', 'estado', ['pagado', 'completado']])
            ->sum('monto') ?? 0.0;
    }

    public function getSaldoPendiente(int $ordenId): float
    {
        $orden = OrdenServicio::findOne($ordenId);
        if ($orden === null) {
            return 0.0;
        }

        $saldo = round((float) $orden->total - $this->totalPagadoPorOrden($ordenId), 2);
        return $saldo > 0 ? $saldo : 0.0;
    }

    /**
     * @return Pago[]
     */
    public function getPagosPorOrden(int $ordenId): array
    {
        return Pago::find()
            ->where(['orden_id' => $ordenId])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();
    }

    public function getHistorialPorCliente(int $clienteId): ActiveDataProvider
    {
        $query = Pago::find()
            ->alias('p')
            ->innerJoin(['o' => OrdenServicio::tableName()], 'o.id = p.orden_id')
            ->where(['o.cliente_id' => $clienteId])
            ->orderBy(['p.created_at' => SORT_DESC]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);
    }

    /**
     * @return array<int, array{fecha: string, total: float, cantidad: int}>
     */
    public function getReporteIngresos(string $desde, string $hasta): array
    {
        $inicio = strtotime($desde . ' 00:00:00');
        $fin = strtotime($hasta . ' 23:59:59');

        $rows = (new \yii\db\Query())
            ->select([
                'fecha' => new Expression("DATE(FROM_UNIXTIME(pagado_at))"),
                'total' => new Expression('SUM(monto)'),
                'cantidad' => new Expression('COUNT(*)'),
            ])
            ->from(Pago::tableName())
            ->where(['in', 'estado', ['pagado', 'completado']])
            ->andWhere(['between', 'pagado_at', $inicio, $fin])
            ->groupBy(new Expression("DATE(FROM_UNIXTIME(pagado_at))"))
            ->orderBy(['fecha' => SORT_ASC])
            ->all();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'fecha' => (string) $row['fecha'],
                'total' => round((float) $row['total'], 2),
                'cantidad' => (int) $row['cantidad'],
            ];
        }

        return $data;
    }

    /**
     * @return array<int, array{metodo: string, total: float, cantidad: int}>
     */
    public function getReportePorMetodo(string $desde, string $hasta): array
    {
        $inicio = strtotime($desde . ' 00:00:00');
        $fin = strtotime($hasta . ' 23:59:59');

        $rows = (new \yii\db\Query())
            ->select([
                'metodo' => new Expression("COALESCE(mp.nombre, p.metodo_pago)"),
                'total' => new Expression('SUM(p.monto)'),
                'cantidad' => new Expression('COUNT(*)'),
            ])
            ->from(['p' => Pago::tableName()])
            ->leftJoin(['mp' => '{{%metodo_pago}}'], 'mp.id = p.metodo_pago_id')
            ->where(['in', 'p.estado', ['pagado', 'completado']])
            ->andWhere(['between', 'p.pagado_at', $inicio, $fin])
            ->groupBy(new Expression("COALESCE(mp.nombre, p.metodo_pago)"))
            ->orderBy(['total' => SORT_DESC])
            ->all();

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'metodo' => (string) $row['metodo'],
                'total' => round((float) $row['total'], 2),
                'cantidad' => (int) $row['cantidad'],
            ];
        }

        return $data;
    }

    /**
     * @param array<int, array<string, scalar>> $rows
     */
    public function exportarCsv(array $rows): string
    {
        $out = fopen('php://temp', 'wb+');
        if ($out === false) {
            return '';
        }

        fwrite($out, "\xEF\xBB\xBF");

        if (!empty($rows)) {
            fputcsv($out, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
        }

        rewind($out);
        $content = stream_get_contents($out);
        fclose($out);

        return $content !== false ? $content : '';
    }
}
