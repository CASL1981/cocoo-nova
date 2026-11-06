<?php

namespace App\Contracts;

use App\Models\User;

/**
 * Implementado por los modelos que se pueden duplicar desde un listado
 * (botón "Duplicar"). DuplicateRecord crea la copia con replicate() y llama a
 * prepareDuplicate() antes de guardarla, para que cada modelo aplique sus reglas:
 * valores únicos con sufijo COPIA, estado inicial y responsable del registro.
 */
interface Duplicable
{
    /**
     * Ajusta la copia (aún sin guardar) antes de persistirla.
     *
     * @param  User  $actor  Usuario que duplica el registro.
     */
    public function prepareDuplicate(User $actor): void;
}
