<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\CaracteristicaController;
use App\Http\Controllers\Api\FavoritoController;
use App\Http\Controllers\Api\PromocionController;
use App\Http\Controllers\Api\Public\DestinoController as PublicDestinoController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\UserController;
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
    });

    // Ruta de Búsqueda Global
    Route::get('/search', SearchController::class)->name('search');
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
    Route::get('destinos/{slug}', [PublicDestinoController::class, 'show'])->name('destinos.show');
    Route::get('destinos/{destino}/reviews', [ReviewController::class, 'getDestinoReviews'])->name('destinos.reviews');
    Route::get('destinos/{destino}/promociones', [PromocionController::class, 'forDestino'])->name('destinos.promociones');

    // Rutas públicas para promociones
    Route::get('promociones', [PromocionController::class, 'index'])->name('promociones.index');
    
    Route::get('home', [\App\Http\Controllers\Api\Public\HomeController::class, 'index']);
});

// Rutas públicas que no requieren autenticación
Route::prefix('public')->group(function () {
    Route::get('destinos', [PublicDestinoController::class, 'index']);
    Route::get('destinos/top', [PublicDestinoController::class, 'top']);
    Route::get('destinos/{slug}', [PublicDestinoController::class, 'show']);
    Route::get('destinos/{destino}/reviews', [ReviewController::class, 'getDestinoReviews']);
    Route::get('promociones', [PromocionController::class, 'index']);
    Route::get('destinos/{destino}/promociones', [PromocionController::class, 'forDestino']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
