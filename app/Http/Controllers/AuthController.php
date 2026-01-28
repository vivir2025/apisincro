<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Services\DatabaseSelector;

/**
 * Controlador de autenticación por sede
 */
class AuthController extends Controller
{
    /**
     * Login y generación de token
     * 
     * POST /api/auth/login
     * {
     *   "sede": "morales",
     *   "usuario": "admin",
     *   "password": "123456"
     * }
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sede' => 'required|string',
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sede = $request->input('sede');
        $usuario = $request->input('usuario');
        $password = $request->input('password');

        // Validar que la sede exista
        if (!DatabaseSelector::sedeExiste($sede)) {
            return response()->json([
                'success' => false,
                'message' => 'Sede no válida',
                'sedes_disponibles' => DatabaseSelector::getSedes(),
            ], 400);
        }

        // Cambiar a la BD de la sede
        DatabaseSelector::setConnection($sede);

        // Buscar usuario en la BD de la sede (tabla usuario con campos usuLogin y usuClave)
        $usuarioDb = \DB::table('usuario')
            ->where('usuLogin', $usuario)
            ->first();

        if (!$usuarioDb) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Verificar contraseña (campo usuClave)
        $passwordValida = $this->verificarPassword($password, $usuarioDb->usuClave);

        if (!$passwordValida) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
            ], 401);
        }

        // Obtener ID del usuario (intentar diferentes nombres de campo)
        $usuarioId = $usuarioDb->usuId ?? $usuarioDb->id ?? $usuarioDb->IdUsuario ?? $usuarioDb->usuario_id ?? null;
        $usuarioNombre = $usuarioDb->usuNombre ?? $usuarioDb->nombre ?? $usuarioDb->Nombre ?? $usuarioDb->nombres ?? 'Usuario';

        // Generar token
        $token = $this->generarToken($usuarioId, $sede);

        return response()->json([
            'success' => true,
            'message' => 'Autenticación exitosa',
            'token' => $token,
            'expires_in' => 3600 * 24, // 24 horas
            'sede' => $sede,
            'usuario' => [
                'id' => $usuarioId,
                'nombre' => $usuarioNombre,
                'usuario' => $usuarioDb->usuLogin,
            ],
        ]);
    }

    /**
     * Verificar contraseña
     */
    protected function verificarPassword($password, $hash)
    {
        // Si el hash está vacío, no permitir
        if (empty($hash)) {
            return false;
        }

        // Si usas MD5 (como muchos sistemas antiguos) - 32 caracteres hexadecimales
        if (strlen($hash) === 32 && ctype_xdigit($hash)) {
            return md5($password) === $hash;
        }
        
        // Si usas bcrypt (comienza con $2y$ o $2a$)
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$')) {
            return Hash::check($password, $hash);
        }
        
        // Si la contraseña está en texto plano (NO RECOMENDADO pero común en sistemas legacy)
        return $password === $hash;
    }

    /**
     * Generar token JWT simple
     */
    protected function generarToken($usuario_id, $sede)
    {
        $payload = [
            'usuario_id' => $usuario_id,
            'sede' => $sede,
            'iat' => time(),
            'exp' => time() + (3600 * 24), // 24 horas
        ];

        $secret = env('JWT_SECRET', 'secret_key_cambiar');
        
        // Token simple: base64(payload) + '.' + hash
        $payloadEncoded = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $payloadEncoded, $secret);
        
        return $payloadEncoded . '.' . $signature;
    }

    /**
     * Validar token
     */
    public static function validarToken($token)
    {
        if (!$token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadEncoded, $signature] = $parts;
        
        // Verificar firma
        $secret = env('JWT_SECRET', 'secret_key_cambiar');
        $expectedSignature = hash_hmac('sha256', $payloadEncoded, $secret);
        
        if ($signature !== $expectedSignature) {
            return null;
        }

        // Decodificar payload
        $payload = json_decode(base64_decode($payloadEncoded), true);
        
        // Verificar expiración
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Verificar token (endpoint de prueba)
     */
    public function verify(Request $request)
    {
        $token = $request->bearerToken();
        $payload = self::validarToken($token);

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'payload' => $payload,
        ]);
    }
}
