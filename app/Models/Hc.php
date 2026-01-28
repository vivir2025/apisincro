<?php

namespace App\Models;

class Hc extends SyncModel
{
    protected $table = 'hc';
    protected $primaryKey = 'id_hc';
    public $timestamps = false;
    
    protected $fillable = [
        'paciente_id',
        'fecha_consulta',
        'medico_id',
        'motivo_consulta',
        'enfermedad_actual',
        'antecedentes',
        'examen_fisico',
        'tension_arterial',
        'frecuencia_cardiaca',
        'temperatura',
        'peso',
        'talla',
        'imc',
        'analisis',
        'plan_tratamiento',
        'usuario_registro',
        // Agregar más campos según tu BD
    ];

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
