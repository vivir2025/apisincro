<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo base para sincronización
 * Todos los modelos sincronizables heredan de esta clase
 */
class SyncModel extends Model
{
    /**
     * Cambiar la conexión de BD dinámicamente según la sede
     */
    public function setSede($sede)
    {
        $connection = config("sync.sedes.{$sede}.connection");
        
        if (!$connection) {
            throw new \Exception("Sede '{$sede}' no válida");
        }
        
        $this->setConnection($connection);
        return $this;
    }

    /**
     * Obtener la sede actual basada en la conexión
     */
    public function getSedeActual()
    {
        $currentConnection = $this->getConnectionName();
        
        foreach (config('sync.sedes') as $sede => $config) {
            if ($config['connection'] === $currentConnection) {
                return $sede;
            }
        }
        
        return null;
    }
}
