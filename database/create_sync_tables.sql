-- =====================================================
-- CREAR TABLAS DE SINCRONIZACIÓN EN EL SERVIDOR
-- Ejecutar este SQL en nacerpar_morales (y en las otras BDs)
-- =====================================================

-- 1. Tabla de control de sincronización
CREATE TABLE IF NOT EXISTS sync_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    ultimo_id_sincronizado INT DEFAULT 0,
    ultima_sincronizacion TIMESTAMP NULL,
    sede VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_tabla_sede (tabla, sede),
    INDEX idx_sede (sede),
    INDEX idx_tabla (tabla)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de log de sincronización
CREATE TABLE IF NOT EXISTS sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    registro_id INT NOT NULL,
    operacion ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    datos_json TEXT,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sincronizado TINYINT(1) DEFAULT 0,
    sede VARCHAR(50),
    hash_cambio VARCHAR(64) UNIQUE,
    usuario_id INT NULL,
    INDEX idx_tabla (tabla),
    INDEX idx_registro (registro_id),
    INDEX idx_sincronizado (sincronizado),
    INDEX idx_sede (sede),
    INDEX idx_sync_lookup (sincronizado, sede, fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificar que se crearon correctamente
SELECT 'Tablas creadas exitosamente' AS mensaje;
SHOW TABLES LIKE 'sync%';
