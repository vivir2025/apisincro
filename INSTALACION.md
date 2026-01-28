# GUÍA DE INSTALACIÓN - API REST LARAVEL

## 📦 Requisitos Previos

- PHP >= 8.0
- Composer
- MySQL
- Extensiones PHP: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON

## 🚀 Pasos de Instalación

### 1. Instalar Laravel (si no está instalado)

```bash
cd c:\xampp\htdocs\ips\api-sync-laravel
composer install
```

O crear proyecto desde cero:
```bash
composer create-project laravel/laravel api-sync-laravel
```

### 2. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
copy .env.example .env

# Generar key de aplicación
php artisan key:generate
```

### 3. Editar el archivo .env

```env
DB_HOST_MORALES=localhost
DB_DATABASE_MORALES=bd_morales
DB_USERNAME_MORALES=root
DB_PASSWORD_MORALES=

DB_HOST_CAJIBIO=localhost
DB_DATABASE_CAJIBIO=bd_cajibio
DB_USERNAME_CAJIBIO=root
DB_PASSWORD_CAJIBIO=

DB_HOST_PIENDAMO=localhost
DB_DATABASE_PIENDAMO=bd_piendamo
DB_USERNAME_PIENDAMO=root
DB_PASSWORD_PIENDAMO=

JWT_SECRET=tu_secreto_super_seguro_cambiar_esto
```

### 4. Ejecutar Migraciones en CADA Base de Datos

```bash
# Ejecutar en bd_morales
php artisan migrate --database=bd_morales

# Ejecutar en bd_cajibio
php artisan migrate --database=bd_cajibio

# Ejecutar en bd_piendamo
php artisan migrate --database=bd_piendamo
```

O ejecutar manualmente el SQL en cada BD:

```sql
-- Ejecutar en bd_morales, bd_cajibio y bd_piendamo

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
    INDEX idx_sede (sede),
    INDEX idx_sync_lookup (sincronizado, sede, fecha_cambio)
);

CREATE TABLE sync_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    ultimo_id_sincronizado INT DEFAULT 0,
    ultima_sincronizacion TIMESTAMP NULL,
    sede VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_tabla_sede (tabla, sede),
    INDEX idx_sede (sede)
);
```

### 5. Registrar Middleware

Editar `bootstrap/app.php` o `app/Http/Kernel.php`:

```php
// En bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'validar.sede' => \App\Http\Middleware\ValidarSede::class,
    ]);
})

// O en app/Http/Kernel.php (Laravel 10)
protected $middlewareAliases = [
    // ...
    'validar.sede' => \App\Http\Middleware\ValidarSede::class,
];
```

### 6. Iniciar Servidor de Desarrollo

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

La API estará disponible en: `http://localhost:8000/api`

## 🧪 Probar la API

### 1. Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"sede\":\"morales\",\"usuario\":\"admin\",\"password\":\"123456\"}"
```

Respuesta:
```json
{
  "success": true,
  "token": "eyJ1c2VyX2lk....",
  "expires_in": 86400,
  "sede": "morales"
}
```

### 2. Verificar Actualizaciones

```bash
curl -X POST http://localhost:8000/api/sync/check-updates \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d "{\"sede\":\"morales\",\"ultimos_ids\":{\"paciente\":100,\"cita\":50}}"
```

### 3. Subir Cambios

```bash
curl -X POST http://localhost:8000/api/sync/upload \
  -H "Authorization: Bearer TU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d "{\"sede\":\"morales\",\"cambios\":[{\"tabla\":\"paciente\",\"operacion\":\"INSERT\",\"registro_id\":101,\"datos\":{\"id\":101,\"nombre\":\"Juan\"}}]}"
```

### 4. Estado de Sincronización

```bash
curl http://localhost:8000/api/sync/status/morales \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

## 📋 Crear Triggers Automáticos (Opcional pero Recomendado)

Para que los cambios se registren automáticamente en `sync_log`:

```sql
-- EJEMPLO: Trigger para tabla paciente en bd_morales

DELIMITER $$

CREATE TRIGGER paciente_after_insert
AFTER INSERT ON paciente
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (tabla, registro_id, operacion, datos_json, sede, hash_cambio)
    VALUES (
        'paciente',
        NEW.id,
        'INSERT',
        JSON_OBJECT(
            'id', NEW.id,
            'numero_documento', NEW.numero_documento,
            'primer_nombre', NEW.primer_nombre,
            'primer_apellido', NEW.primer_apellido
            -- Agregar más campos...
        ),
        'morales',
        SHA2(CONCAT('paciente', NEW.id, 'INSERT', NOW()), 256)
    );
END$$

CREATE TRIGGER paciente_after_update
AFTER UPDATE ON paciente
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (tabla, registro_id, operacion, datos_json, sede, hash_cambio)
    VALUES (
        'paciente',
        NEW.id,
        'UPDATE',
        JSON_OBJECT(
            'id', NEW.id,
            'numero_documento', NEW.numero_documento,
            'primer_nombre', NEW.primer_nombre,
            'primer_apellido', NEW.primer_apellido
            -- Agregar más campos...
        ),
        'morales',
        SHA2(CONCAT('paciente', NEW.id, 'UPDATE', NOW()), 256)
    );
END$$

CREATE TRIGGER paciente_after_delete
AFTER DELETE ON paciente
FOR EACH ROW
BEGIN
    INSERT INTO sync_log (tabla, registro_id, operacion, datos_json, sede, hash_cambio)
    VALUES (
        'paciente',
        OLD.id,
        'DELETE',
        JSON_OBJECT('id', OLD.id),
        'morales',
        SHA2(CONCAT('paciente', OLD.id, 'DELETE', NOW()), 256)
    );
END$$

DELIMITER ;
```

**Repetir para todas las tablas sincronizadas:**
- paciente
- agenda
- cita
- hc
- hc_complementaria
- historia_medicamento
- historia_cups
- historia_diagnostico
- historia_remision
- factura

## 🔒 Seguridad en Producción

1. **Cambiar JWT_SECRET** en .env
2. **Usar HTTPS** obligatoriamente
3. **Configurar CORS** si accedes desde dominios diferentes
4. **Limitar rate limiting** para evitar abuso
5. **Logs de auditoría** habilitados

## 📝 Logs

Los logs se guardan en `storage/logs/laravel.log`

```bash
# Ver logs en tiempo real
tail -f storage/logs/laravel.log
```

## 🐛 Solución de Problemas Comunes

### Error: "Class not found"
```bash
composer dump-autoload
```

### Error: "Permission denied" en storage/
```bash
chmod -R 775 storage bootstrap/cache
```

### Error de conexión a BD
- Verificar credenciales en .env
- Comprobar que el servidor MySQL esté corriendo
- Verificar nombres de bases de datos

## ✅ Checklist Final

- [ ] Composer instalado
- [ ] .env configurado con las 3 bases de datos
- [ ] Migraciones ejecutadas en las 3 BDs
- [ ] Servidor iniciado en puerto 8000
- [ ] Login funcionando
- [ ] Endpoints de sincronización respondiendo
- [ ] Triggers creados (opcional)

## 🎯 Siguiente Paso

Integrar la API con CodeIgniter para que los servidores locales puedan sincronizar.

Ver: `GUIA_INTEGRACION_CODEIGNITER.md`
