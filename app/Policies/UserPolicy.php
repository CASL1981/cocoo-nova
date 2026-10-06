<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Policies\Concerns\ChecksModulePermissions;

class UserPolicy
{
    use ChecksModulePermissions;

    protected string $module = 'users';

    public function viewAny(User $actor): bool { return $this->can($actor, 'read'); }
    public function create(User $actor): bool { return $this->can($actor, 'create'); }
    public function update(User $actor, User $target): bool { return $this->can($actor, 'update'); }

    public function view(User $actor, User $target): bool
    {
        return $this->can($actor, 'read');
    }

    /**
     * Summary of delete
     * @param User $actor
     * @param User $target
     * @return bool
     */
    public function delete(User $actor, User $target): bool
    {
        return $this->can($actor, 'delete') && $actor->isNot($target);
    }

}
