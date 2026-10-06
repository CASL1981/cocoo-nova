<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\PermissionCatalog;

trait ChecksModulePermissions
{
    // Cada Policy que use este trait debe declarar: protected string $module = 'xxx';

    protected function can(User $actor, string $action): bool
    {
        return $actor->hasPermissionTo(PermissionCatalog::permissionName($this->module, $action));
    }
}
