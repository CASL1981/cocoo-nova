<?php

namespace App\Actions\Permissions;

use App\Support\PermissionCatalog;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class SeedModulePermissions
{
    public function execute(string $module, string $guard = 'web'): void
    {
        foreach (array_keys(PermissionCatalog::ACTIONS) as $action) {
            Permission::firstOrCreate([
                'name' => PermissionCatalog::permissionName($module, $action),
                'guard_name' => $guard,
            ]);
        }

        // Obligatorio tras crear permisos fuera del flujo normal de Spatie (documentado por el paquete)
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
