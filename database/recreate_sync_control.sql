-- =====================================================
-- REEMPLAZAR TABLA sync_control CON LA ESTRUCTURA CORRECTA
-- =====================================================

-- 1. Hacer backup de la tabla actual (por si acaso)
CREATE TABLE IF NOT EXISTS sync_control_old_backup AS SELECT * FROM sync_control;

-- 2. Eliminar la tabla actual
DROP TABLE IF EXISTS sync_control;

-- 3. Crear la tabla con la estructura correcta
CREATE TABLE sync_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    ultimo_id_sincronizado INT DEFAULT 0,
    ultima_sincronizacion TIMESTAMP NULL,
    sede VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_tabla_sede (tabla, sede),
    INDEX idx_sede (sede),
    INDEX idx_tabla (tabla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Verificar estructura
DESCRIBE sync_control;

-- 5. Verificar sync_log también
DESCRIBE sync_log;
