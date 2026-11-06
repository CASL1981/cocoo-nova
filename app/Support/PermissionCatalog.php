<?php

namespace App\Support;

final class PermissionCatalog
{
    public const ACTIONS = [
        'read' => 'Leer',
        'create' => 'Crear',
        'update' => 'Editar',
        'delete' => 'Elim.',
        'toggle' => 'Act./Ina.',
        'process' => 'Proces.',
        'reverse' => 'Rever.',
        'import' => 'Impor.',
    ];

    // Agrega aquí cada módulo nuevo del sistema
    public const MODULES = [
        'users' => 'Usuarios',
        'roles' => 'Roles',
        'service-points' => 'Centros de costos',
        'products' => 'Productos',
        'characteristics' => 'Características',
        // Procesos (áreas) y sus directores; los reemplazos llevan línea propia porque los
        // registra el Administrador y definen quién aprueba las requisiciones.
        'processes' => 'Procesos',
        'process-substitutions' => 'Reemplazos de directores',
        // Configuración global con los datos de la empresa: ver (read) y editar (update).
        'company-settings' => 'Datos de la empresa',
        // Países, departamentos y ciudades (una sola tabla jerárquica) que usan los formularios.
        'geographic-locations' => 'Ubicación geográfica',
        'stock-outs' => 'Agotados',
        'pendings' => 'Pendientes',
        // Gestión de Oficina Principal: carga del inventario del ERP, asignación de stock (process)
        // y corrección de cantidades enviadas (reverse).
        'inventory-balances' => 'Inventario disponible',
        // Productos nuevos sin codificar: las farmacias solicitan (create/update/delete) y Oficina
        // Principal asocia el ítem, asigna unidades y TB y rechaza (process) o corrige (reverse).
        'new-products' => 'Productos nuevos',
        // Proveedores (formato FO-300-01): una línea por proceso que los gestiona. Registrar (create),
        // editar el borrador y enviarlo (update), verificar o rechazar (process), devolver a borrador
        // y restaurar (reverse) y activar/inactivar la vinculación (toggle). Aprueba el director del proceso.
        'administrative-providers' => 'Proveedores administrativos',
        'commercial-providers' => 'Proveedores comerciales',
        // Talento Humano: listas propias del área (ej. Cargos con proceso y jefe inmediato).
        'hr-characteristics' => 'Características de talento humano',
    ];

    // íconos por acción, mismo criterio que ya tenías en la vista
    public const ACTION_ICONS = [
        'read' => 'bi-eye',
        'create' => 'bi-plus-circle',
        'update' => 'bi-pencil-square',
        'delete' => 'bi-trash3',
        'toggle' => 'bi-power',
        'process' => 'bi-play-circle',
        'reverse' => 'bi-arrow-counterclockwise',
        'import' => 'bi-upload',
    ];

    // íconos por módulo
    public const MODULE_ICONS = [
        'users' => 'bi-people-fill',
        'roles' => 'bi-shield-lock-fill',
        'service-points' => 'bi-geo-alt-fill',
        'products' => 'bi-box-seam-fill',
        'processes' => 'bi-diagram-3-fill',
        'process-substitutions' => 'bi-person-gear',
        'company-settings' => 'bi-building-gear',
        'geographic-locations' => 'bi-globe-americas',
        'stock-outs' => 'bi-exclamation-octagon-fill',
        'pendings' => 'bi-clipboard2-pulse-fill',
        'inventory-balances' => 'bi-boxes',
        'new-products' => 'bi-bag-plus-fill',
        'administrative-providers' => 'bi-briefcase-fill',
        'commercial-providers' => 'bi-truck',
        'hr-characteristics' => 'bi-person-badge',
    ];

    //  traduce el último segmento del nombre de ruta a una acción del catálogo
    public const ROUTE_ACTION_MAP = [
        'index' => 'read',
        'show' => 'read',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
        'toggle' => 'toggle',
        'process' => 'process',
        'reverse' => 'reverse',
    ];

    public static function permissionName(string $module, string $action): string
    {
        return "{$module}-{$action}";
    }

    /** @return list<string> */
    public static function allForModule(string $module): array
    {
        return array_map(
            fn (string $action) => self::permissionName($module, $action),
            array_keys(self::ACTIONS)
        );
    }
}
