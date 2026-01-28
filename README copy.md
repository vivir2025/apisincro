# API REST - Sincronización Multi-Sede

API desarrollada en Laravel para sincronizar historias clínicas entre servidores locales y servidor principal.

## 🚀 Instalación Rápida

```bash
# 1. Instalar Laravel
composer create-project laravel/laravel api-sync-laravel

# 2. Copiar archivos de este proyecto

# 3. Configurar .env
cp .env.example .env

# 4. Generar key
php artisan key:generate

# 5. Ejecutar migraciones
php artisan migrate

# 6. Iniciar servidor
php artisan serve --port=8000
```

## 📋 Endpoints Disponibles

### Autenticación
- `POST /api/auth/login` - Generar token de acceso por sede

### Sincronización
- `POST /api/sync/check-updates` - Verificar actualizaciones disponibles
- `POST /api/sync/upload` - Subir cambios locales al servidor
- `POST /api/sync/download` - Descargar cambios del servidor
- `GET /api/sync/status/{sede}` - Estado de sincronización

## 🔐 Autenticación

Todas las peticiones requieren:
```
Authorization: Bearer {token}
X-Sede: morales|cajibio|piendamo
```

## 📊 Tablas Sincronizadas

- paciente
- agenda
- cita
- hc (historias clínicas)
- hc_complementaria
- historia_medicamento
- historia_cups
- historia_diagnostico
- historia_remision
- factura
