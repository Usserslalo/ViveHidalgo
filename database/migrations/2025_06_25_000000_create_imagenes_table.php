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
        Schema::create('imagenes', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable'); // imageable_type, imageable_id - Laravel crea el índice automáticamente
            $table->string('path'); // Ruta del archivo
            $table->string('alt')->nullable(); // Texto alternativo
            $table->integer('orden')->default(0); // Orden de las imágenes
            $table->boolean('is_main')->default(false); // Imagen principal
            $table->string('disk')->default('public'); // Disco de almacenamiento
            $table->string('mime_type')->nullable(); // Tipo MIME
            $table->integer('size')->nullable(); // Tamaño en bytes
            $table->timestamps();

            // Índices adicionales (no el de morphs que Laravel crea automáticamente)
            $table->index(['is_main']);
            $table->index(['orden']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imagenes');
    }
}; 