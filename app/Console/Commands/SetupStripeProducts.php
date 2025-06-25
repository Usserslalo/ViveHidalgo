<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;
use Stripe\Exception\ApiErrorException;

class SetupStripeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:setup-products {--force : Forzar recreación de productos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Stripe products and prices for subscription plans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏪 Configurando productos y precios en Stripe...');

        // Configurar Stripe
        Stripe::setApiKey(config('stripe.secret_key'));

        $plans = config('stripe.plans');
        $createdProducts = [];

        foreach ($plans as $planKey => $plan) {
            $this->info("📦 Configurando plan: {$plan['name']}");

            try {
                // Crear o actualizar producto
                $product = $this->createOrUpdateProduct($planKey, $plan);
                $this->info("✅ Producto creado/actualizado: {$product->id}");

                // Crear precio
                $price = $this->createPrice($product->id, $plan);
                $this->info("💰 Precio creado: {$price->id} - $" . number_format($plan['amount'] / 100, 2) . " {$plan['currency']}");

                $createdProducts[$planKey] = [
                    'product_id' => $product->id,
                    'price_id' => $price->id,
                    'name' => $plan['name'],
                    'amount' => $plan['amount'],
                    'currency' => $plan['currency'],
                ];

            } catch (ApiErrorException $e) {
                $this->error("❌ Error creando plan {$plan['name']}: " . $e->getMessage());
                continue;
            }
        }

        // Mostrar resumen
        $this->info('📋 Resumen de productos creados:');
        foreach ($createdProducts as $planKey => $product) {
            $this->info("  - {$product['name']}:");
            $this->info("    Product ID: {$product['product_id']}");
            $this->info("    Price ID: {$product['price_id']}");
            $this->info("    Precio: $" . number_format($product['amount'] / 100, 2) . " {$product['currency']}");
        }

        // Guardar IDs en .env
        $this->info('💾 Actualizando variables de entorno...');
        $this->updateEnvFile($createdProducts);

        $this->info('🎉 ¡Configuración completada!');
        return 0;
    }

    private function createOrUpdateProduct($planKey, $plan)
    {
        $productName = "Vive Hidalgo - {$plan['name']}";
        $productDescription = "Plan de suscripción {$plan['name']} para la plataforma Vive Hidalgo";

        // Buscar producto existente
        $products = Product::all(['limit' => 100]);
        $existingProduct = null;

        foreach ($products->data as $product) {
            if ($product->name === $productName) {
                $existingProduct = $product;
                break;
            }
        }

        if ($existingProduct && !$this->option('force')) {
            $this->info("📦 Producto existente encontrado: {$existingProduct->id}");
            return $existingProduct;
        }

        // Crear nuevo producto
        $productData = [
            'name' => $productName,
            'description' => $productDescription,
            'metadata' => [
                'plan_key' => $planKey,
                'max_destinos' => $plan['features']['max_destinos'] ?? -1,
                'max_imagenes' => $plan['features']['max_imagenes'] ?? -1,
                'support' => $plan['features']['support'] ?? 'email',
            ],
        ];

        if ($existingProduct && $this->option('force')) {
            // Actualizar producto existente
            $product = Product::update($existingProduct->id, $productData);
        } else {
            // Crear nuevo producto
            $product = Product::create($productData);
        }

        return $product;
    }

    private function createPrice($productId, $plan)
    {
        $priceData = [
            'product' => $productId,
            'unit_amount' => $plan['amount'],
            'currency' => $plan['currency'],
            'recurring' => [
                'interval' => $plan['interval'],
            ],
            'metadata' => [
                'plan_type' => $plan['name'],
                'features' => json_encode($plan['features']),
            ],
        ];

        return Price::create($priceData);
    }

    private function updateEnvFile($products)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($products as $planKey => $product) {
            $envKey = "STRIPE_" . strtoupper($planKey) . "_PLAN_PRICE_ID";
            $envValue = $product['price_id'];

            // Buscar si ya existe la variable
            if (strpos($envContent, $envKey) !== false) {
                // Actualizar variable existente
                $envContent = preg_replace(
                    "/^{$envKey}=.*$/m",
                    "{$envKey}={$envValue}",
                    $envContent
                );
            } else {
                // Agregar nueva variable
                $envContent .= "\n{$envKey}={$envValue}";
            }
        }

        file_put_contents($envPath, $envContent);
        $this->info('✅ Variables de entorno actualizadas');
    }
} 