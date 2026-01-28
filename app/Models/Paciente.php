<?php

namespace App\Models;

class Paciente extends SyncModel
{
    protected $table = 'paciente';
    protected $primaryKey = 'idPaciente';
    public $timestamps = false;
    
    protected $fillable = [
        'tipo_documento_id',
        'numero_documento',
        'primer_nombre',
        'segundo_nombre',
        'primer_apellido',
        'segundo_apellido',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'celular',
        'direccion',
        'email',
        'departamento_id',
        'municipio_id',
        'regimen_id',
        'empresa_id',
        'estado',
        // Agregar más campos según tu BD
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Relaciones
     */
    public function citas()
    {
        return $this->hasMany(Cita::class, 'paciente_id');
    }

    public function historias()
    {
        return $this->hasMany(Hc::class, 'paciente_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'paciente_id');
    }
}
