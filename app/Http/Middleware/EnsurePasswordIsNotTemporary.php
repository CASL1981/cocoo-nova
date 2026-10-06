<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obliga a los usuarios con clave temporal (must_change_password = true)
 * a definir una contraseña propia antes de acceder a cualquier otra pantalla.
 * La ruta 'password.force-change' debe quedar FUERA de este middleware en las
 * rutas (ver routes/web.php), o se generaría un bucle infinito de redirecciones.
 */
class EnsurePasswordIsNotTemporary
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            return redirect()->route('password.force-change');
        }

        return $next($request);
    }
}
