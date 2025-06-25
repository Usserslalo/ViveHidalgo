# Configuración de Stripe para Pagos y Facturación

## 📋 Requisitos Previos

1. **Cuenta de Stripe**: Crear una cuenta en [stripe.com](https://stripe.com)
2. **Laravel 10+**: Asegurarse de que el proyecto esté en Laravel 10 o superior
3. **Composer**: Tener Composer instalado

## 🔧 Instalación y Configuración

### 1. Instalar dependencias de Stripe

```bash
composer require stripe/stripe-php
```

### 2. Configurar variables de entorno

Agregar las siguientes variables al archivo `.env`:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_API_VERSION=2023-10-16
STRIPE_CURRENCY=mxn

# Planes de suscripción (IDs de precios en Stripe)
STRIPE_BASIC_PLAN_PRICE_ID=price_...
STRIPE_PREMIUM_PLAN_PRICE_ID=price_...
STRIPE_ENTERPRISE_PLAN_PRICE_ID=price_...

# URLs del frontend
FRONTEND_URL=http://localhost:3000
```

### 3. Ejecutar migraciones

```bash
php artisan migrate
```

### 4. Configurar webhooks en Stripe

1. Ir al [Dashboard de Stripe](https://dashboard.stripe.com/webhooks)
2. Crear un nuevo webhook con la URL: `https://tu-dominio.com/api/v1/payments/webhook`
3. Seleccionar los siguientes eventos:
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `payment_method.attached`
   - `payment_method.detached`
4. Copiar el `Signing secret` y agregarlo a `STRIPE_WEBHOOK_SECRET`

## 💳 Configuración de Planes de Suscripción

### 1. Crear productos en Stripe

En el Dashboard de Stripe, crear los siguientes productos:

#### Plan Básico
- **Nombre**: Plan Básico
- **Precio**: $299.00 MXN/mes
- **Descripción**: Hasta 5 destinos, 20 imágenes, soporte por email

#### Plan Premium
- **Nombre**: Plan Premium
- **Precio**: $599.00 MXN/mes
- **Descripción**: Hasta 20 destinos, 100 imágenes, soporte prioritario, analytics

#### Plan Enterprise
- **Nombre**: Plan Enterprise
- **Precio**: $999.00 MXN/mes
- **Descripción**: Destinos ilimitados, imágenes ilimitadas, soporte dedicado, analytics, dominio personalizado

### 2. Obtener IDs de precios

Para cada producto creado, copiar el `Price ID` y agregarlo a las variables de entorno correspondientes.

## 🧪 Configuración para Testing

### 1. Usar claves de prueba

Asegurarse de usar las claves de prueba (`pk_test_` y `sk_test_`) durante el desarrollo.

### 2. Tarjetas de prueba

Usar las siguientes tarjetas de prueba de Stripe:

- **Visa**: `4242424242424242`
- **Mastercard**: `5555555555554444`
- **American Express**: `378282246310005`
- **Tarjeta que requiere autenticación**: `4000002500003155`
- **Tarjeta que falla**: `4000000000000002`

### 3. Webhook de prueba

Para testing local, usar [Stripe CLI](https://stripe.com/docs/stripe-cli):

```bash
# Instalar Stripe CLI
stripe login

# Escuchar webhooks localmente
stripe listen --forward-to localhost:8000/api/v1/payments/webhook
```

## 🔒 Seguridad

### 1. Validación de webhooks

Los webhooks incluyen validación de firma para prevenir ataques de replay.

### 2. Autenticación

Todos los endpoints de pago requieren autenticación con Sanctum.

### 3. Validación de datos

Se implementa validación exhaustiva en todos los endpoints.

## 📊 Monitoreo

### 1. Logs

Los eventos de pago se registran en los logs de Laravel:
- `storage/logs/laravel.log`

### 2. Dashboard de Stripe

Monitorear transacciones en el [Dashboard de Stripe](https://dashboard.stripe.com).

### 3. Métricas

El sistema incluye endpoints para obtener estadísticas de facturación.

## 🚀 Despliegue

### 1. Variables de producción

Cambiar a claves de producción en el servidor:
- `STRIPE_PUBLISHABLE_KEY=pk_live_...`
- `STRIPE_SECRET_KEY=sk_live_...`

### 2. Webhook de producción

Configurar el webhook con la URL de producción:
`https://tu-dominio-produccion.com/api/v1/payments/webhook`

### 3. SSL

Asegurarse de que el dominio tenga SSL habilitado (requerido por Stripe).

## 🔧 Comandos Útiles

### Crear datos de prueba

```bash
# Crear facturas de prueba
php artisan tinker
>>> App\Models\Invoice::factory()->count(10)->create();

# Crear métodos de pago de prueba
>>> App\Models\PaymentMethod::factory()->count(5)->create();
```

### Ejecutar tests

```bash
php artisan test --filter=PaymentTest
```

## 📝 Notas Importantes

1. **Monedas**: El sistema está configurado para MXN por defecto
2. **Planes**: Los planes están hardcodeados en `config/stripe.php`
3. **Webhooks**: Los webhooks son críticos para mantener sincronización
4. **Testing**: Siempre usar claves de prueba durante desarrollo
5. **Backup**: Hacer backup regular de la base de datos

## 🆘 Solución de Problemas

### Error: "No such customer"

- Verificar que el usuario tenga `stripe_customer_id`
- Crear cliente de Stripe si no existe

### Error: "Invalid webhook signature"

- Verificar `STRIPE_WEBHOOK_SECRET`
- Asegurarse de que la URL del webhook sea correcta

### Error: "Plan not found"

- Verificar que los `Price IDs` estén correctos
- Verificar que los planes estén configurados en `config/stripe.php`

### Error: "Payment method not found"

- Verificar que el `payment_method_id` sea válido
- Verificar que pertenezca al usuario correcto

## 📞 Soporte

Para problemas específicos de Stripe, consultar la [documentación oficial](https://stripe.com/docs).

Para problemas del sistema, revisar los logs de Laravel y la documentación del proyecto. 