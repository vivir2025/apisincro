<?php

namespace App\Models;

class HistoriaMedicamento extends SyncModel
{
    protected $table = 'historia_medicamento';
    public $timestamps = false;
    
    protected $fillable = [
        'hc_id',
        'medicamento_id',
        'medicamento_nombre',
        'dosis',
        'via_administracion',
        'frecuencia',
        'duracion',
        'cantidad',
        'observaciones',
        // Agregar más campos según tu BD
    ];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
