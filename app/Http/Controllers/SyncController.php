<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\SyncService;
use App\Services\DatabaseSelector;

/**
 * Controlador de sincronización
 */
class SyncController extends Controller
{
    /**
     * Verificar actualizaciones disponibles
     * 
     * POST /api/sync/check-updates
     * {
     *   "sede": "morales",
     *   "tabla": "pacientes",
     *   "ultimo_id": 0
     * }
     */
    public function checkUpdates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sede' => 'required|string',
            'tabla' => 'required|string',
            'ultimo_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sede = $request->input('sede');
        $tabla = $request->input('tabla');
        $ultimoId = $request->input('ultimo_id', 0);

        try {
            // Cambiar a la BD de la sede
            DatabaseSelector::setConnection($sede);

            // Instanciar el servicio con la sede
            $syncService = new SyncService($sede);

            // Verificar actualizaciones
            $updates = $syncService->verificarActualizaciones([$tabla => $ultimoId]);

            return response()->json([
                'success' => true,
                'sede' => $sede,
                'tabla' => $tabla,
                'total_updates' => count($updates),
                'updates' => $updates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar actualizaciones',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir cambios locales al servidor
     * 
     * POST /api/sync/upload
     * {
     *   "sede": "morales",
     *   "cambios": [...]
     * }
     */
    public function upload(Request $request)
    {
        Log::info("=== SYNC UPLOAD INICIADO ===", [
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'sede' => 'required|string',
            'cambios' => 'required|array',
        ]);

        if ($validator->fails()) {
            Log::error("Validación fallida", ['errors' => $validator->errors()]);
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sede = $request->input('sede');
        $cambios = $request->input('cambios');

        Log::info("Iniciando procesamiento", [
            'sede' => $sede,
            'total_cambios' => count($cambios)
        ]);

        try {
            // Subir cambios
            $syncService = new SyncService($sede);
            
            Log::info("SyncService creado, procesando upload...");
            $resultado = $syncService->procesarUpload($cambios);

            Log::info("Upload completado", [
                'procesados' => $resultado['procesados'],
                'errores' => count($resultado['errores'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cambios subidos exitosamente',
                'procesados' => $resultado['procesados'],
                'errores' => $resultado['errores'],
            ]);
        } catch (\Exception $e) {
            Log::error("ERROR en upload", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al subir cambios',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Descargar cambios del servidor a local
     * 
     * POST /api/sync/download
     * {
     *   "sede": "morales",
     *   "tabla": "pacientes",
     *   "ultimo_id": 0
     * }
     */
    public function download(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sede' => 'required|string',
            'tablas' => 'nullable|array',
            'desde_fecha' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $sede = $request->input('sede');
        $tablas = $request->input('tablas', null);
        $desdeFecha = $request->input('desde_fecha', null);

        try {
            // Descargar cambios
            $syncService = new SyncService($sede);
            $ultimosIds = is_array($tablas) ? array_fill_keys($tablas, 0) : [];
            $resultado = $syncService->obtenerCambiosParaDownload($ultimosIds);

            Log::info("Download procesado", [
                'sede' => $sede,
                'total_tablas_con_cambios' => $resultado['total_tablas'] ?? 0
            ]);

            return response()->json([
                'success' => true,
                'sede' => $sede,
                'total_tablas_con_cambios' => $resultado['total_tablas'] ?? 0,
                'nuevos_registros' => $resultado['nuevos_registros'] ?? [],
                'message' => ($resultado['total_tablas'] ?? 0) > 0 
                    ? 'Cambios disponibles para descargar' 
                    : 'No hay cambios nuevos disponibles',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar cambios',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estado de sincronización
     * 
     * GET /api/sync/status/{sede}
     */
    public function status($sede)
    {
        try {
            $syncService = new SyncService($sede);
            $status = $syncService->obtenerEstado();

            return response()->json([
                'success' => true,
                'sede' => $sede,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint de prueba
     * 
     * POST /api/sync/test
     */
    public function test(Request $request)
    {
        $sede = $request->input('sede', 'morales');

        try {
            // Cambiar conexión
            DatabaseSelector::setConnection($sede);

            // Probar conexión
            $connection = \DB::connection()->getDatabaseName();

            return response()->json([
                'success' => true,
                'message' => 'Conexión exitosa',
                'sede' => $sede,
                'database' => $connection,
                'timestamp' => now()->toDateTimeString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de conexión',
                'sede' => $sede,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
