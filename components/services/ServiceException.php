<?php

declare(strict_types=1);

namespace app\components\services;

/**
 * Excepción de negocio para los servicios de TOMAKO.
 * Se usa para errores controlados (reglas de negocio, validaciones de dominio)
 * que no deben exponer información técnica al usuario.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class ServiceException extends \RuntimeException
{
    public function __construct(string $mensaje, int $codigo = 0, ?\Throwable $anterior = null)
    {
        parent::__construct($mensaje, $codigo, $anterior);
    }
}
