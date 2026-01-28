# 📚 DOCUMENTACIÓN DE ENDPOINTS - API REST

Base URL: `http://localhost:8000/api`

---

## 🔐 Autenticación

### POST /auth/login

Autentica un usuario y genera un token de acceso.

**Request:**
```json
{
  "sede": "morales",
  "usuario": "admin",
  "password": "123456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Autenticación exitosa",
  "token": "eyJ1c2VyX2lkIjoxLCJzZWRlIjoibW9yYWxlcyIsImlhdCI6MTY...",
  "expires_in": 86400,
  "sede": "morales",
  "usuario": {
    "id": 1,
    "nombre": "Administrador",
    "usuario": "admin"
  }
}
```

**Errores:**
- `401`: Credenciales incorrectas
- `400`: Sede no válida
- `422`: Validación fallida

---

## 🔄 Sincronización

**Headers requeridos en todos los endpoints:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

### POST /sync/check-updates

Verifica si hay actualizaciones disponibles en el servidor.

**Request:**
```json
{
  "sede": "morales",
  "ultimos_ids": {
    "paciente": 1520,
    "agenda": 450,
    "cita": 2300,
    "hc": 880,
    "hc_complementaria": 200,
    "historia_medicamento": 1500,
    "historia_cups": 800,
    "historia_diagnostico": 900,
    "historia_remision": 150,
    "factura": 600
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "hay_cambios": true,
  "total_registros": 45,
  "tablas_con_cambios": {
    "paciente": 12,
    "cita": 23,
    "hc": 8,
    "factura": 2
  }
}
```

---

### POST /sync/upload

Sube cambios desde el servidor local al servidor principal.

**Request:**
```json
{
  "sede": "morales",
  "cambios": [
    {
      "tabla": "paciente",
      "registro_id": 1523,
      "operacion": "INSERT",
      "datos": {
        "id": 1523,
        "tipo_documento_id": 1,
        "numero_documento": "123456789",
        "primer_nombre": "Juan",
        "segundo_nombre": "Carlos",
        "primer_apellido": "Pérez",
        "segundo_apellido": "García",
        "fecha_nacimiento": "1990-05-15",
        "sexo": "M",
        "telefono": "3001234567",
        "direccion": "Calle 123 #45-67"
      },
      "sync_log_id": 45
    },
    {
      "tabla": "cita",
      "registro_id": 2305,
      "operacion": "UPDATE",
      "datos": {
        "id": 2305,
        "estado": "atendida",
        "observaciones": "Paciente atendido"
      },
      "sync_log_id": 46
    }
  ]
}
```

**Response (200):**
```json
{
  "success": true,
  "sincronizados": 2,
  "errores": [],
  "total_cambios": 2
}
```

**Response con errores (200):**
```json
{
  "success": true,
  "sincronizados": 1,
  "errores": [
    {
      "tabla": "cita",
      "registro_id": 2305,
      "error": "Registro no encontrado"
    }
  ],
  "total_cambios": 2
}
```

---

### POST /sync/download

Descarga cambios del servidor principal al servidor local.

**Request:**
```json
{
  "sede": "morales",
  "ultimos_ids": {
    "paciente": 1520,
    "agenda": 450,
    "cita": 2300,
    "hc": 880,
    "hc_complementaria": 200,
    "historia_medicamento": 1500,
    "historia_cups": 800,
    "historia_diagnostico": 900,
    "historia_remision": 150,
    "factura": 600
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "nuevos_registros": {
    "paciente": [
      {
        "id": 1521,
        "numero_documento": "987654321",
        "primer_nombre": "María",
        "primer_apellido": "López"
      },
      {
        "id": 1522,
        "numero_documento": "456789123",
        "primer_nombre": "Pedro",
        "primer_apellido": "Martínez"
      }
    ],
    "cita": [
      {
        "id": 2301,
        "paciente_id": 1521,
        "fecha_cita": "2026-01-28",
        "hora_cita": "10:00:00",
        "estado": "programada"
      }
    ],
    "hc": [
      {
        "id": 881,
        "paciente_id": 1520,
        "fecha_consulta": "2026-01-27 09:30:00",
        "motivo_consulta": "Control"
      }
    ]
  },
  "total_tablas": 3
}
```

---

### GET /sync/status/{sede}

Obtiene el estado actual de sincronización de una sede.

**Request:**
```
GET /sync/status/morales
```

**Response (200):**
```json
{
  "success": true,
  "sede": "morales",
  "ultima_sincronizacion": "2026-01-27 14:30:25",
  "pendientes": 12,
  "tablas": {
    "paciente": {
      "ultimo_id": 1522,
      "ultima_sync": "2026-01-27 14:30:25"
    },
    "cita": {
      "ultimo_id": 2305,
      "ultima_sync": "2026-01-27 14:28:10"
    },
    "hc": {
      "ultimo_id": 881,
      "ultima_sync": "2026-01-27 14:25:00"
    }
  }
}
```

---

### POST /sync/test

Endpoint de prueba para verificar conectividad.

**Request:**
```json
{
  "sede": "morales"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Conexión exitosa",
  "sede": "morales",
  "estadisticas": {
    "pacientes": 1522,
    "citas": 2305
  }
}
```

---

## 🚨 Códigos de Error

| Código | Descripción |
|--------|-------------|
| 200 | OK - Operación exitosa |
| 400 | Bad Request - Parámetros inválidos |
| 401 | Unauthorized - Token inválido o expirado |
| 403 | Forbidden - Sede no autorizada |
| 422 | Unprocessable Entity - Validación fallida |
| 500 | Internal Server Error - Error del servidor |

---

## 📝 Notas Importantes

### Operaciones Soportadas
- `INSERT`: Crear nuevo registro
- `UPDATE`: Actualizar registro existente
- `DELETE`: Eliminar registro

### Límites
- Máximo 500 registros por request de upload
- Máximo 500 registros por tabla en download
- Token expira en 24 horas

### Tablas Sincronizadas
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

### Sedes Disponibles
- `morales`
- `cajibio`
- `piendamo`

---

## 🔧 Ejemplos de Uso con cURL

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"sede":"morales","usuario":"admin","password":"123456"}'
```

### Check Updates
```bash
curl -X POST http://localhost:8000/api/sync/check-updates \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sede":"morales","ultimos_ids":{"paciente":100}}'
```

### Upload
```bash
curl -X POST http://localhost:8000/api/sync/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"sede":"morales","cambios":[{"tabla":"paciente","operacion":"INSERT","registro_id":101,"datos":{"id":101,"nombre":"Test"}}]}'
```

### Status
```bash
curl -X GET http://localhost:8000/api/sync/status/morales \
  -H "Authorization: Bearer YOUR_TOKEN"
```
