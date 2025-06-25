# Políticas

## 🛠️ TAREAS DE IMPLEMENTACIÓN TÉCNICA (Políticas)

### 5. POLÍTICAS NUEVAS

#### 5.1 HomeConfigPolicy
```php
// app/Policies/HomeConfigPolicy.php
- update() // solo admin
```

#### 5.2 EventoPolicy
```php
// app/Policies/EventoPolicy.php
- create() // proveedor o admin
- update() // dueño o admin
- delete() // dueño o admin
```

#### 5.3 GalleryPolicy
```php
// app/Policies/GalleryPolicy.php
- manage() // dueño del destino o admin
``` 