<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si el usuario está autenticado, verificar si la sesión ha expirado
        if (Auth::check()) {
            $lastActivity = session('last_activity_time');
            $sessionLifetime = config('session.lifetime') * 60; // Convertir minutos a segundos
            
            // Si existe el timestamp de última actividad
            if ($lastActivity) {
                $elapsedTime = time() - $lastActivity;
                
                // Si ha pasado más tiempo que el lifetime configurado
                if ($elapsedTime > $sessionLifetime) {
                    // Cerrar sesión y limpiar
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // Si es una petición AJAX/API, retornar JSON
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json([
                            'message' => 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.',
                            'expired' => true
                        ], 401);
                    }
                    
                    // Si es petición web, redirigir al login
                    return redirect()->route('login')
                        ->with('error', 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
                }
            }
            
            // Actualizar el timestamp de última actividad
            session(['last_activity_time' => time()]);
        }
        
        return $next($request);
    }
}
