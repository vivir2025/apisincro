<?php

namespace App\Models;

class HcComplementaria extends SyncModel
{
    protected $table = 'hc_complementaria';
    protected $primaryKey = 'idHcComplementaria';
    public $timestamps = false;
    
    protected $fillable = [
        'hc_id',
        'antecedentes_familiares',
        'antecedentes_personales',
        'habitos',
        'gineco_obstetricos',
        'revision_sistemas',
        // Agregar más campos según tu BD
    ];

    public function historiaClinica()
    {
        return $this->belongsTo(Hc::class, 'hc_id');
    }
}
