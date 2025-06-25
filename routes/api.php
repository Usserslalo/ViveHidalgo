<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CaracteristicaController;
use App\Http\Controllers\Api\FavoritoController;
use App\Http\Controllers\Api\GaleriaController;
use App\Http\Controllers\Api\PromocionController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ProviderDestinoController;
use App\Http\Controllers\Api\ProviderPromocionController;
use App\Http\Controllers\Api\Public\CaracteristicaController as PublicCaracteristicaController;
use App\Http\Controllers\Api\Public\DestinoController as PublicDestinoController;
use App\Http\Controllers\Api\Public\HomeConfigController;
use App\Http\Controllers\Api\Public\HomeController;
use App\Http\Controllers\Api\Public\ProveedorController as PublicProveedorController;
use App\Http\Controllers\Api\Public\SectionController;
use App\Http\Controllers\Api\Public\SearchController as PublicSearchController;
use App\Http\Controllers\Api\Public\SubscriptionController as PublicSubscriptionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Provider\EventoController as ProviderEventoController;
use App\Http\Controllers\Api\Public\EventoController as PublicEventoController;
use App\Http\Controllers\Api\Provider\ActividadController as ProviderActividadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas de prueba simples
Route::get('/test-simple', function () {
    return response()->json([
        'message' => 'Ruta simple funcionando',
        'timestamp' => now()
    ]);
});

Route::get('/test', function () {
    return response()->json([
        'message' => 'Vive Hidalgo API está funcionando correctamente',
        'version' => '1.0.0',
        'timestamp' => now()
    ]);
});

// Rutas protegidas (requieren autenticación)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Autenticación (rutas que requieren token)
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    });
    
    // Usuario autenticado
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile'])->name('api.user.profile');
        Route::put('profile', [UserController::class, 'updateProfile'])->name('api.user.update-profile');
        Route::post('change-password', [UserController::class, 'changePassword'])->name('api.user.change-password');
        Route::delete('account', [UserController::class, 'deleteAccount'])->name('api.user.delete-account');
        Route::get('stats', [UserController::class, 'stats'])->name('api.user.stats');
        
        // Favoritos
        Route::get('favoritos', [FavoritoController::class, 'getUserFavorites']);
        Route::post('favoritos/{destino_id}', [FavoritoController::class, 'addToFavorites']);
        Route::delete('favoritos/{destino_id}', [FavoritoController::class, 'removeFromFavorites']);
        Route::get('favoritos/check/{destino_id}', [FavoritoController::class, 'checkIfFavorite']);
        
        // Reseñas
        Route::get('reviews', [ReviewController::class, 'getUserReviews']);
        Route::post('reviews/{destino}', [ReviewController::class, 'store']);
        Route::put('reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy']);
        
        // Gestión de galerías de imágenes
        Route::prefix('imagenes/galeria')->group(function () {
            Route::get('{destino_id}', [GaleriaController::class, 'index'])->name('galeria.index');
            Route::post('{destino_id}/reorder', [GaleriaController::class, 'reorder'])->name('galeria.reorder');
            Route::patch('{destino_id}/set-main', [GaleriaController::class, 'setMain'])->name('galeria.set-main');
            Route::delete('{destino_id}/{imagen_id}', [GaleriaController::class, 'destroy'])->name('galeria.destroy');
        });
        
        // Rutas de galería alternativas (según el plan)
        Route::prefix('destinos/{id}/gallery')->group(function () {
            Route::post('reorder', [GaleriaController::class, 'reorder'])->name('destinos.gallery.reorder');
            Route::post('set-main', [GaleriaController::class, 'setMain'])->name('destinos.gallery.set-main');
            Route::delete('{image_id}', [GaleriaController::class, 'destroy'])->name('destinos.gallery.destroy');
        });
    });

    // Rutas del proveedor
    Route::prefix('provider')->group(function () {
        Route::get('dashboard', [ProviderController::class, 'dashboard'])->name('provider.dashboard');
        Route::get('analytics', [ProviderController::class, 'analytics'])->name('provider.analytics');
        
        // Analytics detallados
        Route::get('destinos/{id}/analytics', [ProviderController::class, 'getDestinoAnalytics'])->name('provider.destinos.analytics');
        Route::get('promociones/{id}/analytics', [ProviderController::class, 'getPromocionAnalytics'])->name('provider.promociones.analytics');
        
        // CRUD de destinos
        Route::apiResource('destinos', ProviderDestinoController::class)->names([
            'index' => 'provider.destinos.index',
            'store' => 'provider.destinos.store',
            'show' => 'provider.destinos.show',
            'update' => 'provider.destinos.update',
            'destroy' => 'provider.destinos.destroy',
        ]);
        
        // CRUD de promociones
        Route::apiResource('promociones', ProviderPromocionController::class)->names([
            'index' => 'provider.promociones.index',
            'store' => 'provider.promociones.store',
            'show' => 'provider.promociones.show',
            'update' => 'provider.promociones.update',
            'destroy' => 'provider.promociones.destroy',
        ]);
    });

    // Rutas de suscripción
    Route::prefix('subscription')->group(function () {
        Route::post('create-checkout-session', [SubscriptionController::class, 'createCheckoutSession'])->name('subscription.create-checkout');
        Route::get('plans', [SubscriptionController::class, 'getAvailablePlans'])->name('subscription.plans');
        Route::get('my-subscription', [SubscriptionController::class, 'getMySubscription'])->name('subscription.my-subscription');
        Route::post('subscribe', [SubscriptionController::class, 'subscribe'])->name('subscription.subscribe');
        Route::post('cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
        Route::post('renew', [SubscriptionController::class, 'renew'])->name('subscription.renew');
        Route::get('limits', [SubscriptionController::class, 'getLimits'])->name('subscription.limits');
        
        // Rutas de facturación
        Route::get('invoices', [SubscriptionController::class, 'getInvoices'])->name('subscription.invoices');
        Route::get('invoices/{id}', [SubscriptionController::class, 'getInvoice'])->name('subscription.invoice');
        Route::post('update-payment-method', [SubscriptionController::class, 'updatePaymentMethod'])->name('subscription.update-payment-method');
    });

    // Ruta de Búsqueda Global
    Route::get('/search', SearchController::class)->name('search');

    // Eventos turísticos (proveedores)
    Route::post('provider/eventos', [ProviderEventoController::class, 'store'])->name('provider.eventos.store');
    Route::put('provider/eventos/{evento}', [ProviderEventoController::class, 'update'])->name('provider.eventos.update');
    Route::delete('provider/eventos/{evento}', [ProviderEventoController::class, 'destroy'])->name('provider.eventos.destroy');
    
    // Actividades turísticas (proveedores)
    Route::post('provider/destinos/{id}/actividades', [ProviderActividadController::class, 'store'])->name('provider.actividades.store');

    // Rutas de reseñas protegidas
    Route::prefix('reviews')->group(function () {
        Route::post('{id}/report', [ReviewController::class, 'report'])->name('reviews.report');
        Route::post('{id}/reply', [ReviewController::class, 'reply'])->name('reviews.reply');
    });
});

// --- AUTHENTICATION ---
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');
});

// --- PUBLIC API (No Auth Required) ---
Route::prefix('v1/public')->name('api.public.')->group(function () {
    Route::get('destinos', [PublicDestinoController::class, 'index'])->name('destinos.index');
    Route::get('destinos/top', [PublicDestinoController::class, 'top'])->name('destinos.top');
    Route::get('destinos/nearby', [PublicDestinoController::class, 'nearby'])->name('destinos.nearby');
    Route::get('destinos/{slug}', [PublicDestinoController::class, 'show'])->name('destinos.show');
    Route::get('destinos/{slug}/similar', [PublicDestinoController::class, 'similar'])->name('destinos.similar');
    Route::get('destinos/{slug}/stats', [PublicDestinoController::class, 'getStats'])->name('destinos.stats');
    Route::get('destinos/{slug}/gallery', [PublicDestinoController::class, 'gallery'])->name('destinos.gallery');
    Route::get('destinos/{slug}/reviews/summary', [ReviewController::class, 'summary'])->name('destinos.reviews.summary');
    Route::post('reviews/{id}/report', [PublicDestinoController::class, 'reportReview'])->name('reviews.report');
    Route::get('destinos/{destino}/reviews', [ReviewController::class, 'getDestinoReviews'])->name('destinos.reviews');
    Route::get('destinos/{destino}/promociones', [PromocionController::class, 'forDestino'])->name('destinos.promociones');

    // Rutas públicas para promociones
    Route::get('promociones', [PromocionController::class, 'index'])->name('promociones.index');
    
    // Rutas de búsqueda pública
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
    Route::get('search/advanced', [SearchController::class, 'advancedSearch'])->name('search.advanced');
    Route::get('filters', [SearchController::class, 'getFilters'])->name('filters');
    
    // Configuración de portada
    Route::get('home/config', [HomeConfigController::class, 'show'])->name('home.config');
    
    // Secciones visuales
    Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
    Route::get('sections/{section_slug}', [SectionController::class, 'show'])->name('sections.show');
    
    // Regiones públicas
    Route::get('regiones', [\App\Http\Controllers\Api\Public\RegionController::class, 'index'])->name('regiones.index');
    Route::get('regiones/{slug}', [\App\Http\Controllers\Api\Public\RegionController::class, 'show'])->name('regiones.show');
    
    // Categorías públicas
    Route::get('categorias', [\App\Http\Controllers\Api\Public\CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('categorias/{slug}', [\App\Http\Controllers\Api\Public\CategoriaController::class, 'show'])->name('categorias.show');
    
    // Tags públicos
    Route::get('tags', [\App\Http\Controllers\Api\Public\TagController::class, 'index'])->name('tags.index');
    Route::get('tags/{slug}', [\App\Http\Controllers\Api\Public\TagController::class, 'show'])->name('tags.show');
    
    // Características públicas
    Route::get('caracteristicas', [PublicCaracteristicaController::class, 'index'])->name('caracteristicas.index');
    Route::get('caracteristicas/{slug}', [PublicCaracteristicaController::class, 'show'])->name('caracteristicas.show');
    Route::get('caracteristicas-test', [PublicCaracteristicaController::class, 'test'])->name('caracteristicas.test');
    
    // Proveedores públicos
    Route::get('proveedores', [PublicProveedorController::class, 'index'])->name('proveedores.index');
    Route::get('proveedores/{slug}', [PublicProveedorController::class, 'show'])->name('proveedores.show');
    
    // Planes de suscripción públicos
    Route::get('subscription/plans', [PublicSubscriptionController::class, 'getPlans'])->name('subscription.plans');
    
    Route::get('home', [HomeController::class, 'index']);

    // Eventos turísticos
    Route::get('eventos', [PublicEventoController::class, 'index'])->name('eventos.index');
    Route::get('eventos/{slug}', [PublicEventoController::class, 'show'])->name('eventos.show');
    
    // Actividades por destino
    Route::get('destinos/{slug}/actividades', [PublicDestinoController::class, 'getActividades'])->name('destinos.actividades');
});

// Rutas públicas que no requieren autenticación
Route::prefix('public')->group(function () {
    Route::get('destinos', [PublicDestinoController::class, 'index']);
    Route::get('destinos/top', [PublicDestinoController::class, 'top']);
    Route::get('destinos/nearby', [PublicDestinoController::class, 'nearby']);
    Route::get('destinos/{slug}', [PublicDestinoController::class, 'show']);
    Route::get('destinos/{slug}/similar', [PublicDestinoController::class, 'similar']);
    Route::get('destinos/{slug}/stats', [PublicDestinoController::class, 'getStats']);
    Route::get('destinos/{destino}/reviews', [ReviewController::class, 'getDestinoReviews']);
    Route::get('promociones', [PromocionController::class, 'index']);
    Route::get('destinos/{destino}/promociones', [PromocionController::class, 'forDestino']);
    Route::get('regiones', [\App\Http\Controllers\Api\Public\RegionController::class, 'index']);
    Route::get('regiones/{slug}', [\App\Http\Controllers\Api\Public\RegionController::class, 'show']);
    Route::get('categorias', [\App\Http\Controllers\Api\Public\CategoriaController::class, 'index']);
    Route::get('categorias/{slug}', [\App\Http\Controllers\Api\Public\CategoriaController::class, 'show']);
    Route::get('tags', [\App\Http\Controllers\Api\Public\TagController::class, 'index']);
    Route::get('tags/{slug}', [\App\Http\Controllers\Api\Public\TagController::class, 'show']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Webhook de suscripción (no requiere autenticación)
Route::post('v1/subscription/webhook', [SubscriptionController::class, 'webhook'])->name('subscription.webhook');

Route::middleware(['auth:sanctum'])->group(function () {
    // Rutas de autenticación
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    
    // Reseñas
    Route::post('reviews/{id}/reply', [PublicDestinoController::class, 'replyToReview'])->name('reviews.reply');
    
    // Eventos turísticos (proveedores)
    Route::post('provider/eventos', [ProviderEventoController::class, 'store'])->name('provider.eventos.store');
});
