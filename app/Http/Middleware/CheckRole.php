<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $activeRole = session('active_role');

        // Si no hay rol activo o el rol activo no coincide con el requerido
        if (!$activeRole || $activeRole !== $role) {
            // Redirigir según el rol que tiene
            if ($activeRole === 'administrador') {
                return redirect('/dashboard');
            } else {
                return redirect('/caja');
            }
        }

        return $next($request);
    }
}
