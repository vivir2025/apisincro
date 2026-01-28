<?php

namespace App\Models;

class HistoriaRemision extends SyncModel
{
    protected $table = 'historia_remision';
    protected $primaryKey = 'id_historia_remision';
    public $timestamps = false;
    
    protected $fillable = [
        'hc_id',
        'especialidad_destino',
        'institucion_destino',
        'motivo_remision',
        'fecha_remision',
        'observaciones',
        // Agregar más campos según tu BD
    ];

    protected $casts = [
        'fecha_remision' => 'date',
    ];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
