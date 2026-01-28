<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DatabaseSelector;

class DebugController extends Controller
{
    /**
     * Probar sincronización con debug completo
     * POST /api/debug/test-sync
     */
    public function testSync(Request $request)
    {
        try {
            $sede = $request->input('sede', 'morales');
            $cambio = $request->input('cambio');

            Log::info("=== DEBUG: Iniciando test de sincronización ===", [
                'sede' => $sede,
                'cambio' => $cambio
            ]);

            // Cambiar conexión
            DatabaseSelector::setConnection($sede);
            $dbName = DB::connection()->getDatabaseName();

            Log::info("Conexión establecida", ['database' => $dbName]);

            // Verificar tablas
            $tables = DB::select('SHOW TABLES');
            $tablesNames = array_map(function($t) use ($dbName) {
                $key = "Tables_in_$dbName";
                return $t->$key;
            }, $tables);

            $hasSyncControl = in_array('sync_control', $tablesNames);
            $hasSyncLog = in_array('sync_log', $tablesNames);

            Log::info("Verificación de tablas", [
                'sync_control_exists' => $hasSyncControl,
                'sync_log_exists' => $hasSyncLog,
                'all_tables' => $tablesNames
            ]);

            // Verificar estructura de sync_log
            if ($hasSyncLog) {
                $syncLogColumns = DB::select('DESCRIBE sync_log');
                Log::info("Estructura de sync_log", ['columns' => $syncLogColumns]);
            }

            // Verificar estructura de sync_control
            if ($hasSyncControl) {
                $syncControlColumns = DB::select('DESCRIBE sync_control');
                Log::info("Estructura de sync_control", ['columns' => $syncControlColumns]);
            }

            // Si se envió un cambio, intentar aplicarlo
            if ($cambio) {
                Log::info("Intentando aplicar cambio...");
                
                $tabla = $cambio['tabla'] ?? 'paciente';
                $operacion = $cambio['operacion'] ?? 'UPDATE';
                $registroId = $cambio['registro_id'] ?? null;
                $datos = $cambio['datos'] ?? [];

                // Obtener PK
                $primaryKeys = [
                    'paciente' => 'idPaciente',
                    'cita' => 'idcita',
                    'historia' => 'idhistoria',
                ];
                $pk = $primaryKeys[$tabla] ?? 'id';

                Log::info("Preparando query", [
                    'tabla' => $tabla,
                    'operacion' => $operacion,
                    'pk' => $pk,
                    'registro_id' => $registroId
                ]);

                if ($operacion === 'UPDATE' && $registroId && !empty($datos)) {
                    // Intentar el update
                    $affected = DB::table($tabla)
                        ->where($pk, $registroId)
                        ->update($datos);
                    
                    Log::info("Update ejecutado", ['affected_rows' => $affected]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Update ejecutado',
                        'affected_rows' => $affected
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'database' => $dbName,
                'sync_control_exists' => $hasSyncControl,
                'sync_log_exists' => $hasSyncLog,
                'tables_count' => count($tablesNames),
                'message' => 'Verificación completada. Revisa storage/logs/laravel.log para detalles'
            ]);

        } catch (\Exception $e) {
            Log::error("ERROR en test-sync", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => 'Revisa storage/logs/laravel.log para el stack trace completo'
            ], 500);
        }
    }

    /**
     * Ver últimas líneas del log
     * GET /api/debug/logs
     */
    public function getLogs()
    {
        try {
            $logFile = storage_path('logs/laravel.log');
            
            if (!file_exists($logFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Log file not found'
                ]);
            }

            // Leer últimas 100 líneas
            $lines = [];
            $file = new \SplFileObject($logFile, 'r');
            $file->seek(PHP_INT_MAX);
            $lastLine = $file->key();
            $startLine = max(0, $lastLine - 100);

            $file->seek($startLine);
            while (!$file->eof()) {
                $lines[] = $file->fgets();
            }

            return response()->json([
                'success' => true,
                'log_file' => $logFile,
                'lines' => $lines,
                'total_lines' => $lastLine
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
