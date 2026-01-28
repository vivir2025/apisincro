<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Sincronización
    |--------------------------------------------------------------------------
    */

    // Mapeo de sedes a conexiones de BD
    'sedes' => [
        'morales' => [
            'connection' => 'bd_morales',
            'nombre' => 'Morales',
            'rango_ids' => [
                'min' => 1000000,
                'max' => 1999999,
            ],
        ],
        'cajibio' => [
            'connection' => 'bd_cajibio',
            'nombre' => 'Cajibío',
            'rango_ids' => [
                'min' => 2000000,
                'max' => 2999999,
            ],
        ],
        'piendamo' => [
            'connection' => 'bd_piendamo',
            'nombre' => 'Piendamó',
            'rango_ids' => [
                'min' => 3000000,
                'max' => 3999999,
            ],
        ],
    ],

    // Tablas que se sincronizan
    'tablas_sincronizadas' => [
        'paciente',
        'agenda',
        'cita',
        'hc',
        'hc_complementaria',
        'historia_medicamento',
        'historia_cups',
        'historia_diagnostico',
        'historia_remision',
        'factura',
    ],

    // Configuración de sincronización
    'max_records_per_batch' => env('SYNC_MAX_RECORDS_PER_BATCH', 500),
    'timeout' => env('SYNC_TIMEOUT', 300), // segundos
    
    // Estrategia de resolución de conflictos
    // 'newest' = gana el más reciente
    // 'server' = siempre gana el servidor
    // 'manual' = requiere intervención manual
    'conflict_resolution' => 'newest',

    // Habilitar logs de sincronización
    'enable_sync_logs' => true,

];
