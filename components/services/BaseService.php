<?php

declare(strict_types=1);

namespace app\components\services;

use Yii;
use yii\base\Component;
use yii\db\Exception as DbException;

/**
 * Clase base para todos los servicios de negocio de TOMAKO.
 *
 * Provee:
 * - Wrapper de transacciones con rollback automático.
 * - Colección de errores estructurada.
 * - Logging centralizado con categoría por servicio.
 *
 * Uso:
 * ```php
 * class ClienteService extends BaseService
 * {
 *     public function crear(array $datos): ?Cliente
 *     {
 *         return $this->executeInTransaction(function () use ($datos): ?Cliente {
 *             // lógica de negocio
 *         });
 *     }
 * }
 * ```
 *
 * @author ID3.CL
 * @since 1.0.0
 */
abstract class BaseService extends Component
{
    /**
     * Errores de negocio acumulados durante la última operación.
     *
     * @var string[]
     */
    protected array $errors = [];

    /**
     * Categoría de log para este servicio (para Yii::error/info).
     * Sobrescribir en subclases para mayor granularidad.
     */
    protected string $logCategoria = 'app.service';

    /**
     * Ejecuta un callable dentro de una transacción de base de datos.
     * En caso de excepción hace rollback y registra el error.
     *
     * @template T
     * @param callable(): T $operacion  Función con la lógica a ejecutar.
     * @return T|null  Resultado de la operación, o null en caso de error.
     */
    public function executeInTransaction(callable $operacion): mixed
    {
        $this->errors = [];
        $transaction  = Yii::$app->db->beginTransaction();

        try {
            $resultado = $operacion();
            $transaction->commit();
            return $resultado;
        } catch (ServiceException $e) {
            $transaction->rollBack();
            $this->errors[] = $e->getMessage();
            Yii::warning($e->getMessage(), $this->logCategoria);
            return null;
        } catch (DbException $e) {
            $transaction->rollBack();
            $errorMsg = $this->mapearErrorDatabase($e->getMessage());
            $this->errors[] = $errorMsg;
            Yii::error('DbException en ' . static::class . ': ' . $e->getMessage(), $this->logCategoria);
            return null;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            $errorMsg = $this->mapearErrorGeneral($e);
            $this->errors[] = $errorMsg;
            Yii::error(
                'Error inesperado en ' . static::class . ': ' . $e->getMessage() . "\n" . $e->getTraceAsString(),
                $this->logCategoria
            );
            return null;
        }
    }

    /**
     * Mapea errores de base de datos a mensajes amigables.
     */
    protected function mapearErrorDatabase(string $errorDb): string
    {
        // Error de clave única duplicada
        if (stripos($errorDb, 'duplicate') !== false || stripos($errorDb, 'única') !== false) {
            return 'Ya existe un registro con los mismos datos. Verifique la información e intente nuevamente.';
        }
        
        // Error de clave foránea
        if (stripos($errorDb, 'foreign key') !== false || stripos($errorDb, 'clave foránea') !== false) {
            return 'La referencia al registro no es válida. Verifique que el registro relacionado exista.';
        }
        
        // Error de conexión
        if (stripos($errorDb, 'connection') !== false || stripos($errorDb, 'conexión') !== false) {
            return 'No se pudo conectar a la base de datos. Por favor intente más tarde.';
        }
        
        // Error de timeout
        if (stripos($errorDb, 'timeout') !== false || stripos($errorDb, 'tiempo de espera') !== false) {
            return 'La operación tardó demasiado. Por favor intente nuevamente.';
        }
        
        // Error de permisos
        if (stripos($errorDb, 'permission') !== false || stripos($errorDb, 'denied') !== false) {
            return 'No tiene permisos para realizar esta operación en la base de datos.';
        }
        
        return 'Error de base de datos. Por favor intente nuevamente.';
    }

    /**
     * Mapea errores generales a mensajes amigables según el contexto.
     */
    protected function mapearErrorGeneral(\Throwable $e): string
    {
        $mensaje = $e->getMessage();
        
        // Errores de validación que llegaron como excepción general
        if (stripos($mensaje, 'validación') !== false) {
            return 'Hubo errores en la validación de los datos. Revise los campos marcados.';
        }
        
        // Errores de archivo/foto
        if (stripos($mensaje, 'archivo') !== false || stripos($mensaje, 'file') !== false) {
            return 'Error al procesar el archivo. Verifique el formato y tamaño.';
        }
        
        // Errores de memoria
        if (stripos($mensaje, 'memory') !== false || stripos($mensaje, 'memoria') !== false) {
            return 'La operación requiere demasiada memoria. Intente con un archivo más pequeño.';
        }
        
        return 'Error inesperado. Por favor intente nuevamente. Si el problema persiste, contacte al administrador.';
    }

    /**
     * Indica si la última operación generó errores.
     */
    public function tieneErrores(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Retorna los errores acumulados de la última operación.
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna el primer error como string, o cadena vacía si no hay errores.
     */
    public function getPrimerError(): string
    {
        return $this->errors[0] ?? '';
    }

    /**
     * Agrega un error de negocio a la lista.
     *
     * @param string $mensaje  Mensaje de error legible por el usuario.
     */
    protected function agregarError(string $mensaje): void
    {
        $this->errors[] = $mensaje;
    }

    /**
     * Lanza ServiceException si la condición es falsa.
     *
     * @param bool   $condicion  Condición que debe ser verdadera.
     * @param string $mensaje    Mensaje de error si la condición falla.
     * @throws ServiceException
     */
    protected function asegurar(bool $condicion, string $mensaje): void
    {
        if (!$condicion) {
            throw new ServiceException($mensaje);
        }
    }

    /**
     * Registra un mensaje informativo en el log del servicio.
     *
     * @param string $mensaje  Mensaje descriptivo de la operación.
     */
    protected function log(string $mensaje): void
    {
        Yii::info('[' . static::class . '] ' . $mensaje, $this->logCategoria);
    }
}
