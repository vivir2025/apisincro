<?php

namespace App\Models;

class Factura extends SyncModel
{
    protected $table = 'factura';
    public $timestamps = false;
    
    protected $fillable = [
        'numero_factura',
        'paciente_id',
        'fecha_factura',
        'empresa_id',
        'contrato_id',
        'valor_total',
        'valor_copago',
        'valor_descuento',
        'valor_neto',
        'estado',
        'observaciones',
        'usuario_registro',
        // Agregar más campos según tu BD
    ];

    protected $casts = [
        'fecha_factura' => 'date',
        'valor_total' => 'decimal:2',
        'valor_copago' => 'decimal:2',
        'valor_descuento' => 'decimal:2',
        'valor_neto' => 'decimal:2',
    ];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }
}
