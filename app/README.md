# Estructura del Proyecto Vive Hidalgo - Backend

## Organización de Carpetas

### `/app/Http/Controllers/Api/`
Controladores específicos para la API REST:
- `BaseController.php` - Controlador base con métodos comunes de respuesta
- `Auth/` - Controladores de autenticación
- `Provider/` - Controladores para proveedores
- `Admin/` - Controladores para administradores

### `/app/Services/`
Servicios de la aplicación que contienen la lógica de negocio:
- Servicios de autenticación
- Servicios de gestión de destinos
- Servicios de pagos y suscripciones
- Servicios de estadísticas

### `/app/DTOs/`
Data Transfer Objects para estructurar datos entre capas:
- DTOs de respuesta de API
- DTOs de validación
- DTOs de transferencia de datos

### `/app/Traits/`
Traits para reutilizar funcionalidades comunes:
- Traits para modelos
- Traits para controladores
- Traits para servicios

## Convenciones de Nomenclatura

### Controladores API
- Extienden de `BaseController`
- Usan métodos estándar: `index`, `show`, `store`, `update`, `destroy`
- Respuestas JSON consistentes usando métodos del BaseController

### Servicios
- Nombres descriptivos: `DestinoService`, `AuthService`, `PaymentService`
- Métodos específicos para cada operación
- Manejo de excepciones y validaciones

### DTOs
- Nombres claros: `DestinoResponseDTO`, `UserCreateDTO`
- Estructura de datos bien definida
- Validaciones integradas

## Flujo de Desarrollo

1. **Modelos** - Definir entidades y relaciones
2. **Migraciones** - Estructura de base de datos
3. **DTOs** - Estructura de datos de entrada/salida
4. **Servicios** - Lógica de negocio
5. **Controladores** - Endpoints de la API
6. **Rutas** - Definir endpoints en `routes/api.php`
7. **Validaciones** - Request classes para validación
8. **Tests** - Pruebas unitarias y de integración

## Próximos Pasos

1. Instalar paquetes base (Laravel Breeze, Sanctum, etc.)
2. Configurar autenticación
3. Crear modelos y migraciones
4. Implementar controladores básicos
5. Configurar roles y permisos 