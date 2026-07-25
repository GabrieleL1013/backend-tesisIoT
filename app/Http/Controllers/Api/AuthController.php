<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validar que vengan los datos correctos del formulario
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // 2. Buscar al usuario en la base de datos por su correo
        $user = User::where('email', $request->email)->first();

        // 3. Verificar si el usuario existe y si la contraseña coincide con el Hash seguro
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales proporcionadas son incorrectas.'
            ], 401);
        }

        // 4. Generar el Token de acceso seguro en la base de datos
        $token = $user->createToken('auth_token')->plainTextToken;

        // 4.5. Cargar la relación del rol completo
        $user->load('role');

        // 5. Responder a React con el token y los datos del usuario
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $user->role // objeto completo: {id, name, level_permission, color, description}
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        // Revocar/Borrar el token actual cuando el usuario salga del sistema
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente en la base de datos.'
        ]);
    }
}