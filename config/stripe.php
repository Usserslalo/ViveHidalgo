<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para la integración con Stripe para pagos y facturación.
    | Las claves se obtienen desde las variables de entorno.
    |
    */

    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Stripe API Version
    |--------------------------------------------------------------------------
    |
    | Versión de la API de Stripe a utilizar.
    |
    */
    'api_version' => env('STRIPE_API_VERSION', '2023-10-16'),

    /*
    |--------------------------------------------------------------------------
    | Currency Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de monedas soportadas.
    |
    */
    'currency' => env('STRIPE_CURRENCY', 'mxn'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Events
    |--------------------------------------------------------------------------
    |
    | Eventos de webhook que se procesarán.
    |
    */
    'webhook_events' => [
        'invoice.payment_succeeded',
        'invoice.payment_failed',
        'customer.subscription.created',
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'payment_method.attached',
        'payment_method.detached',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Plans
    |--------------------------------------------------------------------------
    |
    | Configuración de planes de suscripción disponibles.
    |
    */
    'plans' => [
        'basic' => [
            'name' => 'Plan Básico',
            'price_id' => env('STRIPE_BASIC_PLAN_PRICE_ID'),
            'amount' => 29900, // $299.00 MXN
            'currency' => 'mxn',
            'interval' => 'month',
            'features' => [
                'max_destinos' => 5,
                'max_imagenes' => 20,
                'support' => 'email',
            ],
        ],
        'premium' => [
            'name' => 'Plan Premium',
            'price_id' => env('STRIPE_PREMIUM_PLAN_PRICE_ID'),
            'amount' => 59900, // $599.00 MXN
            'currency' => 'mxn',
            'interval' => 'month',
            'features' => [
                'max_destinos' => 20,
                'max_imagenes' => 100,
                'support' => 'priority',
                'analytics' => true,
            ],
        ],
        'enterprise' => [
            'name' => 'Plan Enterprise',
            'price_id' => env('STRIPE_ENTERPRISE_PLAN_PRICE_ID'),
            'amount' => 99900, // $999.00 MXN
            'currency' => 'mxn',
            'interval' => 'month',
            'features' => [
                'max_destinos' => -1, // Ilimitado
                'max_imagenes' => -1, // Ilimitado
                'support' => 'dedicated',
                'analytics' => true,
                'custom_domain' => true,
            ],
        ],
    ],
]; 