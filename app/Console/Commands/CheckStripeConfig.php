<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckStripeConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:check-config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Stripe configuration variables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Verificando configuración de Stripe...');

        // Verificar variables directamente del .env
        $this->info('📋 Variables del archivo .env:');
        $this->info('STRIPE_PUBLISHABLE_KEY: ' . env('STRIPE_PUBLISHABLE_KEY', 'NO CONFIGURADA'));
        $this->info('STRIPE_SECRET_KEY: ' . env('STRIPE_SECRET_KEY', 'NO CONFIGURADA'));
        $this->info('STRIPE_CURRENCY: ' . env('STRIPE_CURRENCY', 'NO CONFIGURADA'));
        $this->info('STRIPE_MODE: ' . env('STRIPE_MODE', 'NO CONFIGURADA'));

        $this->info('');

        // Verificar variables desde config
        $this->info('📋 Variables desde config/stripe.php:');
        $this->info('publishable_key: ' . config('stripe.publishable_key', 'NO CONFIGURADA'));
        $this->info('secret_key: ' . config('stripe.secret_key', 'NO CONFIGURADA'));
        $this->info('currency: ' . config('stripe.currency', 'NO CONFIGURADA'));

        $this->info('');

        // Verificar si las claves están configuradas
        if (config('stripe.publishable_key') && config('stripe.secret_key')) {
            $this->info('✅ Configuración correcta');
            $this->info('💰 Moneda: ' . config('stripe.currency'));
            $this->info('🔑 Clave pública: ' . substr(config('stripe.publishable_key'), 0, 20) . '...');
            $this->info('🔐 Clave secreta: ' . substr(config('stripe.secret_key'), 0, 20) . '...');
        } else {
            $this->error('❌ Configuración incompleta');
            $this->error('Verifica que las variables estén en el archivo .env');
        }

        return 0;
    }
} 