# Pagos y Facturación

## 💸 FUNCIONALIDADES DE MONETIZACIÓN REAL

### 1. INTEGRACIÓN CON STRIPE

#### 1.1 Configuración
```php
// config/stripe.php
return [
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'secret_key' => env('STRIPE_SECRET_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
```

#### 1.2 Servicio de Stripe
```php
// app/Services/StripeService.php
class StripeService
{
    public function createCheckoutSession($subscriptionData)
    public function handleWebhook($payload)
    public function createCustomer($user)
    public function updatePaymentMethod($user, $paymentMethodId)
}
```

#### 1.3 Controlador de Pagos
```php
// app/Http/Controllers/Api/PaymentController.php
class PaymentController extends BaseController
{
    public function createCheckoutSession(Request $request)
    public function webhook(Request $request)
    public function getInvoices(Request $request)
    public function updatePaymentMethod(Request $request)
}
```

### 2. MODELOS DE FACTURACIÓN

#### 2.1 Invoice
```php
// app/Models/Invoice.php
- user_id
- subscription_id
- stripe_invoice_id
- amount
- currency
- status
- due_date
- paid_at
```

#### 2.2 PaymentMethod
```php
// app/Models/PaymentMethod.php
- user_id
- stripe_payment_method_id
- type (card, bank_account)
- last4
- brand
- is_default
```

### 3. MIGRACIONES DE FACTURACIÓN
```sql
CREATE TABLE invoices (
    id bigint unsigned PRIMARY KEY,
    user_id bigint unsigned,
    subscription_id bigint unsigned,
    stripe_invoice_id varchar(255),
    amount decimal(10,2),
    currency varchar(3),
    status enum('draft','open','paid','void','uncollectible'),
    due_date date,
    paid_at timestamp NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE payment_methods (
    id bigint unsigned PRIMARY KEY,
    user_id bigint unsigned,
    stripe_payment_method_id varchar(255),
    type varchar(50),
    last4 varchar(4),
    brand varchar(50),
    is_default boolean DEFAULT false,
    created_at timestamp,
    updated_at timestamp
);
``` 