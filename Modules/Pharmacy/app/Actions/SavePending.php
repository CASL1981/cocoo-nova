<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use App\Models\User;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Crea o actualiza el encabezado de un pendiente.
 *
 * Al crear, el solicitante es el usuario autenticado y el estado inicia en Activo;
 * ninguno de los dos llega desde el formulario.
 */
final class SavePending
{
    /** @param  array<string, mixed>  $data */
    public function execute(array $data, User $actor, ?Pending $pending = null): Pending
    {
        if ($pending) {
            $pending->update($data);

            return $pending;
        }

        return Pending::create([
            ...$data,
            'user_id' => $actor->id,
            'status' => PendingStatus::Active,
        ]);
    }
}
