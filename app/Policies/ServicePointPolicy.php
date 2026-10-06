<?php

namespace App\Policies;

use App\Models\ServicePoint;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

/**
 * Autoriza el CRUD de ServicePoint usando los permisos reales del catálogo
 * (service-points-read, service-points-create, etc.), igual que User y Role.
 */
class ServicePointPolicy
{
    use ChecksModulePermissions;

    protected string $module = 'service-points';

    public function viewAny(User $actor): bool { return $this->can($actor, 'read'); }
    public function view(User $actor, ServicePoint $servicePoint): bool { return $this->can($actor, 'read'); }
    public function create(User $actor): bool { return $this->can($actor, 'create'); }
    public function update(User $actor, ServicePoint $servicePoint): bool { return $this->can($actor, 'update'); }

    public function delete(User $actor, ServicePoint $servicePoint): bool
    {
        // No se puede borrar una sede con usuarios asignados (misma regla que Role).
        return $this->can($actor, 'delete')
            && User::where('service_point_id', $servicePoint->id)->doesntExist();
    }
}
