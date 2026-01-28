<?php

namespace App\Models;

class Hc extends SyncModel
{
    protected $table = 'hc';
    protected $primaryKey = 'id_hc';
    public $timestamps = false;
    
    // Permitir sincronización de todos los campos
    protected $guarded = [];

    protected $casts = [
        'fecha_consulta' => 'datetime',
        'peso' => 'decimal:2',
        'talla' => 'decimal:2',
        'imc' => 'decimal:2',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function complementaria()
    {
        return $this->hasOne(HcComplementaria::class, 'hc_id');
    }

    public function medicamentos()
    {
        return $this->hasMany(HistoriaMedicamento::class, 'hc_id');
    }

    public function cups()
    {
        return $this->hasMany(HistoriaCups::class, 'hc_id');
    }

    public function diagnosticos()
    {
        return $this->hasMany(HistoriaDiagnostico::class, 'hc_id');
    }

    public function remisiones()
    {
        return $this->hasMany(HistoriaRemision::class, 'hc_id');
    }
}
