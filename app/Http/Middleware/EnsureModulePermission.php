<?php

namespace App\Http\Middleware;

use App\Support\PermissionCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return $next($request);
        }

        $segments = explode('.', $routeName);
        $module = $segments[0];
        $action = PermissionCatalog::ROUTE_ACTION_MAP[end($segments)] ?? null;

        // Ruta cuyo último segmento no es una acción conocida (ej. 'profile.edit' de Breeze/Jetstream)
        // o cuyo módulo no está registrado en el catálogo: no participa de esta convención, se deja pasar.
        if ($action === null || ! array_key_exists($module, PermissionCatalog::MODULES)) {
            return $next($request);
        }

        $permission = PermissionCatalog::permissionName($module, $action);

        abort_unless($request->user()?->can($permission), 403);

        return $next($request);
    }
}
