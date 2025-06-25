# Endpoints y Funcionalidades Faltantes

## ❌ ENDPOINTS Y FUNCIONALIDADES FALTANTES

### 1. ENDPOINTS PARA EXPERIENCIA DE BÚSQUEDA OPTIMIZADA

#### [COMPLETADO ✅] 1.1 Autocompletado de Búsqueda
```
GET /api/v1/public/search/autocomplete?query={term}
```
- **Propósito**: Sugerencias en tiempo real para el buscador principal
- **Respuesta**: Lista de destinos, regiones, categorías que coincidan
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 1.2 Filtros Agregados
```
GET /api/v1/public/filters
```
- **Propósito**: Obtener todos los filtros disponibles en una sola llamada
- **Respuesta**: Categorías, características, regiones, tags, rangos de precios
- **Prioridad**: ALTA

#### 1.3 Búsqueda Avanzada ✅ COMPLETADO
```
GET /api/v1/public/search/advanced
```
- **Propósito**: Búsqueda con múltiples criterios combinados
- **Parámetros**: Texto, categorías[], características[], precio_min, precio_max, rating_min, distancia_max
- **Prioridad**: MEDIA

### 2. ENDPOINTS PARA PÁGINA PRINCIPAL Y NAVEGACIÓN

#### [COMPLETADO ✅] 2.1 Configuración de Portada
```
GET /api/v1/public/home/config
```
- **Propósito**: Configuración editable de la portada (imagen de fondo, textos, secciones destacadas)
- **Respuesta**: Imagen de fondo, título principal, subtítulo, secciones destacadas
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 2.2 Destinos por Sección Visual
```
GET /api/v1/public/sections/{section_slug}
```
- **Propósito**: Destinos agrupados por secciones visuales (Pueblos Mágicos, Aventura, Cultura, etc.)
- **Parámetros**: limit, offset, sort_by
- **Prioridad**: MEDIA

#### [COMPLETADO ✅] 2.3 Destinos Cercanos
```
GET /api/v1/public/destinos/nearby?lat={lat}&lng={lng}&radius={km}
```
- **Propósito**: Destinos cercanos a una ubicación específica
- **Respuesta**: Lista ordenada por distancia con información de proximidad
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 2.4 Destinos Similares
```
GET /api/v1/public/destinos/{slug}/similar
```
- **Propósito**: Destinos similares basados en categoría, región, características
- **Respuesta**: Lista de destinos similares con score de similitud
- **Prioridad**: MEDIA

### 3. ENDPOINTS PARA EXPERIENCIA DE DESTINO

#### [COMPLETADO ✅] 3.1 Galería de Imágenes Avanzada
```
GET /api/v1/public/destinos/{slug}/gallery
POST /api/v1/destinos/{id}/gallery/reorder
POST /api/v1/destinos/{id}/gallery/set-main
DELETE /api/v1/destinos/{id}/gallery/{image_id}
```
- **Propósito**: Gestión granular de galerías de imágenes
- **Funcionalidades**: Reordenar, cambiar imagen principal, eliminar
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 3.2 Estadísticas de Destino
```
GET /api/v1/public/destinos/{slug}/stats
```
- **Propósito**: Estadísticas detalladas del destino
- **Respuesta**: Conteo de favoritos, visitas, reseñas, rating promedio, distribución de calificaciones
- **Prioridad**: MEDIA

#### 3.3 Reseñas Avanzadas ✅ COMPLETADO
- [x] `GET /api/v1/public/destinos/{slug}/reviews/summary` - Resumen estadístico de reseñas
- [x] `POST /api/v1/public/reviews/{id}/report` - Reportar reseña inapropiada
- [x] `POST /api/v1/public/reviews/{id}/reply` - Responder a reseña (dueño)

### 4. ENDPOINTS PARA PROVEEDORES

#### [COMPLETADO ✅] 4.1 Dashboard de Proveedor
```
GET /api/v1/provider/dashboard
```
- **Propósito**: Estadísticas y métricas del proveedor
- **Respuesta**: Destinos activos, promociones, reseñas, visitas, ingresos
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 4.2 Gestión de Contenido desde Frontend
```
POST /api/v1/provider/destinos
PUT /api/v1/provider/destinos/{id}
DELETE /api/v1/provider/destinos/{id}
POST /api/v1/provider/promociones
PUT /api/v1/provider/promociones/{id}
```
- **Propósito**: CRUD de destinos y promociones desde el frontend
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 4.3 Estadísticas Detalladas
```
GET /api/v1/provider/destinos/{id}/analytics
GET /api/v1/provider/promociones/{id}/analytics
```
- **Propósito**: Analytics detallados por destino/promoción
- **Prioridad**: MEDIA

### 5. ENDPOINTS PARA MONETIZACIÓN

#### [COMPLETADO ✅] 5.1 Planes de Suscripción
```
GET /api/v1/public/subscription/plans
POST /api/v1/subscription/create-checkout-session
POST /api/v1/subscription/webhook
```
- **Propósito**: Integración real con Stripe/PayPal
- **Prioridad**: ALTA

#### [COMPLETADO ✅] 5.2 Facturación
```
GET /api/v1/subscription/invoices
GET /api/v1/subscription/invoices/{id}
POST /api/v1/subscription/update-payment-method
```
- **Propósito**: Gestión de facturas y métodos de pago
- **Prioridad**: MEDIA

### 6. ENDPOINTS PARA EVENTOS Y ACTIVIDADES

#### 6.1 Eventos Turísticos ✅ COMPLETADO
```
GET /api/v1/public/eventos
GET /api/v1/public/eventos/{slug}
POST /api/v1/provider/eventos
```
- **Propósito**: Eventos especiales, festivales, actividades temporales
- **Prioridad**: BAJA

#### 6.2 Actividades por Destino ✅ COMPLETADO
```
GET /api/v1/public/destinos/{slug}/actividades
POST /api/v1/provider/destinos/{id}/actividades
```
- **Propósito**: Actividades específicas disponibles en cada destino
- **Prioridad**: BAJA 