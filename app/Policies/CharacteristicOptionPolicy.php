<?php

namespace App\Policies;

use App\Models\CharacteristicOption;
use App\Models\User;
use App\Policies\Concerns\ChecksModulePermissions;

class CharacteristicOptionPolicy
{
    use ChecksModulePermissions;

    protected string $module = 'characteristics';   // mismo módulo que Characteristic, a propósito

    public function viewAny(User $actor): bool
    {
        return $this->can($actor, 'read');
    }

    public function view(User $actor, CharacteristicOption $o): bool
    {
        return $this->can($actor, 'read');
    }

    public function create(User $actor): bool
    {
        return $this->can($actor, 'create');
    }

    /** Duplicar exige el permiso de crear y que la característica de la opción no esté eliminada. */
    public function duplicate(User $actor, CharacteristicOption $o): bool
    {
        return $this->can($actor, 'create') && $this->parentIsActive($o);
    }

    public function update(User $actor, CharacteristicOption $o): bool
    {
        return $this->can($actor, 'update') && $this->parentIsActive($o);
    }

    public function delete(User $actor, CharacteristicOption $o): bool
    {
        return $this->can($actor, 'delete') && $this->parentIsActive($o);
    }

    public function restore(User $actor, CharacteristicOption $o): bool
    {
        return $this->can($actor, 'reverse') && $this->parentIsActive($o);
    }

    /**
     * Una opción solo se modifica si su característica no está eliminada.
     * La relación excluye las características eliminadas (SoftDeletes),
     * por lo que no existe cuando la característica padre está eliminada.
     */
    private function parentIsActive(CharacteristicOption $o): bool
    {
        return $o->characteristic()->exists();
    }
}
