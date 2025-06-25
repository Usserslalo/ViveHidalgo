# 🚀 Guía Completa de Stripe para Vive Hidalgo

## 📋 Índice
1. [Configuración Inicial](#configuración-inicial)
2. [Comandos de Configuración](#comandos-de-configuración)
3. [Cómo Funciona Stripe](#cómo-funciona-stripe)
4. [Flujo de Pagos](#flujo-de-pagos)
5. [Ejemplos de Uso](#ejemplos-de-uso)
6. [Webhooks](#webhooks)
7. [Testing](#testing)
8. [Producción](#producción)

---

## 🔧 Configuración Inicial

### 1. Variables de Entorno
Agrega estas variables a tu archivo `.env`:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_tu_clave_publica_aqui
STRIPE_SECRET_KEY=sk_test_tu_clave_secreta_aqui
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui
STRIPE_CURRENCY=mxn
STRIPE_MODE=test
```

### 2. Instalar Dependencias
```bash
composer require stripe/stripe-php
```

---

## ⚙️ Comandos de Configuración

### 1. Probar Conexión
```bash
php artisan stripe:test-connection
```
Este comando verifica que las claves funcionen correctamente.

### 2. Configurar Productos
```bash
php artisan stripe:setup-products
```
Este comando crea automáticamente los productos y precios en Stripe.

### 3. Forzar Recreación de Productos
```bash
php artisan stripe:setup-products --force
```

---

## 🧠 Cómo Funciona Stripe

### Conceptos Básicos

1. **Customer (Cliente)**: Representa a un usuario en Stripe
2. **Product (Producto)**: Lo que vendes (ej: Plan Básico, Premium)
3. **Price (Precio)**: El precio del producto
4. **Payment Method (Método de Pago)**: Tarjeta, banco, etc.
5. **Subscription (Suscripción)**: Pago recurrente
6. **Invoice (Factura)**: Documento de cobro
7. **Webhook**: Notificaciones automáticas de eventos

### Flujo de Datos
```
Usuario → Customer → Payment Method → Subscription → Invoice → Webhook → Base de Datos
```

---

## 💳 Flujo de Pagos

### 1. Checkout Session (Recomendado)
```javascript
// Frontend (React/Vue)
const response = await fetch('/api/v1/stripe-demo/create-checkout', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        plan_type: 'premium',
        success_url: 'http://localhost:3000/success',
        cancel_url: 'http://localhost:3000/cancel'
    })
});

const { data } = await response.json();
// Redirigir a Stripe Checkout
window.location.href = data.checkout_url;
```

### 2. Payment Intent (Más Control)
```javascript
// Frontend
const stripe = Stripe('pk_test_...');
const elements = stripe.elements();
const card = elements.create('card');

// Procesar pago
const { paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
    payment_method: {
        card: card,
        billing_details: {
            name: 'Juan Pérez'
        }
    }
});
```

---

## 📝 Ejemplos de Uso

### 1. Obtener Clave Pública
```bash
GET /api/v1/stripe-demo/publishable-key
```

### 2. Crear Checkout
```bash
POST /api/v1/stripe-demo/create-checkout
{
    "plan_type": "premium",
    "success_url": "http://localhost:3000/success",
    "cancel_url": "http://localhost:3000/cancel"
}
```

### 3. Procesar Pago Directo
```bash
POST /api/v1/stripe-demo/process-payment
{
    "amount": 599.00,
    "currency": "mxn",
    "payment_method_id": "pm_..."
}
```

### 4. Obtener Métodos de Pago
```bash
GET /api/v1/stripe-demo/payment-methods
```

### 5. Información del Cliente
```bash
GET /api/v1/stripe-demo/customer-info
```

---

## 🔗 Webhooks

### 1. Configurar Webhook en Stripe Dashboard
1. Ve a [Stripe Dashboard](https://dashboard.stripe.com/webhooks)
2. Crea un nuevo webhook
3. URL: `https://tudominio.com/api/v1/payments/webhook`
4. Eventos a escuchar:
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`

### 2. Obtener Webhook Secret
```bash
# Copia el webhook secret del dashboard y agrégalo a .env
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 3. Procesar Webhooks
```php
// El webhook se procesa automáticamente en:
// app/Http/Controllers/Api/PaymentController@webhook
```

---

## 🧪 Testing

### 1. Tarjetas de Prueba
- **Éxito**: `4242 4242 4242 4242`
- **Fallo**: `4000 0000 0000 0002`
- **Requiere Autenticación**: `4000 0025 0000 3155`

### 2. Ejecutar Tests
```bash
php artisan test --filter=PaymentTest
```

### 3. Probar Webhooks Localmente
```bash
# Instalar Stripe CLI
stripe listen --forward-to localhost:8000/api/v1/payments/webhook
```

---

## 🚀 Producción

### 1. Cambiar a Modo Producción
```env
STRIPE_MODE=live
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
```

### 2. Configurar Webhooks de Producción
- URL: `https://tudominio.com/api/v1/payments/webhook`
- Usar webhook secret de producción

### 3. Monitoreo
- [Stripe Dashboard](https://dashboard.stripe.com)
- [Stripe Logs](https://dashboard.stripe.com/logs)
- [Stripe Analytics](https://dashboard.stripe.com/analytics)

---

## 📊 Planes Configurados

### Plan Básico
- **Precio**: $299.00 MXN/mes
- **Destinos**: 5
- **Imágenes**: 20
- **Soporte**: Email

### Plan Premium
- **Precio**: $599.00 MXN/mes
- **Destinos**: 20
- **Imágenes**: 100
- **Soporte**: Prioritario
- **Analytics**: Sí

### Plan Enterprise
- **Precio**: $999.00 MXN/mes
- **Destinos**: Ilimitado
- **Imágenes**: Ilimitado
- **Soporte**: Dedicado
- **Analytics**: Sí
- **Dominio Personalizado**: Sí

---

## 🔒 Seguridad

### 1. Nunca Expongas la Clave Secreta
```javascript
// ❌ MALO
const stripe = Stripe('sk_test_...');

// ✅ BUENO
const stripe = Stripe('pk_test_...');
```

### 2. Validar Webhooks
```php
// Se valida automáticamente en el controlador
$signature = $request->header('Stripe-Signature');
```

### 3. Usar HTTPS en Producción
```env
APP_URL=https://tudominio.com
```

---

## 🆘 Solución de Problemas

### Error: "No such customer"
```bash
# Verificar que el usuario tenga stripe_customer_id
php artisan tinker
>>> User::find(1)->stripe_customer_id
```

### Error: "Invalid API key"
```bash
# Verificar variables de entorno
php artisan stripe:test-connection
```

### Error: "Webhook signature verification failed"
```bash
# Verificar webhook secret
echo $STRIPE_WEBHOOK_SECRET
```

---

## 📚 Recursos Adicionales

- [Stripe Documentation](https://stripe.com/docs)
- [Stripe API Reference](https://stripe.com/docs/api)
- [Stripe Testing Guide](https://stripe.com/docs/testing)
- [Stripe Webhooks](https://stripe.com/docs/webhooks)

---

## 🎯 Próximos Pasos

1. **Configurar productos**: `php artisan stripe:setup-products`
2. **Probar conexión**: `php artisan stripe:test-connection`
3. **Configurar webhooks** en Stripe Dashboard
4. **Integrar en frontend** usando los ejemplos
5. **Probar con tarjetas de prueba**
6. **Monitorear logs** en Stripe Dashboard

¡Con esto ya tienes todo lo necesario para usar Stripe en tu proyecto! 🎉 