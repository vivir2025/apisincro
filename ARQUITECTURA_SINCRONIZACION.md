# 🌐 ARQUITECTURA DE SINCRONIZACIÓN

## 📊 Esquema de Bases de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    🌐 HOSTING (INTERNET)                     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  API REST Laravel (Puerto 8000)                    │     │
│  │  - Recibe cambios de las sedes                     │     │
│  │  - Consolida datos                                 │     │
│  │  - Redistribuye a otras sedes                      │     │
│  └────────────────────────────────────────────────────┘     │
│                          ↕                                   │
│  ┌────────────────────────────────────────────────────┐     │
│  │  BD Central (MySQL Hosting)                        │     │
│  │  - Almacena datos maestros                         │     │
│  │  - sync_log (opcional)                             │     │
│  │  - sync_control                                    │     │
│  │  ❌ SIN TRIGGERS (solo API escribe aquí)           │     │
│  └────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────┘
                          ↕ (Internet)
┌─────────────────────────────────────────────────────────────┐
│                    💻 SEDES LOCALES                          │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Morales     │  │  Cajibío     │  │  Piéndamo    │      │
│  │              │  │              │  │              │      │
│  │ CodeIgniter  │  │ CodeIgniter  │  │ CodeIgniter  │      │
│  │    ↕         │  │    ↕         │  │    ↕         │      │
│  │ bd_morales   │  │ bd_cajibio   │  │ bd_piendamo  │      │
│  │ ✅ TRIGGERS  │  │ ✅ TRIGGERS  │  │ ✅ TRIGGERS  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎯 ¿Dónde instalar los triggers?

### ✅ **INSTALAR TRIGGERS EN:**

1. **bd_morales** (local XAMPP Morales)
   ```sql
   SET @sede_actual = 'morales';
   -- Ejecutar triggers_sincronizacion.sql
   ```

2. **bd_cajibio** (local XAMPP Cajibío)
   ```sql
   SET @sede_actual = 'cajibio';
   -- Ejecutar triggers_sincronizacion.sql
   ```

3. **bd_piendamo** (local XAMPP Piéndamo)
   ```sql
   SET @sede_actual = 'piendamo';
   -- Ejecutar triggers_sincronizacion.sql
   ```

### ❌ **NO INSTALAR TRIGGERS EN:**

4. **bd_central** (MySQL del hosting)
   - Solo crear tablas `sync_log` y `sync_control`
   - NO instalar triggers
   - La API Laravel se encarga de escribir aquí

---

## 🔄 Flujo de Sincronización

### Caso 1: Usuario en Morales crea un paciente

```
1. Usuario → Registro en bd_morales
2. Trigger → Inserta en sync_log (bd_morales)
3. Botón sincronizar → CodeIgniter llama API
4. API → Lee sync_log de bd_morales
5. API → Escribe en bd_central (hosting)
6. API → Marca como sincronizado
```

### Caso 2: Usuario en Cajibío necesita ver paciente de Morales

```
1. Botón sincronizar → CodeIgniter llama API
2. API → Verifica cambios en bd_central
3. API → Envía cambios a bd_cajibio
4. CodeIgniter → Inserta en bd_cajibio
5. Usuario ve el paciente
```

---

## 📦 Instalación en Hosting

### Paso 1: Crear Base de Datos en Hosting

En tu panel de hosting (cPanel, Plesk, etc.):

```sql
CREATE DATABASE bd_central;
```

### Paso 2: Crear Tablas (SIN triggers)

```sql
-- Ejecutar en bd_central del hosting

-- Tabla de control de sincronización
CREATE TABLE sync_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    ultimo_id_sincronizado INT DEFAULT 0,
    ultima_sincronizacion TIMESTAMP NULL,
    sede VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_tabla_sede (tabla, sede),
    INDEX idx_sede (sede)
);

-- Tabla de log (opcional en hosting, pero útil para auditoría)
CREATE TABLE sync_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    registro_id INT NOT NULL,
    operacion ENUM('INSERT', 'UPDATE', 'DELETE'),
    datos_json TEXT,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sincronizado TINYINT(1) DEFAULT 0,
    sede VARCHAR(50),
    hash_cambio VARCHAR(64) UNIQUE,
    usuario_id INT NULL,
    INDEX idx_tabla (tabla),
    INDEX idx_registro (registro_id),
    INDEX idx_sincronizado (sincronizado),
    INDEX idx_sede (sede)
);

-- Crear todas las tablas de datos
-- (paciente, agenda, cita, hc, etc.)
-- Ejecutar tu script de creación de tablas normal
```

### Paso 3: NO instalar triggers en hosting

⚠️ **IMPORTANTE:** NO ejecutar `triggers_sincronizacion.sql` en el hosting.

**Razón:** La API Laravel ya controla todas las escrituras en el hosting.

---

## ⚙️ Configuración de la API

En tu archivo `.env` del hosting:

```env
# Base de datos del HOSTING (solo esta)
DB_CONNECTION=mysql
DB_HOST=localhost  # o tu host del hosting
DB_PORT=3306
DB_DATABASE=bd_central
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

# JWT para autenticación
JWT_SECRET=tu_secreto_super_seguro

# Configuración de producción
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
```

---

## 🔐 Caso Especial: Edición Directa en Hosting

**¿Y si necesitas editar directamente en la BD del hosting?**

### Opción A: Usar la API (recomendado)
- Crear endpoints adicionales para CRUD
- La API registra los cambios
- Mantiene consistencia

### Opción B: Instalar triggers también en hosting
Si realmente necesitas editar directamente:

```sql
-- Solo en bd_central del hosting
SET @sede_actual = 'central'; -- o 'hosting'

-- Ejecutar triggers_sincronizacion.sql
```

⚠️ **Cuidado con conflictos:**
- Si se edita el mismo registro en local y hosting
- Necesitarás resolución de conflictos
- Recomiendo usar timestamps y "last write wins"

---

## 📝 Resumen

| Base de Datos | Ubicación | Triggers | Descripción |
|---------------|-----------|----------|-------------|
| bd_morales | XAMPP Local | ✅ SÍ | Registra cambios locales |
| bd_cajibio | XAMPP Local | ✅ SÍ | Registra cambios locales |
| bd_piendamo | XAMPP Local | ✅ SÍ | Registra cambios locales |
| bd_central | Hosting MySQL | ❌ NO | Solo API escribe aquí |

---

## 🎯 Ventajas de esta arquitectura

✅ **Sin duplicación:** Un cambio no se registra 2 veces  
✅ **Sin conflictos:** El hosting es el árbitro central  
✅ **Auditoría clara:** Sabes de qué sede vino cada cambio  
✅ **Offline-first:** Las sedes trabajan sin internet  
✅ **Sincronización manual:** El usuario controla cuándo sincronizar  

---

## 🚀 Próximos pasos

1. ✅ Instalar triggers en las 3 BDs locales
2. ✅ Crear bd_central en hosting (sin triggers)
3. ✅ Subir API Laravel al hosting
4. ✅ Configurar .env en hosting
5. ⏳ Crear controlador de sincronización en CodeIgniter
6. ⏳ Agregar botón "Sincronizar" en la interfaz

---

¿Quieres que te ayude con alguno de los siguientes?

1. 📤 Script para subir la API al hosting
2. 🔧 Controlador de sincronización en CodeIgniter
3. 🎨 Interfaz con botón de sincronizar
4. 🧪 Script de pruebas de sincronización
