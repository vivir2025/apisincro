<?php

namespace App\Services;

use App\Models\SyncLog;
use App\Models\SyncControl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio principal de sincronización
 */
class SyncService
{
    protected $sede;
    protected $connection;

    public function __construct($sede)
    {
        $this->sede = $sede;
        $this->connection = DatabaseSelector::setConnection($sede);
    }

    /**
     * Procesar cambios subidos desde un servidor local
     */
    public function procesarUpload(array $cambios)
    {
        $sincronizados = 0;
        $errores = [];
        $ids_sincronizados = [];

        DB::beginTransaction();

        try {
            foreach ($cambios as $cambio) {
                try {
                    $this->aplicarCambio($cambio);
                    $sincronizados++;
                    
                    // Guardar ID del registro en sync_log si viene
                    if (isset($cambio['sync_log_id'])) {
                        $ids_sincronizados[] = $cambio['sync_log_id'];
                    }
                    
                } catch (\Exception $e) {
                    $errores[] = [
                        'tabla' => $cambio['tabla'] ?? 'desconocida',
                        'registro_id' => $cambio['registro_id'] ?? 'desconocido',
                        'error' => $e->getMessage(),
                    ];
                    
                    Log::error("Error sincronizando registro", [
                        'sede' => $this->sede,
                        'cambio' => $cambio,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return [
                'success' => true,
                'procesados' => $sincronizados,
                'sincronizados' => $sincronizados,
                'errores' => $errores,
                'total_cambios' => count($cambios),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error en transacción de sincronización", [
                'sede' => $this->sede,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Aplicar un cambio individual (INSERT, UPDATE, DELETE)
     */
    protected function aplicarCambio(array $cambio)
    {
        Log::info("Aplicando cambio", [
            'tabla' => $cambio['tabla'] ?? 'sin_tabla',
            'operacion' => $cambio['operacion'] ?? 'sin_operacion',
            'registro_id' => $cambio['registro_id'] ?? null
        ]);

        $tabla = $cambio['tabla'];
        $operacion = $cambio['operacion'];
        $datos = $cambio['datos'];
        $registro_id = $cambio['registro_id'] ?? null;

        // Validar que la tabla esté en la lista de tablas sincronizadas
        if (!in_array($tabla, config('sync.tablas_sincronizadas'))) {
            Log::error("Tabla no configurada para sincronización", ['tabla' => $tabla]);
            throw new \Exception("Tabla '{$tabla}' no está configurada para sincronización");
        }

        // Obtener la clave primaria de la tabla
        $primaryKey = $this->getPrimaryKeyForTable($tabla);
        Log::info("Primary key obtenida", ['tabla' => $tabla, 'pk' => $primaryKey]);

        switch ($operacion) {
            case 'INSERT':
                Log::info("Ejecutando INSERT", ['tabla' => $tabla]);
                // Verificar si ya existe el registro
                $existe = DB::table($tabla)->where($primaryKey, $datos[$primaryKey] ?? $registro_id)->exists();
                
                if ($existe) {
                    Log::info("Registro ya existe, actualizando", ['id' => $datos[$primaryKey] ?? $registro_id]);
                    // Si existe, actualizar en lugar de insertar
                    DB::table($tabla)
                        ->where($primaryKey, $datos[$primaryKey] ?? $registro_id)
                        ->update($datos);
                } else {
                    Log::info("Insertando nuevo registro");
                    DB::table($tabla)->insert($datos);
                }
                break;

            case 'UPDATE':
                if (!$registro_id) {
                    throw new \Exception("registro_id requerido para operación UPDATE");
                }
                
                Log::info("Ejecutando UPDATE", ['tabla' => $tabla, 'id' => $registro_id]);
                $affected = DB::table($tabla)
                    ->where($primaryKey, $registro_id)
                    ->update($datos);
                Log::info("UPDATE completado", ['affected_rows' => $affected]);
                break;

            case 'DELETE':
                if (!$registro_id) {
                    throw new \Exception("registro_id requerido para operación DELETE");
                }
                
                Log::info("Ejecutando DELETE", ['tabla' => $tabla, 'id' => $registro_id]);
                DB::table($tabla)
                    ->where($primaryKey, $registro_id)
                    ->delete();
                break;

            default:
                throw new \Exception("Operación '{$operacion}' no válida");
        }

        // Actualizar control de último ID sincronizado
        if ($registro_id) {
            $ultimo_id = SyncControl::obtenerUltimoId($tabla, $this->sede);
            if ($registro_id > $ultimo_id) {
                SyncControl::actualizarUltimoId($tabla, $registro_id, $this->sede);
            }
        }
    }

    /**
     * Obtener la clave primaria de una tabla
     */
    protected function getPrimaryKeyForTable($tabla)
    {
        $primaryKeys = [
            'paciente' => 'idPaciente',
            'cita' => 'idcita',
            'factura' => 'idFactura',
            'historia' => 'idhistoria',
            'hc' => 'idhistoria',
            'agenda' => 'idagenda',
            'historia_cups' => 'idHistoriaCups',
            'historia_diagnostico' => 'idHistoriaDiagnostico',
            'historia_medicamento' => 'idHistoriaMedicamento',
            'historia_remision' => 'idHistoriaRemision',
            'hc_complementaria' => 'idHcComplementaria',
            'empresa' => 'idEmpresa',
            'contrato' => 'idContrato',
            'cups' => 'idCups',
            'usuario' => 'idUsuario',
        ];

        return $primaryKeys[$tabla] ?? 'id';
    }

    /**
     * Obtener cambios nuevos del servidor para descargar a local
     */
    public function obtenerCambiosParaDownload(array $ultimos_ids)
    {
        $nuevos_registros = [];
        $tablas = config('sync.tablas_sincronizadas');

        foreach ($tablas as $tabla) {
            $ultimo_id = $ultimos_ids[$tabla] ?? 0;
            
            // Obtener registros con ID mayor al último sincronizado
            $registros = DB::table($tabla)
                ->where('id', '>', $ultimo_id)
                ->orderBy('id', 'asc')
                ->limit(500) // Limitar por lotes
                ->get()
                ->toArray();

            if (count($registros) > 0) {
                $nuevos_registros[$tabla] = array_map(function($registro) {
                    return (array) $registro;
                }, $registros);
            }
        }

        return [
            'success' => true,
            'nuevos_registros' => $nuevos_registros,
            'total_tablas' => count($nuevos_registros),
        ];
    }

    /**
     * Verificar si hay actualizaciones disponibles
     */
    public function verificarActualizaciones(array $ultimos_ids)
    {
        $hay_cambios = false;
        $total_registros = 0;
        $tablas_con_cambios = [];

        $tablas = config('sync.tablas_sincronizadas');

        foreach ($tablas as $tabla) {
            $ultimo_id = $ultimos_ids[$tabla] ?? 0;
            
            $count = DB::table($tabla)
                ->where('id', '>', $ultimo_id)
                ->count();

            if ($count > 0) {
                $hay_cambios = true;
                $total_registros += $count;
                $tablas_con_cambios[$tabla] = $count;
            }
        }

        return [
            'success' => true,
            'hay_cambios' => $hay_cambios,
            'total_registros' => $total_registros,
            'tablas_con_cambios' => $tablas_con_cambios,
        ];
    }

    /**
     * Obtener estado de sincronización de la sede
     */
    public function obtenerEstado()
    {
        $controles = SyncControl::where('sede', $this->sede)->get();
        $pendientes = SyncLog::where('sede', $this->sede)
            ->where('sincronizado', false)
            ->count();

        $estado = [];
        foreach ($controles as $control) {
            $estado[$control->tabla] = [
                'ultimo_id' => $control->ultimo_id_sincronizado,
                'ultima_sync' => $control->ultima_sincronizacion,
            ];
        }

        return [
            'success' => true,
            'sede' => $this->sede,
            'ultima_sincronizacion' => $controles->max('ultima_sincronizacion'),
            'pendientes' => $pendientes,
            'tablas' => $estado,
        ];
    }

    /**
     * Resolver conflictos según la estrategia configurada
     */
    protected function resolverConflicto($registro_servidor, $registro_local)
    {
        $estrategia = config('sync.conflict_resolution', 'newest');

        switch ($estrategia) {
            case 'newest':
                // Gana el más reciente
                $fecha_servidor = $registro_servidor['updated_at'] ?? $registro_servidor['fecha_modificacion'] ?? null;
                $fecha_local = $registro_local['updated_at'] ?? $registro_local['fecha_modificacion'] ?? null;
                
                if ($fecha_servidor && $fecha_local) {
                    return strtotime($fecha_servidor) >= strtotime($fecha_local) 
                        ? $registro_servidor 
                        : $registro_local;
                }
                return $registro_servidor;

            case 'server':
                // Siempre gana el servidor
                return $registro_servidor;

            case 'manual':
                // Requiere intervención manual
                throw new \Exception("Conflicto requiere resolución manual");

            default:
                return $registro_servidor;
        }
    }
}
