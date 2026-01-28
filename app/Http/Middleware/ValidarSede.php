<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Services\DatabaseSelector;

/**
 * Middleware para validar token y sede
 */
class ValidarSede
{
    /**
     * Manejar request entrante
     */
    public function handle(Request $request, Closure $next)
    {
        // Obtener token del header Authorization
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token no proporcionado',
            ], 401);
        }

        // Validar token
        $payload = AuthController::validarToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado',
            ], 401);
        }

        // Obtener sede del request o del token
        $sede = $request->input('sede') ?? $payload['sede'] ?? null;

        if (!$sede) {
            return response()->json([
                'success' => false,
                'message' => 'Sede no especificada',
            ], 400);
        }

        // Validar que la sede exista
        if (!DatabaseSelector::sedeExiste($sede)) {
            return response()->json([
                'success' => false,
                'message' => 'Sede no válida',
                'sedes_disponibles' => DatabaseSelector::getSedes(),
            ], 400);
        }

        // Validar que la sede del request coincida con la del token
        if ($sede !== $payload['sede']) {
            return response()->json([
                'success' => false,
                'message' => 'La sede no coincide con el token',
            ], 403);
        }

        // Agregar información del usuario autenticado al request
        $request->merge([
            'usuario_autenticado' => $payload['usuario_id'],
            'sede_autenticada' => $payload['sede'],
        ]);

        return $next($request);
    }
}
