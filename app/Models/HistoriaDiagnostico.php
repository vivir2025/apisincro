<?php

namespace App\Models;

class HistoriaDiagnostico extends SyncModel
{
    protected $table = 'historia_diagnostico';
    protected $primaryKey = 'idHistoriaDiagnostico';
    public $timestamps = false;
    
    protected $fillable = [
        'hc_id',
        'codigo_cie10',
        'descripcion_diagnostico',
        'tipo_diagnostico', // Principal, Relacionado, etc.
        'observaciones',
        // Agregar más campos según tu BD
    ];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
