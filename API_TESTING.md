# Testing de la API - Vive Hidalgo

## 🚀 Endpoints de Autenticación

### Base URL
```
http://localhost:8000/api/v1
```

## 📝 Registro de Usuario

### POST `/auth/register`

**Body:**
```json
{
    "name": "Juan Pérez",
    "email": "juan@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "7711234567",
    "city": "Pachuca",
    "state": "Hidalgo"
}
```

**Response exitoso:**
```json
{
    "success": true,
    "message": "Usuario registrado exitosamente",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com",
            "roles": [
                {
                    "name": "tourist"
                }
            ]
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

## 🔐 Login

### POST `/auth/login`

**Body:**
```json
{
    "email": "juan@example.com",
    "password": "password123"
}
```

**Response exitoso:**
```json
{
    "success": true,
    "message": "Inicio de sesión exitoso",
    "data": {
        "user": {
            "id": 1,
            "name": "Juan Pérez",
            "email": "juan@example.com",
            "roles": [
                {
                    "name": "tourist"
                }
            ]
        },
        "token": "2|def456...",
        "token_type": "Bearer"
    }
}
```

## 👤 Obtener Perfil

### GET `/user/profile`

**Headers:**
```
Authorization: Bearer 2|def456...
```

**Response:**
```json
{
    "success": true,
    "message": "Perfil obtenido exitosamente",
    "data": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "juan@example.com",
        "phone": "7711234567",
        "city": "Pachuca",
        "state": "Hidalgo",
        "roles": [
            {
                "name": "tourist"
            }
        ]
    }
}
```

## 🔄 Actualizar Perfil

### PUT `/user/profile`

**Headers:**
```
Authorization: Bearer 2|def456...
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Juan Carlos Pérez",
    "phone": "7719876543",
    "city": "Tula"
}
```

## 🔑 Cambiar Contraseña

### POST `/user/change-password`

**Headers:**
```
Authorization: Bearer 2|def456...
Content-Type: application/json
```

**Body:**
```json
{
    "current_password": "password123",
    "new_password": "newpassword123",
    "new_password_confirmation": "newpassword123"
}
```

## 📊 Estadísticas del Usuario

### GET `/user/stats`

**Headers:**
```
Authorization: Bearer 2|def456...
```

**Response:**
```json
{
    "success": true,
    "message": "Estadísticas obtenidas exitosamente",
    "data": {
        "favoritos_count": 0,
        "destinos_visitados": 0,
        "promociones_vistas": 0,
        "member_since": "2025-06-21",
        "last_login": "2025-06-21 16:30:00"
    }
}
```

## 🚪 Logout

### POST `/auth/logout`

**Headers:**
```
Authorization: Bearer 2|def456...
```

**Response:**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente",
    "data": null
}
```

## 🧪 Comandos para Probar

### Con cURL:

```bash
# 1. Registrar usuario
curl -X POST http://localhost:8000/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# 2. Login
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'

# 3. Obtener perfil (reemplaza TOKEN con el token recibido)
curl -X GET http://localhost:8000/api/v1/user/profile \
  -H "Authorization: Bearer TOKEN"
```

### Con Postman:

1. **Registro**: POST `http://localhost:8000/api/v1/auth/register`
2. **Login**: POST `http://localhost:8000/api/v1/auth/login`
3. **Perfil**: GET `http://localhost:8000/api/v1/user/profile` (con Authorization header)

## ⚠️ Notas Importantes

- **Tokens**: Los tokens se generan automáticamente al hacer login/registro
- **Headers**: Siempre incluir `Authorization: Bearer TOKEN` para rutas protegidas
- **Content-Type**: Usar `application/json` para requests con body
- **Validación**: Todos los endpoints incluyen validación robusta
- **Roles**: Los usuarios nuevos se asignan automáticamente al rol "tourist"

## 🔧 Próximos Pasos

1. ✅ Sistema de autenticación básico
2. ⏳ CRUDs de destinos turísticos
3. ⏳ Sistema de favoritos
4. ⏳ Panel de proveedores
5. ⏳ Panel de administración 