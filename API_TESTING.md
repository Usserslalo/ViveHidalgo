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

# API Testing Guide

## Nuevos Endpoints Optimizados para Frontend

### 🏠 Endpoints de Home

#### 1. Hero Section
```bash
GET /api/v1/public/home/hero
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "hero": {
      "background_image": "https://...",
      "title": "Descubre Hidalgo",
      "subtitle": "Tierra de aventura y tradición",
      "search_placeholder": "Busca destinos, actividades..."
    },
    "featured_destinations": [
      {
        "id": 1,
        "name": "Pueblo Mágico Real del Monte",
        "slug": "real-del-monte",
        "imagen_principal": "https://...",
        "rating": 4.5,
        "reviews_count": 127,
        "favorite_count": 89,
        "price_range": "moderado",
        "caracteristicas": ["Pueblo Mágico", "Gastronomía", "Historia"],
        "region": "Comarca Minera",
        "distance_km": 15.2
      }
    ]
  },
  "message": "Datos del hero recuperados exitosamente."
}
```

#### 2. Secciones Destacadas
```bash
GET /api/v1/public/home/sections
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": [
    {
      "slug": "pueblos-magicos",
      "title": "Pueblos Mágicos",
      "subtitle": "Descubre la magia de nuestros pueblos",
      "image": "https://...",
      "destinations_count": 8,
      "destinations": [
        {
          "id": 1,
          "name": "Real del Monte",
          "slug": "real-del-monte",
          "imagen_principal": "https://...",
          "rating": 4.5,
          "reviews_count": 127,
          "favorite_count": 89,
          "price_range": "moderado",
          "caracteristicas": ["Pueblo Mágico", "Gastronomía", "Historia"],
          "region": "Comarca Minera",
          "distance_km": 15.2
        }
      ]
    }
  ],
  "message": "Secciones recuperadas exitosamente."
}
```

#### 3. Filtros Optimizados
```bash
GET /api/v1/public/home/filters
```

**Respuesta esperada:**
```json
{
  "success": true,
  "data": {
    "filters": {
      "categorias": [
        {
          "id": 1,
          "name": "Pueblo Mágico",
          "count": 8,
          "icon": "🏘️"
        }
      ],
      "caracteristicas": [
        {
          "id": 1,
          "name": "Gastronomía",
          "count": 15,
          "icon": "🍽️"
        }
      ],
      "regiones": [
        {
          "id": 1,
          "name": "Comarca Minera",
          "count": 6
        }
      ],
      "price_ranges": [
        {
          "value": "gratis",
          "label": "Gratis",
          "count": 5
        }
      ]
    }
  },
  "message": "Filtros recuperados exitosamente."
}
```

### 🗺️ Endpoints de Destinos Optimizados

#### 4. Lista de Destinos con Datos Visuales
```bash
GET /api/v1/public/destinos
```

**Respuesta optimizada incluye:**
- `imagen_principal`: URL de imagen optimizada
- `rating`: Calificación promedio
- `reviews_count`: Número de reseñas
- `favorite_count`: Número de favoritos
- `price_range`: Rango de precios
- `caracteristicas`: Array de características principales
- `region`: Nombre de la región
- `distance_km`: Distancia calculada (si se proporcionan coordenadas)

#### 5. Detalle de Destino con Galería Optimizada
```bash
GET /api/v1/public/destinos/{slug}
```

**Galería optimizada incluye:**
```json
{
  "gallery": [
    {
      "id": 1,
      "url": "https://...",
      "thumbnail": "https://...",
      "alt": "Vista panorámica",
      "is_main": true,
      "order": 1,
      "sizes": {
        "original": "https://...",
        "large": "https://...",
        "medium": "https://...",
        "thumbnail": "https://..."
      }
    }
  ]
}
```

### 🧪 Comandos de Prueba

#### Ejecutar Seeders para Datos de Prueba
```bash
php artisan db:seed --class=HomeConfigSeeder
```

#### Limpiar Cache
```bash
php artisan cache:clear
```

#### Verificar Rutas
```bash
php artisan route:list --path=api/v1/public/home
```

### 📊 Verificación de Endpoints

1. **Hero Section**: Verificar que devuelva configuración del home y destinos destacados
2. **Secciones**: Verificar que devuelva secciones con destinos filtrados
3. **Filtros**: Verificar que devuelva conteos y emojis
4. **Destinos**: Verificar que incluya todos los campos visuales
5. **Galería**: Verificar que incluya múltiples tamaños de imagen

### 🔧 Configuración

Los endpoints utilizan:
- **Cache**: 5-10 minutos para optimizar rendimiento
- **Fallbacks**: Imágenes placeholder cuando no hay datos reales
- **Optimización**: Solo campos necesarios para frontend
- **Estructura**: Respuestas consistentes con el patrón del proyecto

### 📝 Notas de Implementación

- Todos los endpoints siguen el patrón JSON descrito en `06_UX-Frontend.md`
- Las imágenes usan placeholders para pruebas
- Los emojis están mapeados por categoría/característica
- Los conteos se calculan dinámicamente
- El cache mejora significativamente el rendimiento 