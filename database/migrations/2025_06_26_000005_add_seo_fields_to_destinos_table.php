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
            $table->string('titulo_seo', 60)->nullable()->after('website');
            $table->string('descripcion_meta', 160)->nullable()->after('titulo_seo');
            $table->string('keywords', 255)->nullable()->after('descripcion_meta');
            $table->string('open_graph_image')->nullable()->after('keywords');
            $table->boolean('indexar_seo')->default(true)->after('open_graph_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->dropColumn(['titulo_seo', 'descripcion_meta', 'keywords', 'open_graph_image', 'indexar_seo']);
        });
    }
}; 