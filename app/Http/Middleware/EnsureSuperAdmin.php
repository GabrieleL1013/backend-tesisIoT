<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        if (! $user->relationLoaded('role')) {
            $user->load('role');
        }

        $isSuper = ($user->role_id === 1) || 
                   ($user->role && $user->role->name === 'Superusuario');

        if (! $isSuper) {
            return response()->json([
                'message' => 'Acceso denegado. Se requieren permisos de Superusuario.'
            ], 403);
        }

        return $next($request);
    }
}
