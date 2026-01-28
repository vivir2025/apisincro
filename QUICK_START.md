# 🎯 GUÍA RÁPIDA - CÓMO USAR LA API

## ✅ PASOS PARA EMPEZAR

### 1️⃣ Instalar y Configurar

```bash
# Ir al directorio
cd c:\xampp\htdocs\ips\api-sync-laravel

# Instalar dependencias (solo primera vez)
composer install

# Copiar .env
copy .env.example .env

# Generar key
php artisan key:generate

# Editar .env con tus bases de datos
notepad .env
```

### 2️⃣ Configurar .env

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

JWT_SECRET=cambiar_esto_por_algo_seguro
```

### 3️⃣ Crear Tablas de Sincronización

Ejecutar en **cada** base de datos (morales, cajibio, piendamo):

```sql
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
    INDEX idx_sincronizado (sincronizado),
    INDEX idx_sede (sede)
);

CREATE TABLE sync_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL,
    ultimo_id_sincronizado INT DEFAULT 0,
    ultima_sincronizacion TIMESTAMP NULL,
    sede VARCHAR(50) NOT NULL,
    UNIQUE KEY unique_tabla_sede (tabla, sede)
);
```

### 4️⃣ Iniciar el Servidor

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

✅ API corriendo en: `http://localhost:8000/api`

---

## 🧪 PROBAR LA API

### Prueba 1: Login

```bash
curl -X POST http://localhost:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"sede\":\"morales\",\"usuario\":\"admin\",\"password\":\"tu_password\"}"
```

**Guarda el token que te devuelve!**

### Prueba 2: Test de Conexión

```bash
curl -X POST http://localhost:8000/api/sync/test ^
  -H "Authorization: Bearer TU_TOKEN_AQUI" ^
  -H "Content-Type: application/json" ^
  -d "{\"sede\":\"morales\"}"
```

---

## 🔄 FLUJO DE SINCRONIZACIÓN

### Desde CodeIgniter Local → Servidor Principal

```php
// En tu servidor local (XAMPP)
$api_url = 'http://tu-servidor.com:8000/api';
$token = 'tu_token_de_login';

// 1. Obtener cambios pendientes de sync_log
$cambios = $this->db->query("
    SELECT * FROM sync_log 
    WHERE sincronizado = 0 
    LIMIT 100
")->result_array();

// 2. Preparar datos para enviar
$datos_enviar = [];
foreach ($cambios as $c) {
    $datos_enviar[] = [
        'tabla' => $c['tabla'],
        'registro_id' => $c['registro_id'],
        'operacion' => $c['operacion'],
        'datos' => json_decode($c['datos_json'], true),
        'sync_log_id' => $c['id']
    ];
}

// 3. Enviar a la API
$ch = curl_init($api_url . '/sync/upload');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'sede' => 'morales',
    'cambios' => $datos_enviar
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$resultado = json_decode($response, true);

// 4. Marcar como sincronizados
if ($resultado['success']) {
    $ids = array_column($datos_enviar, 'sync_log_id');
    $this->db->where_in('id', $ids)->update('sync_log', ['sincronizado' => 1]);
}
```

---

## 📋 ARCHIVOS IMPORTANTES CREADOS

```
api-sync-laravel/
├── README.md                    # Documentación general
├── INSTALACION.md              # Guía de instalación detallada
├── API_DOCUMENTATION.md        # Documentación de endpoints
├── QUICK_START.md              # Esta guía rápida
├── .env.example                # Plantilla de configuración
├── composer.json               # Dependencias
├── 
├── config/
│   ├── database.php           # Conexiones a las 3 BDs
│   └── sync.php               # Configuración de sincronización
├── 
├── app/
│   ├── Models/
│   │   ├── SyncModel.php      # Modelo base
│   │   ├── Paciente.php       # Modelo paciente
│   │   ├── Cita.php           # Modelo cita
│   │   ├── Hc.php             # Modelo historia clínica
│   │   ├── Factura.php        # Modelo factura
│   │   ├── SyncLog.php        # Control de cambios
│   │   └── SyncControl.php    # Control de IDs
│   │
│   ├── Services/
│   │   ├── DatabaseSelector.php  # Cambia BD dinámicamente
│   │   └── SyncService.php        # Lógica de sincronización
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php # Login y tokens
│   │   │   └── SyncController.php # Endpoints sync
│   │   │
│   │   └── Middleware/
│   │       └── ValidarSede.php    # Validación de tokens
│   │
│   └── routes/
│       └── api.php            # Rutas de la API
```

---

## ⚡ COMANDOS ÚTILES

```bash
# Iniciar servidor
php artisan serve --port=8000

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Limpiar caché
php artisan cache:clear
php artisan config:clear

# Ver rutas disponibles
php artisan route:list

# Regenerar autoload
composer dump-autoload
```

---

## 🎯 PRÓXIMOS PASOS

1. ✅ **Crear triggers** en cada tabla para registrar cambios automáticamente
2. ✅ **Integrar con CodeIgniter** para sincronización desde locales
3. ✅ **Desplegar en hosting** y configurar dominio
4. ✅ **Crear interfaz web** para monitorear sincronización
5. ✅ **Programar tareas** automáticas de sincronización

---

## 🆘 AYUDA

Si algo no funciona:

1. Verifica que XAMPP/MySQL esté corriendo
2. Revisa que las bases de datos existan
3. Confirma credenciales en .env
4. Revisa logs: `storage/logs/laravel.log`
5. Verifica que el puerto 8000 esté libre

---

**¡La API está lista para sincronizar tus historias clínicas! 🚀**
