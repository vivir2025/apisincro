<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración de Múltiples Bases de Datos
    |--------------------------------------------------------------------------
    | 
    | Cada sede tiene su propia conexión a base de datos.
    | La API cambia dinámicamente entre estas conexiones según la sede solicitada.
    |
    */

    'default' => env('DB_CONNECTION', 'bd_morales'),

    'connections' => [

        // SEDE MORALES
        'bd_morales' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_MORALES', '127.0.0.1'),
            'port' => env('DB_PORT_MORALES', '3306'),
            'database' => env('DB_DATABASE_MORALES', 'bd_morales'),
            'username' => env('DB_USERNAME_MORALES', 'root'),
            'password' => env('DB_PASSWORD_MORALES', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // SEDE CAJIBÍO
        'bd_cajibio' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_CAJIBIO', '127.0.0.1'),
            'port' => env('DB_PORT_CAJIBIO', '3306'),
            'database' => env('DB_DATABASE_CAJIBIO', 'bd_cajibio'),
            'username' => env('DB_USERNAME_CAJIBIO', 'root'),
            'password' => env('DB_PASSWORD_CAJIBIO', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        // SEDE PIENDAMÓ
        'bd_piendamo' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST_PIENDAMO', '127.0.0.1'),
            'port' => env('DB_PORT_PIENDAMO', '3306'),
            'database' => env('DB_DATABASE_PIENDAMO', 'bd_piendamo'),
            'username' => env('DB_USERNAME_PIENDAMO', 'root'),
            'password' => env('DB_PASSWORD_PIENDAMO', ''),
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'laravel_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
