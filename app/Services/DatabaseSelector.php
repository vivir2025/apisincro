<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Servicio para cambiar dinámicamente la conexión de BD según la sede
 */
class DatabaseSelector
{
    /**
     * Obtener el nombre de la conexión de BD para una sede
     */
    public static function getConnection($sede)
    {
        $sedes = config('sync.sedes');
        
        if (!isset($sedes[$sede])) {
            throw new \Exception("Sede '{$sede}' no válida. Sedes disponibles: " . implode(', ', array_keys($sedes)));
        }
        
        return $sedes[$sede]['connection'];
    }

    /**
     * Cambiar la conexión de BD por defecto a una sede específica
     */
    public static function setConnection($sede)
    {
        $connection = self::getConnection($sede);
        
        // Cambiar la conexión por defecto
        Config::set('database.default', $connection);
        
        // Limpiar y reconectar
        DB::purge($connection);
        DB::reconnect($connection);
        
        return $connection;
    }

    /**
     * Obtener todas las sedes disponibles
     */
    public static function getSedes()
    {
        return array_keys(config('sync.sedes'));
    }

    /**
     * Validar si una sede existe
     */
    public static function sedeExiste($sede)
    {
        return in_array($sede, self::getSedes());
    }

    /**
     * Obtener información completa de una sede
     */
    public static function getSedeInfo($sede)
    {
        $sedes = config('sync.sedes');
        return $sedes[$sede] ?? null;
    }

    /**
     * Obtener el rango de IDs permitido para una sede
     */
    public static function getRangoIds($sede)
    {
        $info = self::getSedeInfo($sede);
        return $info['rango_ids'] ?? null;
    }

    /**
     * Validar si un ID está en el rango permitido de una sede
     */
    public static function idEnRango($id, $sede)
    {
        $rango = self::getRangoIds($sede);
        
        if (!$rango) {
            return true; // Si no hay rango definido, permitir cualquier ID
        }
        
        return $id >= $rango['min'] && $id <= $rango['max'];
    }
}
