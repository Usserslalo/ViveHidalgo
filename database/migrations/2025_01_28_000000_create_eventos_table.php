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
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 255)->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->string('location')->nullable();
            $table->decimal('latitude', 8, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->integer('capacity')->default(0);
            $table->integer('current_attendees')->default(0);
            $table->enum('status', ['draft', 'published', 'cancelled'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->string('main_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('contact_info')->nullable();
            $table->string('organizer_name')->nullable();
            $table->string('organizer_email')->nullable();
            $table->string('organizer_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->json('social_media')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('destino_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'start_date']);
            $table->index(['is_featured', 'status']);
            $table->index(['destino_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        // Tabla pivot para eventos y categorías
        Schema::create('evento_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained()->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['evento_id', 'categoria_id']);
        });

        // Tabla pivot para eventos y características
        Schema::create('evento_caracteristica', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained()->onDelete('cascade');
            $table->foreignId('caracteristica_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['evento_id', 'caracteristica_id']);
        });

        // Tabla pivot para eventos y tags
        Schema::create('evento_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['evento_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evento_tag');
        Schema::dropIfExists('evento_caracteristica');
        Schema::dropIfExists('evento_categoria');
        Schema::dropIfExists('eventos');
    }
}; 