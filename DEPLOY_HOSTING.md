# 🚀 GUÍA DE DESPLIEGUE EN HOSTING

## ⚠️ IMPORTANTE
Esta API REST usa **JWT** para autenticación, **NO necesita sesiones de base de datos**.

## 📋 Pasos para Desplegar

### 1. Subir archivos al hosting
```bash
# Hacer pull desde el repositorio
git pull origin main
```

O subir vía FTP todos los archivos excepto:
- `.git/`
- `node_modules/` (si existe)

### 2. Configurar el archivo .env en el servidor

**COPIAR el contenido de `.env.production` y renombrarlo a `.env` en el hosting**

Configuración crítica:
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://apisincro.nacerparavivir.org

# IMPORTANTE: Para API con JWT usar sesiones en array
SESSION_DRIVER=array

# Conexión principal de BD
DB_CONNECTION=mysql
DB_HOST=69.195.111.236
DB_PORT=3306
DB_DATABASE=nacerpar_morales
DB_USERNAME=nacerpar_node
DB_PASSWORD=@Fnpv2025@

# JWT Secret (nunca cambiar en producción)
JWT_SECRET=yo-soy-fnpv-2026
```

### 3. Configurar permisos (si tienes SSH)
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

### 4. Limpiar caché (si tienes SSH)
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**Si NO tienes SSH:** Borra manualmente estos archivos:
- `bootstrap/cache/config.php`
- `bootstrap/cache/routes.php`
- `bootstrap/cache/services.php`

### 5. Configurar el documento raíz del dominio

**En cPanel/Plesk:** Apuntar el dominio a la carpeta `/public`

Ruta completa: `/home2/nacerpar/apisincro.nacerparavivir.org/public`

### 6. Verificar archivo .htaccess en /public

Debe contener:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 7. Verificar versión de PHP en el hosting

**Mínimo requerido: PHP 8.2**

En cPanel:
1. Ir a "Select PHP Version" o "MultiPHP Manager"
2. Seleccionar **PHP 8.2** o **PHP 8.3**
3. Activar extensiones:
   - pdo_mysql
   - mbstring
   - tokenizer
   - xml
   - ctype
   - json
   - openssl
   - bcmath

### 8. Crear las tablas necesarias en la base de datos

Ejecutar en **nacerpar_morales** (y en cada BD de las sedes):

```sql
-- Solo si usaras sesiones de BD (NO necesario con SESSION_DRIVER=array)
-- CREATE TABLE sessions (...);

-- Tablas de sincronización (REQUERIDAS)
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
```

### 9. Probar la API

#### Prueba 1: Bienvenida
```bash
GET https://apisincro.nacerparavivir.org/api/
```

Debe retornar:
```json
{
  "success": true,
  "message": "API de Sincronización - Historia Clínica",
  "version": "1.0.0"
}
```

#### Prueba 2: Login
```bash
POST https://apisincro.nacerparavivir.org/api/auth/login
Content-Type: application/json

{
  "sede": "morales",
  "usuario": "admin",
  "password": "123456"
}
```

## 🔍 Solución de Problemas

### Error: "sessions table not found"
✅ **Solución:** Cambiar `SESSION_DRIVER=database` a `SESSION_DRIVER=array` en `.env`

### Error: "Access denied for user 'root'@'localhost'"
✅ **Solución:** Verificar que el `.env` existe y tiene las credenciales correctas

### Error 500 genérico
✅ **Solución:** 
1. Borrar archivos cache: `bootstrap/cache/*.php`
2. Verificar permisos: `chmod -R 755 storage`
3. Revisar logs en: `storage/logs/laravel.log`

### La página muestra Laravel pero /api/ da error
✅ **Solución:** Verificar que el dominio apunta a `/public` y no a la raíz

## 📝 Notas Importantes

- ✅ **vendor/** está incluido en el repo (no necesitas `composer install`)
- ✅ **JWT** maneja la autenticación (no necesitas sesiones de BD)
- ✅ La carpeta `public/` debe ser el documento raíz
- ✅ Verificar que `.env` existe en el servidor (Git lo ignora)
- ✅ PHP 8.2+ es **obligatorio**
