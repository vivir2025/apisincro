<?php

namespace App\Models;

class HistoriaCups extends SyncModel
{
    protected $table = 'historia_cups';
    protected $primaryKey = 'id_historia_cups';
    public $timestamps = false;
    
    protected $fillable = [
        'hc_id',
        'cups_id',
        'cups_codigo',
        'cups_descripcion',
        'cantidad',
        'observaciones',
        // Agregar más campos según tu BD
    ];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
