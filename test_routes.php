<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;

echo "Verificando rutas de autenticación:\n\n";

$routes = Route::getRoutes();

foreach ($routes as $route) {
    if (str_contains($route->uri(), 'login') || str_contains($route->uri(), 'register') || str_contains($route->uri(), 'password')) {
        echo "Método: " . implode('|', $route->methods()) . " | URI: " . $route->uri() . " | Nombre: " . $route->getName() . "\n";
    }
}

echo "\nVerificación completada.\n"; 