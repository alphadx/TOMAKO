<?php
declare(strict_types=1);

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Response;
use app\models\Servicio;
use app\models\Categoria;

/**
 * CalculadoraController: Calculadora de precios de servicios (HU-027).
 * Permite cotizar servicios combinando mano de obra, repuestos y márgenes.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class CalculadoraController extends BaseController
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

    /**
     * Página principal de la calculadora.
     */
    public function actionIndex(): string
    {
        $servicios = Servicio::find()
            ->where(['status' => 1])
            ->with('categoria')
            ->orderBy('nombre')
            ->all();

        $categorias = Categoria::getCategoriasList();
        $model = new \app\models\CalculadoraForm();
        $modelCliente = new \app\models\CalculadoraForm();
        
        // Obtener parámetros del sistema
        $tasaIva = (float) \app\models\ParametroSistema::getValor('tasa_iva', 19);
        $diasValidez = (int) \app\models\ParametroSistema::getValor('dias_validez_cotizacion', 7);

        return $this->render('index', [
            'servicios' => $servicios,
            'categorias' => $categorias,
            'model' => $model,
            'modelCliente' => $modelCliente,
            'tasaIva' => $tasaIva,
            'diasValidez' => $diasValidez,
        ]);
    }

    /**
     * Calcula el precio total de un servicio con parámetros personalizados.
     * 
     * @return Response|array
     */
    public function actionCalcular(): Response|array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $post = Yii::$app->request->post();
        
        $servicioId = (int) ($post['servicio_id'] ?? 0);
        $cantidad = max(1, (int) ($post['cantidad'] ?? 1));
        $margenGanancia = max(0, min(100, (float) ($post['margen_ganancia'] ?? 20)));
        $descuento = max(0, min(100, (float) ($post['descuento'] ?? 0)));
        $incluirRepuestos = (bool) ($post['incluir_repuestos'] ?? false);
        $costoRepuestos = max(0, (float) ($post['costo_repuestos'] ?? 0));
        $porcentajeRepuestos = max(0, min(100, (float) ($post['porcentaje_repuestos'] ?? 15)));
        
        // Obtener tasa de IVA desde configuración (default 19%)
        $tasaIva = (float) \app\models\ParametroSistema::getValor('tasa_iva', 19);
        
        // Obtener servicio
        $servicio = Servicio::findOne($servicioId);
        if ($servicio === null) {
            return [
                'success' => false,
                'message' => 'Servicio no encontrado.',
            ];
        }

        // Cálculos
        $precioBaseUnitario = (float) $servicio->precio_base;
        $subtotalManoObra = $precioBaseUnitario * $cantidad;
        
        // Aplicar margen de ganancia a mano de obra
        $precioConMargen = $subtotalManoObra * (1 + $margenGanancia / 100);
        
        // Calcular repuestos
        $totalRepuestos = 0;
        if ($incluirRepuestos) {
            // Si hay costo específico de repuestos
            if ($costoRepuestos > 0) {
                $totalRepuestos = $costoRepuestos * (1 + $porcentajeRepuestos / 100);
            } else {
                // Por defecto, calcular como porcentaje del servicio
                $totalRepuestos = $subtotalManoObra * ($porcentajeRepuestos / 100);
            }
        }
        
        // Subtotal antes de descuento (NETO)
        $subtotal = $precioConMargen + $totalRepuestos;
        
        // Aplicar descuento
        $montoDescuento = $subtotal * ($descuento / 100);
        $netoConDescuento = $subtotal - $montoDescuento;
        
        // Calcular IVA
        $montoIva = $netoConDescuento * ($tasaIva / 100);
        
        // Total final con IVA
        $totalFinal = $netoConDescuento + $montoIva;
        
        // Duración estimada total
        $duracionEstimada = 0;
        if ($servicio->duracion_estimada) {
            $duracionEstimada = $servicio->duracion_estimada * $cantidad;
        }

        return [
            'success' => true,
            'data' => [
                'servicio_nombre' => $servicio->nombre,
                'servicio_codigo' => $servicio->codigo,
                'cantidad' => $cantidad,
                'precio_base_unitario' => number_format($precioBaseUnitario, 2, ',', '.'),
                'subtotal_mano_obra' => number_format($subtotalManoObra, 2, ',', '.'),
                'margen_ganancia_porcentaje' => $margenGanancia,
                'precio_con_margen' => number_format($precioConMargen, 2, ',', '.'),
                'incluye_repuestos' => $incluirRepuestos,
                'costo_repuestos' => number_format($totalRepuestos, 2, ',', '.'),
                'porcentaje_repuestos' => $porcentajeRepuestos,
                'subtotal' => number_format($subtotal, 2, ',', '.'),
                'descuento_porcentaje' => $descuento,
                'monto_descuento' => number_format($montoDescuento, 2, ',', '.'),
                'neto' => number_format($netoConDescuento, 2, ',', '.'),
                'tasa_iva' => $tasaIva,
                'monto_iva' => number_format($montoIva, 2, ',', '.'),
                'total_final' => number_format($totalFinal, 2, ',', '.'),
                'duracion_estimada_minutos' => $duracionEstimada,
                'duracion_estimada_horas' => round($duracionEstimada / 60, 2),
            ],
        ];
    }

    /**
     * Genera una cotización en formato imprimible con JWT y QR.
     */
    public function actionImprimir(): string
    {
        $post = Yii::$app->request->post();
        
        // Obtener items de la cotización acumulada
        $itemsJson = $post['items'] ?? '[]';
        
        // Decodificar JSON con manejo de errores
        $itemsData = json_decode($itemsJson, true);
        
        // Verificar si hubo error en el decode
        if (json_last_error() !== JSON_ERROR_NONE) {
            Yii::error('JSON decode error en actionImprimir: ' . json_last_error_msg() . ' - JSON recibido: ' . substr($itemsJson, 0, 500));
            throw new \yii\web\BadRequestHttpException('Datos de items inválidos: ' . json_last_error_msg());
        }
        
        if ($itemsData === null) {
            $itemsData = [];
        }
        
        $clienteNombre = trim($post['cliente_nombre'] ?? '');
        $clienteRut = trim($post['cliente_rut'] ?? '');
        $vehiculoPatente = trim($post['vehiculo_patente'] ?? '');
        
        // Valores por defecto sin depender de BD
        $tasaIva = 19.0;
        $diasValidez = 7;
        
        try {
            $tasaIva = (float) \app\models\ParametroSistema::getValor('tasa_iva', 19);
            $diasValidez = (int) \app\models\ParametroSistema::getValor('dias_validez_cotizacion', 7);
        } catch (\Throwable $e) {
            Yii::warning('No se pudo obtener parámetros del sistema, usando valores por defecto: ' . $e->getMessage());
        }
        
        if (empty($itemsData)) {
            throw new \yii\web\NotFoundHttpException('No hay items en la cotización.');
        }
        
        // Calcular totales
        $totalNeto = 0;
        $totalMontoIva = 0;
        $totalFinal = 0;
        $duracionTotal = 0;
        
        foreach ($itemsData as &$item) {
            // Asegurar que los valores sean numéricos flotantes
            $item['neto'] = is_numeric($item['neto']) ? (float) $item['neto'] : 0;
            $item['monto_iva'] = is_numeric($item['monto_iva']) ? (float) $item['monto_iva'] : 0;
            $item['total_final'] = is_numeric($item['total_final']) ? (float) $item['total_final'] : 0;
            $item['duracion_minutos'] = is_numeric($item['duracion_minutos']) ? (int) $item['duracion_minutos'] : 0;
            
            $totalNeto += $item['neto'];
            $totalMontoIva += $item['monto_iva'];
            $totalFinal += $item['total_final'];
            $duracionTotal += $item['duracion_minutos'];
        }
        
        // Fechas
        $fechaEmision = new \DateTime();
        $fechaVencimiento = clone $fechaEmision;
        $fechaVencimiento->modify("+{$diasValidez} days");
        
        // Crear payload para JWT
        $jwtPayload = [
            'iat' => $fechaEmision->getTimestamp(),
            'exp' => $fechaVencimiento->getTimestamp(),
            'data' => [
                'cliente_rut' => $clienteRut,
                'cliente_nombre' => $clienteNombre,
                'vehiculo_patente' => $vehiculoPatente,
                'cantidad_items' => count($itemsData),
                'monto_total' => round($totalFinal, 2),
                'fecha_emision' => $fechaEmision->format('Y-m-d H:i:s'),
                'fecha_vencimiento' => $fechaVencimiento->format('Y-m-d')
            ]
        ];
        
        // Obtener SECRET_KEY desde .env
        $secretKey = getenv('SECRET_KEY') ?: 'default_key_change_in_production';
        
        // Generar JWT simple (HS256)
        $jwt = $this->generarJWT($jwtPayload, $secretKey);
        
        // Obtener URL raíz de la aplicación
        $raizUrl = Yii::$app->request->hostInfo;
        
        // Generar hash único para el JWT
        $hash = \app\models\CotizacionJwt::generateHash($jwt, $raizUrl);
        
        // Guardar en la base de datos
        $cotizacionJwt = new \app\models\CotizacionJwt();
        $cotizacionJwt->hash = $hash;
        $cotizacionJwt->jwt = $jwt;
        $cotizacionJwt->raiz_url = $raizUrl;
        $cotizacionJwt->expires_at = $fechaVencimiento->getTimestamp();
        
        if (!$cotizacionJwt->save()) {
            // Log detallado del error
            Yii::error('Error al guardar cotización JWT: ' . print_r($cotizacionJwt->getErrors(), true));
            throw new \yii\web\ServerErrorHttpException('Error al guardar la cotización: ' . json_encode($cotizacionJwt->getErrors()));
        }
        
        // Generar URL de validación completa
        $urlValidacion = $cotizacionJwt->getValidacionUrl();
        
        // Generar datos para QR (URL de validación completa con el hash)
        $qrData = $urlValidacion;

        return $this->renderPartial('imprimir', [
            'itemsCotizacion' => $itemsData,
            'cantidad' => 1, // Por defecto
            'margenGanancia' => 20, // Por defecto
            'totalMargenGanancia' => 0,
            'incluirRepuestos' => false,
            'totalRepuestos' => 0,
            'porcentajeRepuestos' => 0,
            'totalNeto' => $totalNeto,
            'descuento' => 0,
            'montoDescuento' => 0,
            'netoConDescuento' => $totalNeto,
            'tasaIva' => $tasaIva,
            'montoIva' => $totalMontoIva,
            'totalFinal' => $totalFinal,
            'duracionTotal' => $duracionTotal,
            'clienteNombre' => $clienteNombre,
            'clienteRut' => $clienteRut,
            'vehiculoPatente' => $vehiculoPatente,
            'fechaCotizacion' => $fechaEmision->format('d/m/Y H:i'),
            'fechaVencimiento' => $fechaVencimiento->format('d/m/Y'),
            'diasValidez' => $diasValidez,
            'jwt' => $jwt,
            'qrData' => $qrData,
            'fechaEmisionTimestamp' => $fechaEmision->getTimestamp(),
            'fechaVencimientoTimestamp' => $fechaVencimiento->getTimestamp(),
            'hash' => $hash,
            'urlValidacion' => $urlValidacion,
        ]);
    }
    
    /**
     * Genera un token JWT simple con algoritmo HS256
     */
    private function generarJWT(array $payload, string $secret): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payloadEncoded = json_encode($payload);
        
        $base64Header = str_replace('=', '', strtr(base64_encode($header), '+/', '-_'));
        $base64Payload = str_replace('=', '', strtr(base64_encode($payloadEncoded), '+/', '-_'));
        
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64Signature = str_replace('=', '', strtr(base64_encode($signature), '+/', '-_'));
        
        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }
    
    /**
     * Valida una firma JWT comparándola con los datos reconstruidos
     * @param string $firmaEscaneada Firma extraída del QR
     * @param array $datosDatos Datos del documento para reconstruir el payload
     * @param string $secret Clave secreta para validación
     * @return bool True si la firma es válida
     */
    public static function validarFirmaJWT(string $firmaEscaneada, array $datosDatos, string $secret): bool
    {
        // Reconstruir payload con los datos del documento
        $jwtPayload = [
            'iat' => strtotime($datosDatos['fecha_emision'] ?? 'now'),
            'exp' => strtotime($datosDatos['fecha_vencimiento'] ?? '+7 days'),
            'data' => [
                'cliente_rut' => $datosDatos['cliente_rut'] ?? '',
                'cliente_nombre' => $datosDatos['cliente_nombre'] ?? '',
                'vehiculo_patente' => $datosDatos['vehiculo_patente'] ?? '',
                'cantidad_items' => $datosDatos['cantidad_items'] ?? 0,
                'monto_total' => round($datosDatos['monto_total'] ?? 0, 2),
                'fecha_emision' => $datosDatos['fecha_emision'] ?? '',
                'fecha_vencimiento' => $datosDatos['fecha_vencimiento'] ?? ''
            ]
        ];
        
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payloadEncoded = json_encode($jwtPayload);
        
        $base64Header = str_replace('=', '', strtr(base64_encode($header), '+/', '-_'));
        $base64Payload = str_replace('=', '', strtr(base64_encode($payloadEncoded), '+/', '-_'));
        
        $signatureCalculada = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64SignatureCalculada = str_replace('=', '', strtr(base64_encode($signatureCalculada), '+/', '-_'));
        
        return hash_equals($base64SignatureCalculada, $firmaEscaneada);
    }

    /**
     * Acción para validar una cotización mediante QR/hash.
     * 
     * Esta acción recibe un hash por parámetro GET (qr) y busca en la base de datos
     * el JWT completo asociado. Si lo encuentra y es válido, muestra el contenido
     * del payload para validación.
     * 
     * @return string|\yii\web\Response
     */
    public function actionValidacion(): string|\yii\web\Response
    {
        $hash = Yii::$app->request->get('qr', '');
        
        if (empty($hash)) {
            return $this->render('validacion-error', [
                'titulo' => 'Hash no proporcionado',
                'mensaje' => 'No se ha proporcionado un hash válido para validar la cotización.',
            ]);
        }

        // Buscar el registro en la base de datos
        $cotizacionJwt = \app\models\CotizacionJwt::findByHash($hash);
        
        if ($cotizacionJwt === null) {
            return $this->render('validacion-error', [
                'titulo' => 'Cotización no encontrada',
                'mensaje' => 'El hash proporcionado no corresponde a ninguna cotización registrada o ha expirado.',
            ]);
        }

        // Obtener SECRET_KEY desde .env
        $secretKey = getenv('SECRET_KEY') ?: 'default_key_change_in_production';
        
        // Validar el JWT
        $jwtValido = $this->validarJWT($cotizacionJwt->jwt, $secretKey);
        
        if (!$jwtValido) {
            return $this->render('validacion-error', [
                'titulo' => 'JWT inválido',
                'mensaje' => 'La firma del JWT no es válida. La cotización puede haber sido alterada.',
            ]);
        }

        // Decodificar el payload para mostrarlo
        $payload = $this->decodificarJWT($cotizacionJwt->jwt);
        
        // Marcar como usado (opcional, dependiendo de si quieres que sea de un solo uso)
        // $cotizacionJwt->markAsUsed();

        return $this->render('validacion-exito', [
            'cotizacionJwt' => $cotizacionJwt,
            'payload' => $payload,
            'esValido' => true,
        ]);
    }

    /**
     * Valida un token JWT completo.
     * 
     * @param string $jwt El token JWT completo.
     * @param string $secret La clave secreta.
     * @return bool True si el JWT es válido.
     */
    private function validarJWT(string $jwt, string $secret): bool
    {
        $partes = explode('.', $jwt);
        
        if (count($partes) !== 3) {
            return false;
        }

        [$base64Header, $base64Payload, $base64Signature] = $partes;
        
        // Verificar firma
        $signatureCalculada = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", $secret, true);
        $base64SignatureCalculada = str_replace('=', '', strtr(base64_encode($signatureCalculada), '+/', '-_'));
        
        if (!hash_equals($base64SignatureCalculada, $base64Signature)) {
            return false;
        }

        // Decodificar payload para verificar expiración
        $payloadJson = strtr(base64_decode($base64Payload), '-_', '+/');
        $payload = json_decode($payloadJson, true);
        
        if ($payload === null) {
            return false;
        }

        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return true;
    }

    /**
     * Decodifica un token JWT y retorna el payload.
     * 
     * @param string $jwt El token JWT completo.
     * @return array|null El payload decodificado o null si hay error.
     */
    private function decodificarJWT(string $jwt): ?array
    {
        $partes = explode('.', $jwt);
        
        if (count($partes) !== 3) {
            return null;
        }

        $base64Payload = $partes[1];
        $payloadJson = strtr(base64_decode($base64Payload), '-_', '+/');
        $payload = json_decode($payloadJson, true);
        
        return $payload ?: null;
    }
}
