<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\DebugController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas (sin autenticación)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify', [AuthController::class, 'verify']);
});

// Rutas protegidas (requieren autenticación y sede válida)
Route::middleware(['validar.sede'])->prefix('sync')->group(function () {
    
    // Verificar actualizaciones disponibles
    Route::post('/check-updates', [SyncController::class, 'checkUpdates']);
    
    // Subir cambios locales al servidor
    Route::post('/upload', [SyncController::class, 'upload']);
    
    // Descargar cambios del servidor a local
    Route::post('/download', [SyncController::class, 'download']);
    
    // Estado de sincronización
    Route::get('/status/{sede}', [SyncController::class, 'status']);
    
    // Endpoint de prueba
    Route::post('/test', [SyncController::class, 'test']);
});

// Rutas de debug (sin middleware para diagnóstico)
Route::prefix('debug')->group(function () {
    Route::post('/test-sync', [DebugController::class, 'testSync']);
    Route::get('/logs', [DebugController::class, 'getLogs']);
});

// Ruta de bienvenida
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'API de Sincronización - Historia Clínica',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /api/auth/login' => 'Autenticación y generación de token',
            'POST /api/sync/check-updates' => 'Verificar actualizaciones',
            'POST /api/sync/upload' => 'Subir cambios',
            'POST /api/sync/download' => 'Descargar cambios',
            'GET /api/sync/status/{sede}' => 'Estado de sincronización',
        ],
    ]);
});
