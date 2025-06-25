# Configuración de Webhook de Stripe

## 🔧 Configuración en Stripe Dashboard

### 1. Acceder al Dashboard de Stripe
1. Ve a [dashboard.stripe.com](https://dashboard.stripe.com)
2. Inicia sesión con tu cuenta de Stripe
3. Asegúrate de estar en el modo correcto (Test/Live)

### 2. Crear el Webhook
1. En el menú lateral, ve a **Developers** > **Webhooks**
2. Haz clic en **Add endpoint**
3. Configura el endpoint:
   - **Endpoint URL**: `https://tu-dominio.com/api/v1/payments/webhook`
   - **Events to send**: Selecciona los siguientes eventos:
     - `invoice.payment_succeeded`
     - `invoice.payment_failed`
     - `customer.subscription.created`
     - `customer.subscription.updated`
     - `customer.subscription.deleted`
     - `payment_method.attached`
     - `payment_method.detached`

### 3. Obtener el Webhook Secret
1. Después de crear el webhook, haz clic en **Reveal** en la sección **Signing secret**
2. Copia el secret que comienza con `whsec_`
3. Agrega este secret a tu archivo `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_tu_webhook_secret_aqui
```

## 🧪 Testing del Webhook

### 1. Usar Stripe CLI (Recomendado)
```bash
# Instalar Stripe CLI
# Windows: https://github.com/stripe/stripe-cli/releases
# macOS: brew install stripe/stripe-cli/stripe
# Linux: https://github.com/stripe/stripe-cli#installation

# Login
stripe login

# Escuchar webhooks localmente
stripe listen --forward-to localhost:8000/api/v1/payments/webhook

# En otra terminal, disparar un evento de prueba
stripe trigger invoice.payment_succeeded
```

### 2. Usar el Dashboard de Stripe
1. Ve a **Developers** > **Webhooks**
2. Selecciona tu webhook
3. Haz clic en **Send test webhook**
4. Selecciona un evento y haz clic en **Send test webhook**

## 🔍 Verificación

### 1. Verificar en los Logs
```bash
# Ver logs de Laravel
tail -f storage/logs/laravel.log

# Buscar eventos de webhook
grep "Stripe webhook received" storage/logs/laravel.log
```

### 2. Verificar en la Base de Datos
```sql
-- Verificar facturas actualizadas
SELECT id, status, paid_at FROM invoices WHERE stripe_invoice_id IS NOT NULL;

-- Verificar suscripciones
SELECT id, status, stripe_subscription_id FROM subscriptions;
```

### 3. Verificar Notificaciones
```bash
# Verificar que las notificaciones se envían
php artisan queue:work --queue=default
```

## 🚨 Troubleshooting

### Error: "No signatures found matching the expected signature"
- Verifica que `STRIPE_WEBHOOK_SECRET` esté correctamente configurado
- Asegúrate de que el secret no tenga espacios extra
- Verifica que estés usando el secret correcto (Test vs Live)

### Error: "Webhook endpoint request failed"
- Verifica que la URL del webhook sea accesible públicamente
- Asegúrate de que el endpoint responda con HTTP 200
- Verifica los logs de Laravel para errores específicos

### Webhook no se procesa
- Verifica que los eventos estén configurados correctamente
- Asegúrate de que el webhook esté activo en Stripe
- Verifica que la URL del webhook sea correcta

## 📋 Checklist de Configuración

- [ ] Webhook creado en Stripe Dashboard
- [ ] Eventos configurados correctamente
- [ ] Webhook secret agregado al `.env`
- [ ] URL del webhook es accesible públicamente
- [ ] Webhook responde con HTTP 200
- [ ] Logs muestran eventos recibidos
- [ ] Base de datos se actualiza correctamente
- [ ] Notificaciones se envían

## 🔄 Comandos Útiles

```bash
# Limpiar cache de configuración
php artisan config:clear

# Verificar configuración de Stripe
php artisan stripe:check-config

# Probar conexión con Stripe
php artisan stripe:test-connection

# Enviar recordatorios de suscripción (modo dry-run)
php artisan subscriptions:send-reminders --dry-run

# Enviar recordatorios reales
php artisan subscriptions:send-reminders
```

## 📞 Soporte

Si tienes problemas con la configuración:

1. Verifica los logs de Laravel: `storage/logs/laravel.log`
2. Verifica los logs de Stripe en el Dashboard
3. Usa Stripe CLI para debugging local
4. Contacta al equipo de desarrollo con los logs de error 