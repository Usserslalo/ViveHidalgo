<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plan_type'); // basic, premium, enterprise
            $table->string('status'); // active, cancelled, expired, pending
            $table->decimal('amount', 10, 2); // Monto de la suscripción
            $table->string('currency', 3)->default('MXN'); // Moneda
            $table->date('start_date'); // Fecha de inicio
            $table->date('end_date'); // Fecha de fin
            $table->date('next_billing_date')->nullable(); // Próxima fecha de facturación
            $table->string('billing_cycle'); // monthly, quarterly, yearly
            $table->boolean('auto_renew')->default(true); // Renovación automática
            $table->string('payment_method')->nullable(); // Método de pago
            $table->string('payment_status')->default('pending'); // pending, completed, failed
            $table->string('transaction_id')->nullable(); // ID de transacción externa
            $table->text('notes')->nullable(); // Notas adicionales
            $table->json('features')->nullable(); // Características incluidas en el plan
            $table->timestamps();
            
            // Índices para optimización
            $table->index(['user_id', 'status']);
            $table->index(['status', 'end_date']);
            $table->index('plan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
}; 