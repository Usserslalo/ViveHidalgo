<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\UserController;

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

// Ruta de autenticación simple para pruebas
Route::post('/auth/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'password' => 'required|string|min:6|confirmed',
    ]);

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'is_active' => true,
    ]);

    $user->assignRole('tourist');

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'data' => [
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]
    ]);
});

Route::post('/auth/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    if (!\Illuminate\Support\Facades\Auth::attempt($request->only('email', 'password'))) {
        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas',
        ], 401);
    }

    $user = \App\Models\User::where('email', $request->email)->first();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login exitoso',
        'data' => [
            'user' => $user->load('roles'),
            'token' => $token,
            'token_type' => 'Bearer',
        ]
    ]);
});

// Ruta protegida de prueba
Route::middleware('auth:sanctum')->get('/user/profile', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Perfil obtenido exitosamente',
        'data' => $request->user()->load('roles')
    ]);
});

// Rutas públicas (sin autenticación) - Comentadas hasta crear los controladores
/*
Route::prefix('v1')->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('register', [App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
        Route::post('login', [App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
        Route::post('forgot-password', [App\Http\Controllers\Api\Auth\AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [App\Http\Controllers\Api\Auth\AuthController::class, 'resetPassword']);
    });

    // Destinos turísticos (públicos)
    Route::prefix('destinos')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\DestinoController::class, 'index']);
        Route::get('/{destino}', [App\Http\Controllers\Api\DestinoController::class, 'show']);
        Route::get('/categoria/{categoria}', [App\Http\Controllers\Api\DestinoController::class, 'byCategory']);
        Route::get('/region/{region}', [App\Http\Controllers\Api\DestinoController::class, 'byRegion']);
        Route::get('/buscar', [App\Http\Controllers\Api\DestinoController::class, 'search']);
    });

    // Categorías (públicas)
    Route::get('categorias', [App\Http\Controllers\Api\CategoriaController::class, 'index']);
    
    // Regiones (públicas)
    Route::get('regiones', [App\Http\Controllers\Api\RegionController::class, 'index']);
    
    // Promociones (públicas)
    Route::get('promociones', [App\Http\Controllers\Api\PromocionController::class, 'index']);
    Route::get('promociones/activas', [App\Http\Controllers\Api\PromocionController::class, 'activas']);
    
    // Top Spots (públicos)
    Route::get('top-spots', [App\Http\Controllers\Api\TopSpotController::class, 'index']);
});
*/

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
        Route::get('favoritos', [App\Http\Controllers\Api\FavoritoController::class, 'getUserFavorites']);
        Route::post('favoritos/{destino_id}', [App\Http\Controllers\Api\FavoritoController::class, 'addToFavorites']);
        Route::delete('favoritos/{destino_id}', [App\Http\Controllers\Api\FavoritoController::class, 'removeFromFavorites']);
        Route::get('favoritos/check/{destino_id}', [App\Http\Controllers\Api\FavoritoController::class, 'checkIfFavorite']);
        
        // Reseñas
        Route::get('reviews', [App\Http\Controllers\Api\ReviewController::class, 'getUserReviews']);
        Route::post('reviews/{destino}', [App\Http\Controllers\Api\ReviewController::class, 'store']);
        Route::put('reviews/{review}', [App\Http\Controllers\Api\ReviewController::class, 'update']);
        Route::delete('reviews/{review}', [App\Http\Controllers\Api\ReviewController::class, 'destroy']);
        
        // Historial
        Route::get('historial', [App\Http\Controllers\Api\HistorialController::class, 'index']);
    });
});

// Rutas para proveedores (requieren rol provider)
Route::prefix('v1')->middleware(['auth:sanctum', 'role:provider'])->group(function () {
    
    Route::prefix('provider')->group(function () {
        // Perfil del proveedor
        Route::get('profile', [App\Http\Controllers\Api\Provider\ProviderController::class, 'profile']);
        Route::put('profile', [App\Http\Controllers\Api\Provider\ProviderController::class, 'updateProfile']);
        
        // Destinos del proveedor
        Route::get('destinos', [App\Http\Controllers\Api\Provider\DestinoController::class, 'index']);
        Route::post('destinos', [App\Http\Controllers\Api\Provider\DestinoController::class, 'store']);
        Route::get('destinos/{destino}', [App\Http\Controllers\Api\Provider\DestinoController::class, 'show']);
        Route::put('destinos/{destino}', [App\Http\Controllers\Api\Provider\DestinoController::class, 'update']);
        Route::delete('destinos/{destino}', [App\Http\Controllers\Api\Provider\DestinoController::class, 'destroy']);
        
        // Promociones del proveedor
        Route::get('promociones', [App\Http\Controllers\Api\Provider\PromocionController::class, 'index']);
        Route::post('promociones', [App\Http\Controllers\Api\Provider\PromocionController::class, 'store']);
        Route::put('promociones/{promocion}', [App\Http\Controllers\Api\Provider\PromocionController::class, 'update']);
        Route::delete('promociones/{promocion}', [App\Http\Controllers\Api\Provider\PromocionController::class, 'destroy']);
        
        // Estadísticas
        Route::get('stats', [App\Http\Controllers\Api\Provider\StatsController::class, 'index']);
        
        // Suscripción
        Route::get('subscription', [App\Http\Controllers\Api\Provider\SubscriptionController::class, 'show']);
        Route::post('subscription', [App\Http\Controllers\Api\Provider\SubscriptionController::class, 'store']);
        Route::put('subscription', [App\Http\Controllers\Api\Provider\SubscriptionController::class, 'update']);
        Route::delete('subscription', [App\Http\Controllers\Api\Provider\SubscriptionController::class, 'cancel']);
    });
});

// Rutas para administradores (requieren rol admin)
Route::prefix('v1')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    Route::prefix('admin')->group(function () {
        // Gestión de usuarios
        Route::get('users', [App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::get('users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'show']);
        Route::put('users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'update']);
        Route::delete('users/{user}', [App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
        
        // Gestión de destinos
        Route::get('destinos', [App\Http\Controllers\Api\Admin\DestinoController::class, 'index']);
        Route::get('destinos/{destino}', [App\Http\Controllers\Api\Admin\DestinoController::class, 'show']);
        Route::put('destinos/{destino}', [App\Http\Controllers\Api\Admin\DestinoController::class, 'update']);
        Route::delete('destinos/{destino}', [App\Http\Controllers\Api\Admin\DestinoController::class, 'destroy']);
        
        // Gestión de promociones
        Route::get('promociones', [App\Http\Controllers\Api\Admin\PromocionController::class, 'index']);
        Route::get('promociones/{promocion}', [App\Http\Controllers\Api\Admin\PromocionController::class, 'show']);
        Route::put('promociones/{promocion}', [App\Http\Controllers\Api\Admin\PromocionController::class, 'update']);
        Route::delete('promociones/{promocion}', [App\Http\Controllers\Api\Admin\PromocionController::class, 'destroy']);
        
        // Estadísticas globales
        Route::get('stats', [App\Http\Controllers\Api\Admin\StatsController::class, 'index']);
        
        // Gestión de suscripciones
        Route::get('subscriptions', [App\Http\Controllers\Api\Admin\SubscriptionController::class, 'index']);
        Route::get('subscriptions/{subscription}', [App\Http\Controllers\Api\Admin\SubscriptionController::class, 'show']);
        Route::put('subscriptions/{subscription}', [App\Http\Controllers\Api\Admin\SubscriptionController::class, 'update']);
    });
});

// --- AUTHENTICATION ---
// All routes will be prefixed with `/api` automatically by Laravel
Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('api.auth.me');
    });
});

// --- USER ---
Route::prefix('v1/user')->middleware('auth:sanctum')->group(function () {
    Route::get('profile', [UserController::class, 'profile'])->name('api.user.profile');
    Route::put('profile', [UserController::class, 'updateProfile'])->name('api.user.update-profile');
    Route::post('change-password', [UserController::class, 'changePassword'])->name('api.user.change-password');
    Route::delete('account', [UserController::class, 'deleteAccount'])->name('api.user.delete-account');
    Route::get('stats', [UserController::class, 'stats'])->name('api.user.stats');
});

// --- CONTENT: REGIONS ---
Route::apiResource('v1/regions', App\Http\Controllers\Api\RegionController::class);

// --- CONTENT: CATEGORIES ---
Route::apiResource('v1/categorias', App\Http\Controllers\Api\CategoriaController::class);

// --- CONTENT: DESTINOS ---
Route::apiResource('v1/destinos', App\Http\Controllers\Api\DestinoController::class);

// --- CONTENT: CARACTERISTICAS ---
Route::get('v1/caracteristicas/activas', [App\Http\Controllers\Api\CaracteristicaController::class, 'activas']);
Route::get('v1/caracteristicas/tipo/{tipo}', [App\Http\Controllers\Api\CaracteristicaController::class, 'porTipo']);
Route::apiResource('v1/caracteristicas', App\Http\Controllers\Api\CaracteristicaController::class);

// --- PUBLIC API (No Auth Required) ---
Route::prefix('v1/public')->name('api.public.')->group(function () {
    Route::get('destinos', [App\Http\Controllers\Api\Public\DestinoController::class, 'index'])->name('destinos.index');
    Route::get('destinos/{slug}', [App\Http\Controllers\Api\Public\DestinoController::class, 'show'])->name('destinos.show');
    Route::get('destinos/{destino}/reviews', [App\Http\Controllers\Api\ReviewController::class, 'getDestinoReviews'])->name('destinos.reviews');
    // Aquí podrías agregar rutas para regiones y categorías públicas si es necesario
    // Route::get('regions', [App\Http\Controllers\Api\Public\RegionController::class, 'index'])->name('regions.index');
    // Route::get('categories', [App\Http\Controllers\Api\Public\CategoryController::class, 'index'])->name('categories.index');
});

// --- VIVE HIDALGO CONTENT (To be implemented later) ---
/*
Route::prefix('v1')->group(function () {
    // Destinos turísticos (públicos)
    Route::prefix('destinos')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\DestinoController::class, 'index']);
        // ... more routes
    });
    // ... more sections
});
*/
