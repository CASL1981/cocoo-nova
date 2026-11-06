<?php

namespace App\Policies;

use App\Models\Characteristic;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class CharacteristicPolicy
{
    use ChecksModulePermissions;

    protected string $module = 'characteristics';

    public function viewAny(User $actor): bool
    {
        return $this->can($actor, 'read');
    }

    public function view(User $actor, Characteristic $c): bool
    {
        return $this->can($actor, 'read');
    }

    public function create(User $actor): bool
    {
        return $this->can($actor, 'create');
    }

    /** Duplicar un registro exige el mismo permiso de crear. */
    public function duplicate(User $actor, Characteristic $c): bool
    {
        return $this->can($actor, 'create');
    }

    public function update(User $actor, Characteristic $c): bool
    {
        return $this->can($actor, 'update');
    }

    public function delete(User $actor, Characteristic $c): bool
    {
        return $this->can($actor, 'delete') && $c->options()->doesntExist();
    }

    /**
     * Restaurar una característica eliminada requiere el permiso de reversar.
     */
    public function restore(User $actor, Characteristic $c): bool
    {
        return $this->can($actor, 'reverse');
    }
}
