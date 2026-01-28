<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de control de sincronización
 * Registra todos los cambios realizados para sincronizar
 */
class SyncLog extends Model
{
    protected $table = 'sync_log';
    public $timestamps = false;
    
    protected $fillable = [
        'tabla',
        'registro_id',
        'operacion',
        'datos_json',
        'fecha_cambio',
        'sincronizado',
        'sede',
        'hash_cambio',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
        'sincronizado' => 'boolean',
    ];

    /**
     * Generar hash único para un cambio
     */
    public static function generarHash($tabla, $registro_id, $operacion, $datos)
    {
        $string = $tabla . $registro_id . $operacion . json_encode($datos);
        return hash('sha256', $string);
    }

    /**
     * Registrar un cambio para sincronizar
     */
    public static function registrar($tabla, $registro_id, $operacion, $datos, $sede, $usuario_id = null)
    {
        $hash = self::generarHash($tabla, $registro_id, $operacion, $datos);
        
        return self::create([
            'tabla' => $tabla,
            'registro_id' => $registro_id,
            'operacion' => $operacion,
            'datos_json' => json_encode($datos),
            'fecha_cambio' => now(),
            'sincronizado' => false,
            'sede' => $sede,
            'hash_cambio' => $hash,
            'usuario_id' => $usuario_id,
        ]);
    }

    /**
     * Marcar registros como sincronizados
     */
    public static function marcarSincronizados(array $ids)
    {
        return self::whereIn('id', $ids)->update(['sincronizado' => true]);
    }

    /**
     * Obtener cambios pendientes de sincronizar
     */
    public static function obtenerPendientes($sede, $limite = 500)
    {
        return self::where('sede', $sede)
            ->where('sincronizado', false)
            ->orderBy('fecha_cambio', 'asc')
            ->limit($limite)
            ->get();
    }
}
