<?php

declare(strict_types=1);

namespace Modules\HumanResources\App\Policies;

use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * Autorización de las características de Talento Humano. No se elimina una característica con opciones.
 */
final class HrCharacteristicPolicy
{
    use HasStandardModuleAbilities {
        delete as private deletePermission;
    }

    protected string $module = 'hr-characteristics';

    /** @param HrCharacteristic $model */
    public function delete(User $actor, Model $model): bool
    {
        return $this->deletePermission($actor, $model) && $model->options()->doesntExist();
    }
}
