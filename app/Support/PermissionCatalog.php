<?php

namespace App\Support;

final class PermissionCatalog
{
    public const ACTIONS = [
        'read'    => 'Leer (Ver)',
        'create'  => 'Crear',
        'update'  => 'Editar',
        'delete'  => 'Eliminar',
        'toggle'  => 'Activar/Inactivar',
        'process' => 'Procesar',
        'reverse' => 'Reversar',
    ];

    // Agrega aquí cada módulo nuevo del sistema
    public const MODULES = [
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'service-points' => 'Centros de costos',
        'products' => 'Productos',
    ];

    // íconos por acción, mismo criterio que ya tenías en la vista
    public const ACTION_ICONS = [
        'read'    => 'bi-eye',
        'create'  => 'bi-plus-circle',
        'update'  => 'bi-pencil-square',
        'delete'  => 'bi-trash3',
        'toggle'  => 'bi-power',
        'process' => 'bi-play-circle',
        'reverse' => 'bi-arrow-counterclockwise',
    ];

    // íconos por módulo
    public const MODULE_ICONS = [
        'users' => 'bi-people-fill',
        'roles' => 'bi-shield-lock-fill',
        'service-points' => 'bi-geo-alt-fill',
        'products' => 'bi-box-seam-fill',
    ];

    //  traduce el último segmento del nombre de ruta a una acción del catálogo
    public const ROUTE_ACTION_MAP = [
        'index'   => 'read',
        'show'    => 'read',
        'create'  => 'create',
        'store'   => 'create',
        'edit'    => 'update',
        'update'  => 'update',
        'destroy' => 'delete',
        'toggle'  => 'toggle',
        'process' => 'process',
        'reverse' => 'reverse',
    ];

    public static function permissionName(string $module, string $action): string
    {
        return "{$module}-{$action}";
    }

    public static function allForModule(string $module): array
    {
        return array_map(
            fn (string $action) => self::permissionName($module, $action),
            array_keys(self::ACTIONS)
        );
    }
}
