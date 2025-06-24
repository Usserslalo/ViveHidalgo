# 🏔️ Vive Hidalgo - API de Turismo

Una API moderna y robusta para la plataforma de turismo Vive Hidalgo, construida con Laravel 11 y las mejores prácticas de desarrollo.

## 📋 Tabla de Contenidos

- [Características](#-características)
- [Tecnologías](#-tecnologías)
- [Requisitos](#-requisitos)
- [Instalación](#-instalación)
- [Configuración](#-configuración)
- [Uso](#-uso)
- [API Documentation](#-api-documentation)
- [Testing](#-testing)
- [Deployment](#-deployment)
- [Estructura del Proyecto](#-estructura-del-proyecto)
- [Contribución](#-contribución)
- [Licencia](#-licencia)

## ✨ Características

### 🎯 Funcionalidades Core
- **Sistema de Características Dinámicas** - Gestión flexible de atributos de destinos
- **Geolocalización Avanzada** - Búsqueda por proximidad y filtros espaciales
- **Sistema de Favoritos** - Gestión personalizada de destinos favoritos
- **Reseñas y Calificaciones** - Sistema de reviews con moderación
- **Promociones Temporales** - Gestión de ofertas y descuentos
- **Búsqueda Avanzada** - Motor de búsqueda con Laravel Scout
- **Destinos Destacados** - Sistema TOP para destacar lugares especiales

### 🚀 Funcionalidades Avanzadas
- **Tareas Programadas** - Automatización de procesos críticos
- **Notificaciones Desacopladas** - Sistema de notificaciones con colas
- **Gestión de Perfiles Avanzada** - Perfiles diferenciados por rol
- **Suscripciones y Monetización** - Sistema de planes y pagos
- **Auditoría y Analíticas** - Logging completo y métricas
- **Optimización de Performance** - Cache, rate limiting y seguridad

### 🔧 Características Técnicas
- **API RESTful** - Documentada con Swagger/OpenAPI
- **Autenticación JWT** - Con Laravel Sanctum
- **Control de Acceso** - Roles y permisos con Spatie
- **Panel de Administración** - Filament Admin Panel
- **Testing Exhaustivo** - Cobertura completa de tests
- **Optimización de Base de Datos** - Índices y consultas optimizadas

## 🛠️ Tecnologías

- **Backend**: Laravel 11, PHP 8.2+
- **Base de Datos**: MySQL 8.0+ / PostgreSQL 13+
- **Cache**: Redis / Memcached
- **Search**: Laravel Scout (Meilisearch)
- **Admin Panel**: Filament 3
- **Testing**: PHPUnit, Pest
- **Documentation**: Swagger/OpenAPI
- **Authentication**: Laravel Sanctum
- **Permissions**: Spatie Laravel Permission
- **Maps**: Leaflet + OpenStreetMap

## 📋 Requisitos

- PHP 8.2 o superior
- Composer 2.0+
- MySQL 8.0+ o PostgreSQL 13+
- Redis (opcional, para cache)
- Node.js 18+ (para compilación de assets)

## 🚀 Instalación

### 1. Clonar el repositorio
```bash
git clone https://github.com/tu-usuario/vive-hidalgo-backend.git
cd vive-hidalgo-backend
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar base de datos
Editar `.env` con tus credenciales de base de datos:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vive_hidalgo
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password
```

### 5. Ejecutar migraciones y seeders
```bash
php artisan migrate
php artisan db:seed
```

### 6. Configurar storage
```bash
php artisan storage:link
```

### 7. Compilar assets (opcional)
```bash
npm run build
```

## ⚙️ Configuración

### Configuración de Cache
```bash
# Configurar cache de Redis
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Configuración de Colas
```bash
# Procesar colas en background
php artisan queue:work
```

### Configuración de Tareas Programadas
```bash
# Agregar al crontab
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Configuración de Búsqueda
```bash
# Indexar modelos para búsqueda
php artisan scout:import "App\Models\Destino"
php artisan scout:import "App\Models\Region"
```

## 📖 Uso

### Comandos Artisan Disponibles

```bash
# Optimización de base de datos
php artisan app:optimize-database

# Limpiar logs de auditoría
php artisan app:clean-audit-logs

# Expirar promociones
php artisan app:expire-promotions

# Expirar suscripciones
php artisan app:expire-subscriptions

# Generar documentación de API
php artisan l5-swagger:generate
```

### Estructura de Roles

- **admin**: Acceso completo al sistema
- **provider**: Gestión de destinos y promociones
- **user**: Usuario regular con acceso básico

## 📚 API Documentation

La documentación completa de la API está disponible en:
- **Swagger UI**: `/api/documentation`
- **JSON Schema**: `/storage/api-docs/api-docs.json`

### Endpoints Principales

#### Autenticación
- `POST /api/v1/auth/login` - Iniciar sesión
- `POST /api/v1/auth/register` - Registrarse
- `POST /api/v1/auth/logout` - Cerrar sesión

#### Destinos
- `GET /api/v1/public/destinos` - Listar destinos públicos
- `GET /api/v1/public/destinos/{id}` - Ver destino específico
- `GET /api/v1/public/destinos/top` - Destinos destacados

#### Búsqueda
- `GET /api/v1/search` - Búsqueda global

#### Perfil
- `GET /api/v1/profile` - Ver perfil
- `PUT /api/v1/profile` - Actualizar perfil

## 🧪 Testing

### Ejecutar Tests
```bash
# Todos los tests
php artisan test

# Tests específicos
php artisan test tests/Feature/Api/DestinoTest.php

# Con cobertura
php artisan test --coverage
```

### Tipos de Tests
- **Unit Tests**: Lógica de negocio
- **Feature Tests**: Endpoints de API
- **Integration Tests**: Flujos completos

## 🚀 Deployment

### Producción
```bash
# Optimizar para producción
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Configurar supervisor para colas
# Configurar cron para tareas programadas
```

### Docker (opcional)
```bash
docker-compose up -d
```

## 📁 Estructura del Proyecto

```
app/
├── Console/Commands/          # Comandos Artisan
├── DTOs/                     # Data Transfer Objects
├── Filament/                 # Panel de administración
├── Http/
│   ├── Controllers/Api/      # Controladores de API
│   ├── Middleware/           # Middlewares personalizados
│   └── Requests/             # Form Requests
├── Models/                   # Modelos Eloquent
├── Notifications/            # Notificaciones
├── Observers/                # Observers de modelos
├── Policies/                 # Políticas de autorización
├── Providers/                # Service Providers
├── Services/                 # Servicios de negocio
└── Traits/                   # Traits reutilizables

database/
├── factories/                # Factories para testing
├── migrations/               # Migraciones de BD
└── seeders/                  # Seeders de datos

routes/
├── api.php                   # Rutas de API
├── auth.php                  # Rutas de autenticación
└── web.php                   # Rutas web

tests/
├── Feature/                  # Tests de características
└── Unit/                     # Tests unitarios
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

### Estándares de Código
- PSR-12 coding standards
- Tests obligatorios para nuevas funcionalidades
- Documentación de API actualizada

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 📞 Soporte

- **Email**: soporte@vivehidalgo.com
- **Documentación**: [docs.vivehidalgo.com](https://docs.vivehidalgo.com)
- **Issues**: [GitHub Issues](https://github.com/tu-usuario/vive-hidalgo-backend/issues)

## 🙏 Agradecimientos

- Laravel Team por el framework
- Spatie por los paquetes de permisos
- Filament Team por el panel de administración
- Comunidad de Laravel por el soporte

---

**Desarrollado con ❤️ para Vive Hidalgo**

# Vive Hidalgo Backend

## Descripción General

Este backend es una plataforma robusta y extensible para turismo, desarrollada en Laravel, que ofrece gestión avanzada de destinos, características dinámicas, geolocalización, favoritos, reseñas, promociones, búsqueda avanzada, destinos destacados, tareas programadas, notificaciones, gestión avanzada de perfiles, suscripciones y auditoría. Incluye panel de administración (Filament), API documentada (Swagger/OpenAPI), pruebas automatizadas y documentación profesional.

---

## Funcionalidades Principales

- **Gestión de Destinos Turísticos**: CRUD completo, categorías, características dinámicas, ubicación geográfica, estadísticas de reseñas, favoritos, y destinos destacados.
- **Características Dinámicas**: Asocia características personalizadas a cada destino.
- **Geolocalización**: Soporte para ubicación y referencia geográfica de destinos.
- **Favoritos**: Usuarios pueden marcar destinos como favoritos.
- **Reseñas**: Sistema de reseñas con aprobación/rechazo, notificaciones y estadísticas.
- **Promociones**: Gestión de promociones con expiración automática y notificaciones.
- **Búsqueda Avanzada**: Filtros por ubicación, categoría, características, texto, etc.
- **Destinos Destacados (Top)**: Marcar y administrar destinos destacados.
- **Tareas Programadas (CRON)**: Expiración automática de promociones y suscripciones, limpieza de logs de auditoría, optimización de base de datos.
- **Notificaciones**: Asíncronas, desacopladas, para eventos clave (reseñas, promociones, etc.).
- **Gestión Avanzada de Perfil**: Edición de perfil, cambio de contraseña, eliminación de cuenta, soporte para proveedores externos.
- **Suscripciones y Monetización**: Gestión de suscripciones, expiración automática, integración con usuarios.
- **Auditoría y Analítica**: Registro de acciones clave, consulta y limpieza de logs.
- **Panel de Administración (Filament)**: CRUD y gestión avanzada de todos los recursos desde una interfaz amigable.
- **Documentación API (Swagger/OpenAPI)**: Documentación interactiva y actualizada de todos los endpoints.
- **Pruebas Automatizadas**: Cobertura completa de pruebas para controladores, comandos, modelos y lógica de negocio.
- **Seguridad**: Middleware de rate limiting, cabeceras de seguridad, validación exhaustiva, autenticación y autorización.

---

## Módulos y Estructura

- **API RESTful**: Controladores en `app/Http/Controllers/Api/` para cada recurso.
- **Modelos Eloquent**: Relaciones, scopes y lógica de negocio en `app/Models/`.
- **Recursos Filament**: Administración visual en `app/Filament/Resources/`.
- **Comandos Artisan**: Tareas programadas y utilidades en `app/Console/Commands/`.
- **Notificaciones**: En `app/Notifications/` y colas asíncronas.
- **Servicios y Observers**: Lógica desacoplada y eventos.
- **Middlewares**: Seguridad y optimización de peticiones.
- **Pruebas**: En `tests/Feature/` y `tests/Unit/`.
- **Migrations, Seeders y Factories**: En `database/` para gestión de base de datos y datos de prueba.

---

## Endpoints y Documentación

- **Todos los endpoints están documentados en Swagger/OpenAPI**.
- Acceso: `/api/documentation` o según configuración de l5-swagger.
- Incluye ejemplos, parámetros, respuestas y esquemas de datos.

---

## Panel de Administración (Filament)

- Acceso a CRUD de todos los recursos: destinos, categorías, características, usuarios, reseñas, promociones, suscripciones, auditoría, etc.
- Validaciones, filtros, búsquedas y acciones masivas.
- Integración directa con la lógica de negocio y políticas de autorización.

---

## Tareas Programadas y Utilidades

- **Expiración de promociones**: `php artisan app:expire-promotions`
- **Expiración de suscripciones**: `php artisan app:expire-subscriptions`
- **Limpieza de logs de auditoría**: `php artisan app:clean-audit-logs`
- **Optimización de base de datos**: `php artisan app:optimize-database`
- **Configuración de CRON**: Ver `CRON_SETUP.md` y `scripts/setup-cron.sh`

---

## Pruebas y Calidad

- Pruebas automatizadas para todos los módulos clave.
- Ejecutar: `php artisan test` o `vendor/bin/phpunit`
- Factories y seeders para datos de prueba realistas.

---

## Seguridad y Buenas Prácticas

- Middleware de rate limiting y cabeceras de seguridad.
- Validación exhaustiva de datos y políticas de acceso.
- Autenticación (sanctum) y autorización por roles/políticas.
- Logs de auditoría para trazabilidad.

---

## Extensibilidad y Posibilidades

- **Puedes construir encima**:
  - Aplicaciones móviles (Android/iOS) usando la API.
  - Frontend web (React, Vue, etc.) consumiendo la API.
  - Paneles de analítica y dashboards personalizados.
  - Integración con pasarelas de pago, notificaciones push, etc.
  - Nuevos módulos (eventos, reservas, etc.) fácilmente integrables.
- **No incluido por defecto**:
  - Frontend visual para usuarios finales (solo API y admin).
  - Procesamiento de pagos (puede integrarse).
  - Notificaciones push (puede integrarse).
  - Infraestructura de despliegue (ver `DEPLOYMENT.md`).

---

## Despliegue y Documentación

- Guía de despliegue en `DEPLOYMENT.md`.
- Configuración de CRON en `CRON_SETUP.md`.
- Documentación de endpoints en Swagger.
- Plan de desarrollo y fases en `PLAN_DESARROLLO_ACTUALIZADO.md`.

---

## Estado del Proyecto

- **100% de las fases completadas**.
- Listo para producción, pruebas y extensiones.
- Documentación y pruebas al día.

---

## Contacto y Soporte

Para dudas, soporte o contribuciones, contactar al equipo de desarrollo o abrir un issue en el repositorio correspondiente.
