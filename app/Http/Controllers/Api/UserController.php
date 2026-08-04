<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Retorna únicamente el conteo total de usuarios registrados en el sistema.
     */
    public function count()
    {
        return response()->json([
            'total_users' => User::count()
        ]);
    }

    /**
     * Devuelve el listado de todos los usuarios en la base de datos.
     */
    public function index()
    {
        return response()->json(User::with('role')->orderBy('id', 'asc')->get());
    }

    /**
     * Muestra el detalle de un usuario específico.
     */
    public function show($id)
    {
        $user = User::with('role')->findOrFail($id);
        return response()->json($user);
    }

    /**
     * Registra un nuevo usuario cifrando su contraseña.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|integer|exists:roles,id'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id
        ]);

        $user->load('role');

        return response()->json($user, 201);
    }

    /**
     * Actualiza los datos de un usuario y cifra su nueva contraseña si se proporciona.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|integer|exists:roles,id'
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id
        ];

        // Cifrar la nueva contraseña si fue completada
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);
        $user->load('role');

        return response()->json($user);
    }

    /**
     * Elimina un usuario de la base de datos (evitando eliminar al usuario raíz id=1).
     */
    public function destroy($id)
    {
        if ($id == 1) {
            return response()->json(['message' => 'Operación restringida. No se puede eliminar al Superusuario raíz.'], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado con éxito.']);
    }
}
