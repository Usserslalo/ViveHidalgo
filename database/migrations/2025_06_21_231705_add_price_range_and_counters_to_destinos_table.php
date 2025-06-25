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
        Schema::table('destinos', function (Blueprint $table) {
            // Rango de precios para categorizar destinos
            $table->enum('price_range', ['gratis', 'economico', 'moderado', 'premium'])->nullable()->after('status');
            
            // Contadores para estadísticas
            $table->integer('visit_count')->default(0)->after('price_range');
            $table->integer('favorite_count')->default(0)->after('visit_count');
            
            // Índices para optimizar consultas
            $table->index('price_range');
            $table->index(['visit_count', 'favorite_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['price_range']);
            $table->dropIndex(['visit_count', 'favorite_count']);
            
            // Eliminar columnas
            $table->dropColumn(['price_range', 'visit_count', 'favorite_count']);
        });
    }
}; 