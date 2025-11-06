<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                /** @var \App\Models\User $user */
                $user = Auth::guard($guard)->user();
                $roles = $user->roles()->pluck('name')->toArray();
                
                // If user has active role in session, go to dashboard
                if (session('active_role')) {
                    return redirect('/dashboard');
                }
                
                // If user has only one role, set it and go to dashboard
                if (count($roles) === 1) {
                    session(['active_role' => $roles[0]]);
                    return redirect('/dashboard');
                }
                
                // If user has multiple roles, go to role selection
                return redirect('/select-role');
            }
        }

        return $next($request);
    }
}
