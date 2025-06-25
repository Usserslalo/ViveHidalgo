# Controladores y Rutas

## 🛠️ TAREAS DE IMPLEMENTACIÓN TÉCNICA (Controladores y Rutas)

### 3. CONTROLADORES NUEVOS

#### 3.1 SearchController (ampliado)
```php
// app/Http/Controllers/Api/Public/SearchController.php
- autocomplete()
- advanced()
- filters()
```

#### 3.2 HomeConfigController
```php
// app/Http/Controllers/Api/Public/HomeConfigController.php
- show()
- update() // solo admin
```

#### 3.3 SectionController
```php
// app/Http/Controllers/Api/Public/SectionController.php
- show()
- index()
```

#### 3.4 EventoController
```php
// app/Http/Controllers/Api/Public/EventoController.php
- index()
- show()
```

#### 3.5 ProviderController
```php
// app/Http/Controllers/Api/ProviderController.php
- dashboard()
- analytics()
```

#### 3.6 GalleryController
```php
// app/Http/Controllers/Api/GalleryController.php
- reorder()
- setMain()
- destroy()
```

### 4. RUTAS NUEVAS

#### 4.1 Rutas Públicas
```php
// routes/api.php
Route::prefix('v1/public')->group(function () {
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete']);
    Route::get('search/advanced', [SearchController::class, 'advanced']);
    Route::get('filters', [SearchController::class, 'filters']);
    Route::get('home/config', [HomeConfigController::class, 'show']);
    Route::get('sections/{slug}', [SectionController::class, 'show']);
    Route::get('destinos/nearby', [DestinoController::class, 'nearby']);
    Route::get('destinos/{slug}/similar', [DestinoController::class, 'similar']);
    Route::get('destinos/{slug}/gallery', [DestinoController::class, 'gallery']);
    Route::get('destinos/{slug}/stats', [DestinoController::class, 'stats']);
    Route::get('destinos/{slug}/reviews/summary', [ReviewController::class, 'summary']);
    Route::get('eventos', [EventoController::class, 'index']);
    Route::get('eventos/{slug}', [EventoController::class, 'show']);
});
```

#### 4.2 Rutas Protegidas
```php
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Proveedor
    Route::prefix('provider')->middleware('role:provider')->group(function () {
        Route::get('dashboard', [ProviderController::class, 'dashboard']);
        Route::get('destinos/{id}/analytics', [ProviderController::class, 'destinoAnalytics']);
        Route::get('promociones/{id}/analytics', [ProviderController::class, 'promocionAnalytics']);
    });
    
    // Galería
    Route::post('destinos/{id}/gallery/reorder', [GalleryController::class, 'reorder']);
    Route::post('destinos/{id}/gallery/set-main', [GalleryController::class, 'setMain']);
    Route::delete('destinos/{id}/gallery/{image_id}', [GalleryController::class, 'destroy']);
    
    // Reseñas
    Route::post('reviews/{id}/report', [ReviewController::class, 'report']);
    Route::post('reviews/{id}/reply', [ReviewController::class, 'reply']);
}); 