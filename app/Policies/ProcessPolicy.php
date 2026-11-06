<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Process;
use App\Models\User;
use App\Policies\Concerns\HasStandardModuleAbilities;
use Illuminate\Database\Eloquent\Model;

/**
 * Autorización del catálogo de procesos.
 */
final class ProcessPolicy
{
    use HasStandardModuleAbilities {
        delete as protected standardDelete;
    }

    protected string $module = 'processes';

    /** Un proceso con usuarios asignados no se elimina: primero se reasignan. */
    public function delete(User $actor, Model $model): bool
    {
        /** @var Process $model */
        return $this->standardDelete($actor, $model) && ! $model->users()->exists();
    }
}
