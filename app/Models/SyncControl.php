<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla de control de última sincronización por tabla
 */
class SyncControl extends Model
{
    protected $table = 'sync_control';
    public $timestamps = false;
    
    protected $fillable = [
        'tabla',
        'ultimo_id_sincronizado',
        'ultima_sincronizacion',
        'sede',
    ];

    protected $casts = [
        'ultima_sincronizacion' => 'datetime',
    ];

    /**
     * Actualizar último ID sincronizado de una tabla
     */
    public static function actualizarUltimoId($tabla, $ultimo_id, $sede)
    {
        return self::updateOrCreate(
            ['tabla' => $tabla, 'sede' => $sede],
            [
                'ultimo_id_sincronizado' => $ultimo_id,
                'ultima_sincronizacion' => now(),
            ]
        );
    }

    /**
     * Obtener último ID sincronizado de una tabla
     */
    public static function obtenerUltimoId($tabla, $sede)
    {
        $control = self::where('tabla', $tabla)
            ->where('sede', $sede)
            ->first();
        
        return $control ? $control->ultimo_id_sincronizado : 0;
    }

    /**
     * Obtener todos los últimos IDs de una sede
     */
    public static function obtenerTodosUltimosIds($sede)
    {
        $controles = self::where('sede', $sede)->get();
        $resultado = [];
        
        foreach ($controles as $control) {
            $resultado[$control->tabla] = $control->ultimo_id_sincronizado;
        }
        
        return $resultado;
    }
}
