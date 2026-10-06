<?php

namespace App\Actions\ServicePoints;

use App\Models\ServicePoint;

/**
 * Crea o actualiza un ServicePoint. Sin reglas de negocio adicionales por
 * ahora (a diferencia de SaveUser, que maneja clave temporal y foto).
 */
final class SaveServicePoint
{
    /**
     * Crea o actualiza un ServicePoint.
     *
     * @param array $data
     * @param ServicePoint|null $servicePoint
     * @return ServicePoint
     */
   public function execute(array $data, ?ServicePoint $servicePoint = null): ServicePoint
    {
        $servicePoint ??= new ServicePoint();
        $servicePoint->fill($data)->save();

        return $servicePoint;
    }
}
