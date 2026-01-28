<?php

namespace App\Models;

class Cita extends SyncModel
{
    protected $table = 'cita';
    protected $primaryKey = 'idcita';
    public $timestamps = false;
    
    protected $fillable = [
        'paciente_id',
        'agenda_id',
        'fecha_cita',
        'hora_cita',
        'motivo_consulta',
        'estado',
        'observaciones',
        'usuario_registro',
        // Agregar más campos según tu BD
    ];

    protected $casts = [
        'fecha_cita' => 'date',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function agenda()
    {
        return $this->belongsTo(Agenda::class, 'agenda_id');
    }
}
