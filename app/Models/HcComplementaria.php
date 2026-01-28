<?php

namespace App\Models;

class HcComplementaria extends SyncModel
{
    protected $table = 'hc_complementaria';
    protected $primaryKey = 'id_hc_complementaria';
    public $timestamps = false;
    
    // Permitir asignación masiva de todos los campos para sincronización
    protected $guarded = [];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
