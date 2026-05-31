<?php
declare(strict_types=1);

namespace app\components\services;

use Yii;
use yii\web\UploadedFile;
use app\models\Vehiculo;
use app\models\Cliente;
use app\models\OrdenServicio;

/**
 * VehiculoService: lógica de negocio para vehículos del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class VehiculoService extends BaseService
{
    protected string $logCategoria = 'app.vehiculo';

    /**
     * Crea un nuevo vehículo.
     *
     * @param array             $data Datos del formulario.
     * @param UploadedFile|null $foto Archivo de foto subido.
     * @return Vehiculo|null
     */
    public function create(array $data, ?UploadedFile $foto = null): ?Vehiculo
    {
        return $this->executeInTransaction(function () use ($data, $foto): Vehiculo {
            $vehiculo = new Vehiculo();
            $vehiculo->patente    = $data['patente']   ?? '';
            
            // Manejar marca_id y modelo_id (Select2) o marca y modelo (texto)
            if (!empty($data['marca_id'])) {
                $vehiculo->marca_id = (int) $data['marca_id'];
                // Obtener nombre de marca desde la BD
                $marca = \app\models\Marca::findOne($data['marca_id']);
                if ($marca) {
                    $vehiculo->marca = $marca->nombre;
                }
            } elseif (!empty($data['marca'])) {
                $vehiculo->marca = $data['marca'];
            }
            
            if (!empty($data['modelo_id'])) {
                $vehiculo->modelo_id = (int) $data['modelo_id'];
                // Obtener nombre de modelo desde la BD
                $modelo = \app\models\Modelo::findOne($data['modelo_id']);
                if ($modelo) {
                    $vehiculo->modelo = $modelo->nombre;
                }
            } elseif (!empty($data['modelo'])) {
                $vehiculo->modelo = $data['modelo'];
            }
            
            $vehiculo->anio       = (int) ($data['anio'] ?? 0);
            $vehiculo->vin        = !empty($data['vin'])       ? strtoupper(trim($data['vin'])) : null;
            $vehiculo->cliente_id = (int) ($data['cliente_id'] ?? 0);
            $vehiculo->ultimo_km  = (int) ($data['ultimo_km']  ?? 0);
            $vehiculo->status     = (int) ($data['status'] ?? 1);

            if (!$vehiculo->validate()) {
                // Mostrar errores de validación específicos
                $errores = [];
                foreach ($vehiculo->getFirstErrors() as $atributo => $error) {
                    $errores[] = $this->mapearErrorAtributo($atributo, $error);
                }
                throw new ServiceException(implode('; ', $errores));
            }
            $this->asegurar($vehiculo->save(false), 'Error al guardar el vehículo en la base de datos.');

            if ($foto !== null) {
                $vehiculo->foto_path = $this->handleFoto($vehiculo, $foto);
                $vehiculo->save(false, ['foto_path']);
            }

            $this->log("Vehículo creado: #{$vehiculo->id} ({$vehiculo->patente})");
            return $vehiculo;
        });
    }

    /**
     * Mapea errores de atributos a mensajes más amigables.
     */
    private function mapearErrorAtributo(string $atributo, string $error): string
    {
        $mapeo = [
            'patente' => [
                'La patente ya ha sido registrada.' => 'La patente ingresada ya está registrada en el sistema.',
                'Patente no puede estar en blanco.' => 'El campo patente es obligatorio.',
                'Patente debe tener entre 4 y 20 caracteres.' => 'La patente debe tener entre 4 y 20 caracteres.',
            ],
            'marca' => [
                'Marca no puede estar en blanco.' => 'El campo marca es obligatorio.',
            ],
            'modelo' => [
                'Modelo no puede estar en blanco.' => 'El campo modelo es obligatorio.',
            ],
            'anio' => [
                'Año no puede estar en blanco.' => 'El campo año es obligatorio.',
                'Año debe ser un valor válido.' => 'El año ingresado no es válido.',
            ],
            'cliente_id' => [
                'Cliente no puede estar en blanco.' => 'Debe seleccionar un cliente.',
                'Cliente no válido.' => 'El cliente seleccionado no es válido.',
            ],
            'vin' => [
                'VIN debe tener exactamente 17 caracteres.' => 'El VIN debe tener exactamente 17 caracteres.',
                'VIN contiene caracteres inválidos.' => 'El VIN contiene caracteres inválidos.',
            ],
        ];

        if (isset($mapeo[$atributo][$error])) {
            return $mapeo[$atributo][$error];
        }

        // Si hay una coincidencia parcial en el mensaje
        foreach (($mapeo[$atributo] ?? []) as $busqueda => $mensaje) {
            if (stripos($error, $busqueda) !== false) {
                return $mensaje;
            }
        }

        return $error;
    }

    /**
     * Actualiza un vehículo existente.
     *
     * @param Vehiculo          $v    Instancia a actualizar.
     * @param array             $data Datos del formulario.
     * @param UploadedFile|null $foto Nueva foto.
     * @return Vehiculo|null
     */
    public function update(Vehiculo $v, array $data, ?UploadedFile $foto = null): ?Vehiculo
    {
        return $this->executeInTransaction(function () use ($v, $data, $foto): Vehiculo {
            $kmAnterior = (int) $v->ultimo_km;

            if (isset($data['patente']))                  $v->patente    = $data['patente'];
            
            // Manejar marca_id y modelo_id (Select2) o marca y modelo (texto)
            if (!empty($data['marca_id'])) {
                $v->marca_id = (int) $data['marca_id'];
                $marca = \app\models\Marca::findOne($data['marca_id']);
                if ($marca) {
                    $v->marca = $marca->nombre;
                }
            } elseif (isset($data['marca'])) {
                $v->marca = $data['marca'];
            }
            
            if (!empty($data['modelo_id'])) {
                $v->modelo_id = (int) $data['modelo_id'];
                $modelo = \app\models\Modelo::findOne($data['modelo_id']);
                if ($modelo) {
                    $v->modelo = $modelo->nombre;
                }
            } elseif (isset($data['modelo'])) {
                $v->modelo = $data['modelo'];
            }
            
            if (isset($data['anio']))                     $v->anio       = (int) $data['anio'];
            if (array_key_exists('vin', $data))           $v->vin        = !empty($data['vin']) ? strtoupper(trim($data['vin'])) : null;
            if (isset($data['cliente_id']))               $v->cliente_id = (int) $data['cliente_id'];
            if (isset($data['ultimo_km'])) {
                $nuevoKm = (int) $data['ultimo_km'];
                if ($nuevoKm < $kmAnterior) {
                    throw new ServiceException('El kilometraje no puede ser menor al último registrado.');
                }
                $v->ultimo_km = $nuevoKm;
            }
            if (isset($data['status']))                   $v->status     = (int) $data['status'];

            if (!$v->validate()) {
                // Mostrar errores de validación específicos
                $errores = [];
                foreach ($v->getFirstErrors() as $atributo => $error) {
                    $errores[] = $this->mapearErrorAtributo($atributo, $error);
                }
                throw new ServiceException(implode('; ', $errores));
            }
            $this->asegurar($v->save(false), 'Error al actualizar el vehículo en la base de datos.');

            if ($foto !== null) {
                $v->foto_path = $this->handleFoto($v, $foto);
                $v->save(false, ['foto_path']);
            }

            $this->log("Vehículo actualizado: #{$v->id}");
            return $v;
        });
    }

    /**
     * Desactiva un vehículo (status=0).
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $vehiculo = Vehiculo::findOne($id);
        if ($vehiculo === null) {
            $this->agregarError('Vehículo no encontrado.');
            return false;
        }

        $tieneOrdenesAbiertas = OrdenServicio::find()
            ->where(['vehiculo_id' => $id])
            ->andWhere(['in', 'estado', ['abierto', 'en_progreso', 'esperando_repuestos', 'listo_para_entrega']])
            ->exists();

        if ($tieneOrdenesAbiertas) {
            $this->agregarError('No se puede desactivar: el vehículo tiene órdenes abiertas o en progreso.');
            return false;
        }

        $vehiculo->status = 0;
        if (!$vehiculo->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar el vehículo.');
            return false;
        }
        $this->log("Vehículo desactivado: #{$id}");
        return true;
    }

    /**
     * Retorna vehículos activos de un cliente para dropdowns.
     *
     * @param int $clienteId
     * @return array<array{id:int,patente:string,marca:string,modelo:string}>
     */
    public function getVehiculosPorCliente(int $clienteId): array
    {
        return Vehiculo::find()
            ->where(['cliente_id' => $clienteId, 'status' => 1])
            ->orderBy('patente')
            ->asArray()
            ->all();
    }

    /**
     * Guarda la foto del vehículo en uploads/vehiculos/{id}/ y retorna el path relativo.
     *
     * @param Vehiculo     $v    Vehículo dueño de la foto.
     * @param UploadedFile $foto Archivo subido.
     * @return string            Path relativo guardado.
     */
    public function handleFoto(Vehiculo $v, UploadedFile $foto): string
    {
        $this->validarFoto($foto);

        $dir = Yii::getAlias('@app/web/uploads/vehiculos/' . $v->id);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!empty($v->foto_path)) {
            $rutaActual = Yii::getAlias('@app/web/' . ltrim($v->foto_path, '/'));
            if (is_file($rutaActual)) {
                @unlink($rutaActual);
            }
        }

        $filename = 'foto_' . time() . '.' . $foto->extension;
        $foto->saveAs($dir . '/' . $filename);
        return 'uploads/vehiculos/' . $v->id . '/' . $filename;
    }

    private function validarFoto(UploadedFile $foto): void
    {
        $permitidos = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeReal = (string) $finfo->file($foto->tempName);

        if (!in_array($mimeReal, $permitidos, true)) {
            throw new ServiceException('Formato no permitido. Use JPG, PNG o WebP.');
        }

        if ((int) $foto->size > 5 * 1024 * 1024) {
            throw new ServiceException('La imagen supera el tamaño máximo permitido de 5MB.');
        }

        [$ancho, $alto] = @getimagesize($foto->tempName) ?: [0, 0];
        if ($ancho > 2048 || $alto > 2048) {
            throw new ServiceException('La imagen excede las dimensiones máximas de 2048x2048.');
        }
    }
}
