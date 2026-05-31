<?php
declare(strict_types=1);

namespace app\components\services;

use app\models\Tecnico;
use app\models\Certificacion;

/**
 * TecnicoService: lógica de negocio para técnicos del taller.
 *
 * @author ID3.CL
 * @since 1.0.0
 */
class TecnicoService extends BaseService
{
    protected string $logCategoria = 'app.tecnico';

    /**
     * Crea un nuevo técnico.
     *
     * @param array $data
     * @return Tecnico|null
     */
    public function create(array $data): ?Tecnico
    {
        return $this->executeInTransaction(function () use ($data): Tecnico {
            $t = new Tecnico();
            $t->nombre          = $data['nombre']   ?? '';
            $t->apellido        = $data['apellido'] ?? '';
            $t->rut             = !empty($data['rut'])      ? $data['rut']      : null;
            $t->email           = !empty($data['email'])    ? $data['email']    : null;
            $t->telefono        = !empty($data['telefono']) ? $data['telefono'] : null;
            $t->especialidad_id = !empty($data['especialidad_id']) ? (int) $data['especialidad_id'] : null;
            $t->costo_hora      = (float) ($data['costo_hora'] ?? 0);
            $t->status          = (int) ($data['status'] ?? 1);

            if (!$t->validate()) {
                throw new ServiceException(implode('; ', $t->getFirstErrors()));
            }
            $this->asegurar($t->save(false), 'Error al guardar el técnico.');
            $this->log("Técnico creado: #{$t->id} ({$t->getFullName()})");
            return $t;
        });
    }

    /**
     * Actualiza un técnico existente.
     *
     * @param Tecnico $t
     * @param array   $data
     * @return Tecnico|null
     */
    public function update(Tecnico $t, array $data): ?Tecnico
    {
        return $this->executeInTransaction(function () use ($t, $data): Tecnico {
            if (isset($data['nombre']))                   $t->nombre          = $data['nombre'];
            if (isset($data['apellido']))                 $t->apellido        = $data['apellido'];
            if (array_key_exists('rut', $data))           $t->rut             = !empty($data['rut'])      ? $data['rut']      : null;
            if (array_key_exists('email', $data))         $t->email           = !empty($data['email'])    ? $data['email']    : null;
            if (array_key_exists('telefono', $data))      $t->telefono        = !empty($data['telefono']) ? $data['telefono'] : null;
            if (array_key_exists('especialidad_id', $data)) $t->especialidad_id = !empty($data['especialidad_id']) ? (int) $data['especialidad_id'] : null;
            if (isset($data['costo_hora']))               $t->costo_hora      = (float) $data['costo_hora'];
            if (isset($data['status']))                   $t->status          = (int) $data['status'];

            if (!$t->validate()) {
                throw new ServiceException(implode('; ', $t->getFirstErrors()));
            }
            $this->asegurar($t->save(false), 'Error al actualizar el técnico.');
            $this->log("Técnico actualizado: #{$t->id}");
            return $t;
        });
    }

    /**
     * Desactiva un técnico (status=0).
     *
     * @param int $id
     * @return bool
     */
    public function deactivate(int $id): bool
    {
        $t = Tecnico::findOne($id);
        if ($t === null) {
            $this->agregarError('Técnico no encontrado.');
            return false;
        }
        $t->status = 0;
        if (!$t->save(false, ['status', 'updated_at'])) {
            $this->agregarError('Error al desactivar el técnico.');
            return false;
        }
        $this->log("Técnico desactivado: #{$id}");
        return true;
    }

    /**
     * Retorna técnicos activos para dropdowns.
     *
     * @return array<int,string>
     */
    public function getTecnicosActivos(): array
    {
        $tecnicos = Tecnico::find()
            ->where(['status' => 1])
            ->orderBy(['apellido' => SORT_ASC, 'nombre' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($tecnicos as $t) {
            $result[$t->id] = $t->getFullName();
        }
        return $result;
    }

    /**
     * Agrega una certificación a un técnico.
     *
     * @param int   $tecnicoId
     * @param array $data
     * @return Certificacion|null
     */
    public function addCertificacion(int $tecnicoId, array $data): ?Certificacion
    {
        return $this->executeInTransaction(function () use ($tecnicoId, $data): Certificacion {
            $cert                  = new Certificacion();
            $cert->tecnico_id      = $tecnicoId;
            $cert->titulo          = $data['titulo']          ?? '';
            $cert->entidad_emisora = !empty($data['entidad_emisora'])  ? $data['entidad_emisora']  : null;
            $cert->fecha_emision   = !empty($data['fecha_emision'])    ? $data['fecha_emision']    : null;
            $cert->fecha_vencimiento = !empty($data['fecha_vencimiento']) ? $data['fecha_vencimiento'] : null;

            if (!$cert->validate()) {
                throw new ServiceException(implode('; ', $cert->getFirstErrors()));
            }
            $this->asegurar($cert->save(false), 'Error al guardar la certificación.');
            $this->log("Certificación agregada al técnico #{$tecnicoId}: {$cert->titulo}");
            return $cert;
        });
    }
}
