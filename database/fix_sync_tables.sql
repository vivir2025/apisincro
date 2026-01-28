-- =====================================================
-- ACTUALIZAR/CORREGIR TABLAS DE SINCRONIZACIÓN
-- Si las tablas ya existen pero tienen estructura incorrecta
-- =====================================================

-- Primero, hacer backup si es necesario
-- CREATE TABLE sync_control_backup AS SELECT * FROM sync_control;
-- CREATE TABLE sync_log_backup AS SELECT * FROM sync_log;

-- Opción 1: Eliminar y recrear (CUIDADO: perderás datos)
-- DROP TABLE IF EXISTS sync_control;
-- DROP TABLE IF EXISTS sync_log;

-- Opción 2: Agregar columnas faltantes si no existen
ALTER TABLE sync_control 
ADD COLUMN IF NOT EXISTS tabla VARCHAR(100) NOT NULL AFTER id,
ADD COLUMN IF NOT EXISTS ultimo_id_sincronizado INT DEFAULT 0 AFTER tabla,
ADD COLUMN IF NOT EXISTS ultima_sincronizacion TIMESTAMP NULL AFTER ultimo_id_sincronizado,
ADD COLUMN IF NOT EXISTS sede VARCHAR(50) NOT NULL AFTER ultima_sincronizacion;

-- Agregar índices si no existen
ALTER TABLE sync_control 
ADD UNIQUE INDEX IF NOT EXISTS unique_tabla_sede (tabla, sede),
ADD INDEX IF NOT EXISTS idx_sede (sede),
ADD INDEX IF NOT EXISTS idx_tabla (tabla);

ALTER TABLE sync_log 
ADD COLUMN IF NOT EXISTS tabla VARCHAR(100) NOT NULL AFTER id,
ADD COLUMN IF NOT EXISTS registro_id INT NOT NULL AFTER tabla,
ADD COLUMN IF NOT EXISTS operacion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL AFTER registro_id,
ADD COLUMN IF NOT EXISTS datos_json TEXT AFTER operacion,
ADD COLUMN IF NOT EXISTS fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER datos_json,
ADD COLUMN IF NOT EXISTS sincronizado TINYINT(1) DEFAULT 0 AFTER fecha_cambio,
ADD COLUMN IF NOT EXISTS sede VARCHAR(50) AFTER sincronizado,
ADD COLUMN IF NOT EXISTS hash_cambio VARCHAR(64) AFTER sede,
ADD COLUMN IF NOT EXISTS usuario_id INT NULL AFTER hash_cambio;

-- Agregar índices a sync_log
ALTER TABLE sync_log 
ADD UNIQUE INDEX IF NOT EXISTS idx_hash_cambio (hash_cambio),
ADD INDEX IF NOT EXISTS idx_tabla (tabla),
ADD INDEX IF NOT EXISTS idx_registro (registro_id),
ADD INDEX IF NOT EXISTS idx_sincronizado (sincronizado),
ADD INDEX IF NOT EXISTS idx_sede (sede);

-- Verificar que las tablas tienen las columnas correctas
SELECT 'Verificando sync_control' AS mensaje;
SHOW COLUMNS FROM sync_control;

SELECT 'Verificando sync_log' AS mensaje;
SHOW COLUMNS FROM sync_log;
