<?php

namespace App\Models;

class Agenda extends SyncModel
{
    protected $table = 'agenda';
    public $timestamps = false;
    
    protected $fillable = [
        'fecha',
        'hora_inicio',
        'hora_fin',
        'medico_id',
        'especialidad_id',
        'sede_id',
        'consultorio',
        'cupos_disponibles',
        'estado',
        // Agregar más campos según tu BD
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function citas()
    {
        return $this->hasMany(Cita::class, 'agenda_id');
    }
}
