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
        Schema::create('caracteristica_destino', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caracteristica_id')->constrained()->onDelete('cascade');
            $table->foreignId('destino_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['caracteristica_id', 'destino_id']);
            $table->unique(['caracteristica_id', 'destino_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caracteristica_destino');
    }
}; 