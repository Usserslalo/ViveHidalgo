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
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 255)->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('MXN');
            $table->integer('max_participants')->nullable();
            $table->integer('min_participants')->nullable();
            $table->enum('difficulty_level', ['facil', 'moderado', 'dificil', 'experto'])->default('moderado');
            $table->integer('age_min')->nullable();
            $table->integer('age_max')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('main_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('included_items')->nullable();
            $table->json('excluded_items')->nullable();
            $table->json('what_to_bring')->nullable();
            $table->json('safety_notes')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->string('meeting_point')->nullable();
            $table->time('meeting_time')->nullable();
            $table->json('seasonal_availability')->nullable();
            $table->boolean('weather_dependent')->default(false);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('destino_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['destino_id', 'is_available']);
            $table->index(['user_id', 'is_available']);
            $table->index(['difficulty_level', 'is_available']);
            $table->index(['is_featured', 'is_available']);
            $table->index(['price', 'is_available']);
        });

        // Tabla pivot para actividades y categorías
        Schema::create('actividad_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['actividad_id', 'categoria_id']);
        });

        // Tabla pivot para actividades y características
        Schema::create('actividad_caracteristica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('caracteristica_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['actividad_id', 'caracteristica_id']);
        });

        // Tabla pivot para actividades y tags
        Schema::create('actividad_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actividad_id')->constrained('actividades')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['actividad_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividad_tag');
        Schema::dropIfExists('actividad_caracteristica');
        Schema::dropIfExists('actividad_categoria');
        Schema::dropIfExists('actividades');
    }
}; 