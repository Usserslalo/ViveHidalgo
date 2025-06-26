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
        Schema::create('destino_promocion_destacada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_destacada_id')->constrained('promocion_destacadas')->onDelete('cascade');
            $table->foreignId('destino_id')->constrained('destinos')->onDelete('cascade');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['promocion_destacada_id', 'destino_id'], 'promo_destino_unique');
            
            // Índices para mejorar el rendimiento
            $table->index('promocion_destacada_id');
            $table->index('destino_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('destino_promocion_destacada');
    }
};