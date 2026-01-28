-- =====================================================
-- SCRIPT DE INSTALACIÓN PARA BASE DE DATOS DEL HOSTING
-- =====================================================
-- Este script crea SOLO las tablas necesarias en el hosting
-- SIN triggers, porque la API Laravel controla las escrituras
-- =====================================================

-- 1. TABLAS DE CONTROL DE SINCRONIZACIÓN
-- =====================================================

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

-- =====================================================
-- 2. TABLA DE USUARIOS (para autenticación)
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    sede VARCHAR(50) NOT NULL,
    remember_token VARCHAR(100),
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_sede (sede)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuarios de ejemplo (cambiar contraseñas en producción)
INSERT INTO users (name, email, password, sede) VALUES
('Admin Morales', 'admin@morales.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'morales'),
('Admin Cajibío', 'admin@cajibio.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cajibio'),
('Admin Piéndamo', 'admin@piendamo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'piendamo');
-- Contraseña por defecto: "password" (cambiar en producción)

-- =====================================================
-- 3. TABLAS DE DATOS PRINCIPALES
-- =====================================================

-- PACIENTE
CREATE TABLE IF NOT EXISTS paciente (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_documento VARCHAR(20) NOT NULL,
    tipo_documento VARCHAR(10),
    primer_nombre VARCHAR(50) NOT NULL,
    segundo_nombre VARCHAR(50),
    primer_apellido VARCHAR(50) NOT NULL,
    segundo_apellido VARCHAR(50),
    fecha_nacimiento DATE,
    sexo CHAR(1),
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    email VARCHAR(100),
    estado VARCHAR(20) DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_documento (numero_documento),
    INDEX idx_nombres (primer_nombre, primer_apellido),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AGENDA
CREATE TABLE IF NOT EXISTS agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    medico_id INT,
    consultorio VARCHAR(50),
    estado VARCHAR(20) DEFAULT 'disponible',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_medico (medico_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- CITA
CREATE TABLE IF NOT EXISTS cita (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    agenda_id INT,
    fecha_cita DATE NOT NULL,
    hora_cita TIME NOT NULL,
    estado VARCHAR(20) DEFAULT 'programada',
    tipo_consulta VARCHAR(50),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_paciente (paciente_id),
    INDEX idx_agenda (agenda_id),
    INDEX idx_fecha (fecha_cita),
    INDEX idx_estado (estado),
    FOREIGN KEY (paciente_id) REFERENCES paciente(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HISTORIA CLÍNICA
CREATE TABLE IF NOT EXISTS hc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    cita_id INT,
    fecha_consulta DATE NOT NULL,
    motivo_consulta TEXT,
    enfermedad_actual TEXT,
    examen_fisico TEXT,
    analisis TEXT,
    plan_tratamiento TEXT,
    medico_id INT,
    estado VARCHAR(20) DEFAULT 'abierta',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_paciente (paciente_id),
    INDEX idx_cita (cita_id),
    INDEX idx_fecha (fecha_consulta),
    INDEX idx_medico (medico_id),
    FOREIGN KEY (paciente_id) REFERENCES paciente(id) ON DELETE CASCADE,
    FOREIGN KEY (cita_id) REFERENCES cita(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HC COMPLEMENTARIA
CREATE TABLE IF NOT EXISTS hc_complementaria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hc_id INT NOT NULL,
    antecedentes_personales TEXT,
    antecedentes_familiares TEXT,
    alergias TEXT,
    medicamentos_actuales TEXT,
    habitos TEXT,
    revision_sistemas TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hc (hc_id),
    FOREIGN KEY (hc_id) REFERENCES hc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HISTORIA MEDICAMENTO
CREATE TABLE IF NOT EXISTS historia_medicamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hc_id INT NOT NULL,
    medicamento_id INT,
    nombre_medicamento VARCHAR(200) NOT NULL,
    dosis VARCHAR(100),
    via_administracion VARCHAR(50),
    frecuencia VARCHAR(100),
    duracion VARCHAR(100),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hc (hc_id),
    INDEX idx_medicamento (medicamento_id),
    FOREIGN KEY (hc_id) REFERENCES hc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HISTORIA CUPS
CREATE TABLE IF NOT EXISTS historia_cups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hc_id INT NOT NULL,
    cups_id INT,
    codigo_cups VARCHAR(20),
    nombre_procedimiento VARCHAR(200) NOT NULL,
    cantidad INT DEFAULT 1,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hc (hc_id),
    INDEX idx_cups (cups_id),
    INDEX idx_codigo (codigo_cups),
    FOREIGN KEY (hc_id) REFERENCES hc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HISTORIA DIAGNÓSTICO
CREATE TABLE IF NOT EXISTS historia_diagnostico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hc_id INT NOT NULL,
    diagnostico_id INT,
    codigo_cie10 VARCHAR(10),
    nombre_diagnostico VARCHAR(200) NOT NULL,
    tipo_diagnostico VARCHAR(50),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hc (hc_id),
    INDEX idx_diagnostico (diagnostico_id),
    INDEX idx_codigo (codigo_cie10),
    FOREIGN KEY (hc_id) REFERENCES hc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HISTORIA REMISIÓN
CREATE TABLE IF NOT EXISTS historia_remision (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hc_id INT NOT NULL,
    especialidad VARCHAR(100),
    institucion VARCHAR(200),
    motivo_remision TEXT,
    prioridad VARCHAR(20),
    estado VARCHAR(20) DEFAULT 'pendiente',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_hc (hc_id),
    INDEX idx_estado (estado),
    FOREIGN KEY (hc_id) REFERENCES hc(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FACTURA
CREATE TABLE IF NOT EXISTS factura (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    cita_id INT,
    numero_factura VARCHAR(50) UNIQUE,
    fecha_factura DATE NOT NULL,
    valor_total DECIMAL(10,2) DEFAULT 0,
    estado_pago VARCHAR(20) DEFAULT 'pendiente',
    forma_pago VARCHAR(50),
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_paciente (paciente_id),
    INDEX idx_cita (cita_id),
    INDEX idx_numero (numero_factura),
    INDEX idx_fecha (fecha_factura),
    INDEX idx_estado (estado_pago),
    FOREIGN KEY (paciente_id) REFERENCES paciente(id) ON DELETE CASCADE,
    FOREIGN KEY (cita_id) REFERENCES cita(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. VERIFICACIÓN
-- =====================================================

-- Ver tablas creadas
SHOW TABLES;

-- Ver estructura de sync_log
DESCRIBE sync_log;

-- Ver estructura de sync_control
DESCRIBE sync_control;

-- Ver usuarios creados
SELECT id, name, email, sede FROM users;

-- =====================================================
-- ⚠️ IMPORTANTE: NO INSTALAR TRIGGERS EN ESTA BD
-- =====================================================
-- Esta base de datos del hosting NO necesita triggers
-- porque la API Laravel controla todas las escrituras.
-- 
-- Los triggers solo deben estar en:
-- - bd_morales (local)
-- - bd_cajibio (local)
-- - bd_piendamo (local)
-- =====================================================
