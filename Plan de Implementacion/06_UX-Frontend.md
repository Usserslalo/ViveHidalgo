# UX y Recomendaciones para Frontend

## 🎨 RECOMENDACIONES UX PARA FRONTEND

### 1. OPTIMIZACIÓN DE RESPUESTAS API

#### 1.1 Incluir Siempre Datos Visuales
```json
{
  "destinos": [
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
}
```

#### 1.2 Respuestas para Filtros
```json
{
  "filters": {
    "categorias": [
      {"id": 1, "name": "Pueblo Mágico", "count": 8, "icon": "🏘️"},
      {"id": 2, "name": "Aventura", "count": 12, "icon": "🏔️"}
    ],
    "caracteristicas": [
      {"id": 1, "name": "Gastronomía", "count": 15, "icon": "🍽️"},
      {"id": 2, "name": "Historia", "count": 10, "icon": "🏛️"}
    ],
    "regiones": [
      {"id": 1, "name": "Comarca Minera", "count": 6},
      {"id": 2, "name": "Sierra Gorda", "count": 4}
    ],
    "price_ranges": [
      {"value": "gratis", "label": "Gratis", "count": 5},
      {"value": "economico", "label": "Económico", "count": 12}
    ]
  }
}
```

### 2. ENDPOINTS PARA COMPONENTES VISUALES

#### 2.1 Hero Section
```json
{
  "hero": {
    "background_image": "https://...",
    "title": "Descubre Hidalgo",
    "subtitle": "Tierra de aventura y tradición",
    "search_placeholder": "Busca destinos, actividades...",
    "featured_destinations": [...]
  }
}
```

#### 2.2 Secciones Destacadas
```json
{
  "sections": [
    {
      "slug": "pueblos-magicos",
      "title": "Pueblos Mágicos",
      "subtitle": "Descubre la magia de nuestros pueblos",
      "image": "https://...",
      "destinations_count": 8,
      "destinations": [...]
    }
  ]
}
```

### 3. OPTIMIZACIÓN DE IMÁGENES

#### 3.1 Múltiples Tamaños
```json
{
  "imagen": {
    "original": "https://.../original.jpg",
    "large": "https://.../large.jpg",
    "medium": "https://.../medium.jpg",
    "thumbnail": "https://.../thumb.jpg",
    "alt": "Descripción de la imagen"
  }
}
```

#### 3.2 Galería Optimizada
```json
{
  "gallery": [
    {
      "id": 1,
      "url": "https://...",
      "thumbnail": "https://...",
      "alt": "Vista panorámica",
      "is_main": true,
      "order": 1
    }
  ]
}
``` 