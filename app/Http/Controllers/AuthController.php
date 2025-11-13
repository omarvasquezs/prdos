<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Mostrar el formulario de login
     */
    public function showLogin()
    {
        return view('login');
    }

    /**
     * Procesar el login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Establecer el timestamp de última actividad al iniciar sesión
            session(['last_activity_time' => time()]);

            /** @var \App\Models\User|null $user */
            $user = Auth::user();
            
            $roles = $user->roles()->pluck('name')->toArray();

            // If user has only one role, set it as active immediately
            if (count($roles) === 1) {
                $request->session()->put('active_role', $roles[0]);
                $redirect = '/dashboard';
            } else {
                $redirect = '/select-role';
            }

            return response()->json([
                'message' => 'Logged in successfully',
                'roles' => $roles,
                'redirect' => $redirect,
            ]);
        }

        throw ValidationException::withMessages([
            'username' => ['Las credenciales proporcionadas no coinciden con nuestros registros.'],
        ]);
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Obtener el usuario autenticado
     */
    public function user(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(null, 204);
        }

        $data = $user->toArray();
        $data['roles'] = $user->roles()->pluck('name')->toArray();
        $data['active_role'] = session('active_role');

        return response()->json($data);
    }

    /**
     * Set the active role for the current session.
     */
    public function setActiveRole(Request $request)
    {
        $request->validate([
            'role' => ['required', 'string'],
        ]);

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $role = $request->input('role');

        // Ensure the user actually has this role
        if (! $user->roles()->where('name', $role)->exists()) {
            return response()->json(['message' => 'Role not available for user.'], 403);
        }

        $request->session()->put('active_role', $role);

        // Determinar redirección basada en el rol
        $redirect = match($role) {
            'caja' => '/caja',
            'administrador' => '/dashboard',
            default => '/dashboard'
        };

        return response()->json([
            'message' => 'Active role set.',
            'redirect' => $redirect
        ]);
    }
}
