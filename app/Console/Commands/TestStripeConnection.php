<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

class TestStripeConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Stripe connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Probando conexión con Stripe...');

        // Verificar variables de entorno
        $this->info('📋 Verificando configuración...');
        
        $publishableKey = config('stripe.publishable_key');
        $secretKey = config('stripe.secret_key');
        $currency = config('stripe.currency');

        // Si no se pueden leer del config, mostrar error
        if (!$publishableKey) {
            $this->error('❌ STRIPE_PUBLISHABLE_KEY no está configurada en .env');
            return 1;
        }

        if (!$secretKey) {
            $this->error('❌ STRIPE_SECRET_KEY no está configurada en .env');
            return 1;
        }

        if (!$currency) {
            $currency = 'mxn';
        }

        $this->info('✅ Variables configuradas');
        $this->info("💰 Moneda: {$currency}");

        // Configurar Stripe
        Stripe::setApiKey($secretKey);

        try {
            // Probar conexión creando un customer de prueba
            $this->info('🔗 Probando conexión con la API de Stripe...');
            
            $customer = Customer::create([
                'email' => 'test@example.com',
                'name' => 'Cliente de Prueba',
                'description' => 'Cliente creado para probar la conexión con Stripe',
            ]);

            $this->info('✅ Conexión exitosa con Stripe!');
            $this->info("👤 Customer ID creado: {$customer->id}");

            // Eliminar el customer de prueba
            $customer->delete();
            $this->info('🧹 Customer de prueba eliminado');

            // Mostrar información de planes
            $this->info('📦 Planes configurados:');
            $plans = config('stripe.plans');
            
            foreach ($plans as $planKey => $plan) {
                $this->info("  - {$plan['name']}: $" . number_format($plan['amount'] / 100, 2) . " {$plan['currency']}");
            }

            $this->info('🎉 ¡Todo está configurado correctamente!');
            return 0;

        } catch (ApiErrorException $e) {
            $this->error('❌ Error de API de Stripe: ' . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error('❌ Error inesperado: ' . $e->getMessage());
            return 1;
        }
    }
} 