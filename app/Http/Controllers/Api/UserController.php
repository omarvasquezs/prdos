<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            
            $query = User::with('roles')
                ->orderBy('id');
            
            // Búsqueda
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%");
                });
            }
            
            // Paginación
            $users = $query->paginate($perPage);

            return response()->json([
                'data' => collect($users->items())->map(function ($user) {
                    return $this->transformUser($user);
                })->values(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener usuarios',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show(User $user): JsonResponse
    {
        try {
            $user->load('roles');
            return response()->json($this->transformUser($user));
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'username' => 'required|string|unique:users,username|max:255',
                'password' => 'required|string|min:6|confirmed',
                'roles' => 'required|array|min:1',
                'roles.*' => 'exists:roles,id',
            ]);

            $validated['password'] = Hash::make($validated['password']);
            
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'username' => $validated['username'],
                'password' => $validated['password'],
            ]);

            // Asignar roles
            $user->roles()->sync($validated['roles']);
            $user->load('roles');

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'user' => $this->transformUser($user)
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un usuario existente
     */
    public function update(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'username' => 'sometimes|required|string|unique:users,username,' . $user->id . '|max:255',
                'roles' => 'sometimes|required|array|min:1',
                'roles.*' => 'exists:roles,id',
            ]);

            // Actualizar roles si se proporcionan
            if (isset($validated['roles'])) {
                $user->roles()->sync($validated['roles']);
                unset($validated['roles']);
            }

            $user->update($validated);
            $user->load('roles');

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'user' => $this->transformUser($user)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy(User $user): JsonResponse
    {
        try {
            // No permitir eliminar el usuario actual
            if ($user->id === Auth::id()) {
                return response()->json([
                    'error' => 'No puedes eliminar tu propio usuario'
                ], 422);
            }

            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar usuario',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar todos los roles disponibles
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::orderBy('name')->get(['id', 'name']);
            return response()->json($roles);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener roles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar o restablecer contraseña de usuario
     */
    public function changePassword(Request $request, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:new,reset',
                'new_password' => 'required_if:action,new|nullable|string|min:6|confirmed',
            ]);

            if ($validated['action'] === 'reset') {
                // Restablecer a 12345678
                $user->password = Hash::make('12345678');
                $message = 'Contraseña restablecida a 12345678';
            } else {
                // Nueva contraseña personalizada
                $user->password = Hash::make($validated['new_password']);
                $message = 'Contraseña actualizada exitosamente';
            }

            $user->save();

            return response()->json([
                'message' => $message
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al cambiar contraseña',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transformar usuario
     */
    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'roles' => $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                ];
            }),
            'roles_text' => $user->roles->pluck('name')->join(', '),
            'created_at' => $user->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $user->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
