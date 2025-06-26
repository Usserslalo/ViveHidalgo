<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Login personalizado
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

require __DIR__.'/auth.php';

// Ruta para el sitemap
Route::get('sitemap.xml', function () {
    $sitemapPath = public_path('sitemap.xml');
    
    if (!File::exists($sitemapPath)) {
        // Si no existe, generarlo automáticamente
        \Artisan::call('generar:sitemap');
    }
    
    return response()->file($sitemapPath, [
        'Content-Type' => 'application/xml',
        'Cache-Control' => 'public, max-age=3600', // Cache por 1 hora
    ]);
})->name('sitemap.xml');
