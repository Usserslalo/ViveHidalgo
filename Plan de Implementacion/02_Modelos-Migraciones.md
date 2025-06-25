# Modelos y Migraciones

## 🛠️ TAREAS DE IMPLEMENTACIÓN TÉCNICA

### 1. MODELOS NUEVOS

#### 1.1 Modelo HomeConfig
```php
// app/Models/HomeConfig.php
- hero_image_path
- hero_title
- hero_subtitle
- featured_sections (JSON)
- is_active
```

#### 1.2 Modelo Evento
```php
// app/Models/Evento.php
- name, slug, description
- start_date, end_date
- location, coordinates
- image_path
- is_active
- user_id (organizador)
```

#### 1.3 Modelo Actividad
```php
// app/Models/Actividad.php
- name, description
- duration, price
- difficulty_level
- destino_id
- is_active
```

#### 1.4 Modelo ReviewReport
```php
// app/Models/ReviewReport.php
- review_id
- reporter_id
- reason
- status (pending, resolved, dismissed)
```

### 2. MIGRACIONES NECESARIAS

#### 2.1 Tabla home_configs
```sql
CREATE TABLE home_configs (
    id bigint unsigned PRIMARY KEY,
    hero_image_path varchar(255),
    hero_title varchar(255),
    hero_subtitle text,
    featured_sections json,
    is_active boolean DEFAULT true,
    created_at timestamp,
    updated_at timestamp
);
```

#### 2.2 Tabla eventos
```sql
CREATE TABLE eventos (
    id bigint unsigned PRIMARY KEY,
    name varchar(255),
    slug varchar(255) UNIQUE,
    description text,
    start_date datetime,
    end_date datetime,
    location varchar(255),
    latitude decimal(10,8),
    longitude decimal(11,8),
    image_path varchar(255),
    is_active boolean DEFAULT true,
    user_id bigint unsigned,
    created_at timestamp,
    updated_at timestamp
);
```

#### 2.3 Tabla actividades
```sql
CREATE TABLE actividades (
    id bigint unsigned PRIMARY KEY,
    name varchar(255),
    description text,
    duration integer, -- en minutos
    price decimal(10,2),
    difficulty_level enum('facil','moderado','dificil'),
    destino_id bigint unsigned,
    is_active boolean DEFAULT true,
    created_at timestamp,
    updated_at timestamp
);
```

#### 2.4 Tabla review_reports
```sql
CREATE TABLE review_reports (
    id bigint unsigned PRIMARY KEY,
    review_id bigint unsigned,
    reporter_id bigint unsigned,
    reason text,
    status enum('pending','resolved','dismissed') DEFAULT 'pending',
    created_at timestamp,
    updated_at timestamp
);
```

#### 2.5 Campos adicionales en destinos
```sql
ALTER TABLE destinos ADD COLUMN price_range enum('gratis','economico','moderado','premium');
ALTER TABLE destinos ADD COLUMN visit_count integer DEFAULT 0;
ALTER TABLE destinos ADD COLUMN favorite_count integer DEFAULT 0;
``` 