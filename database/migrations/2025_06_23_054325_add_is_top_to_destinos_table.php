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
            $table->boolean('is_top')->default(false)->after('is_featured');
            $table->index('is_top'); // Índice para optimizar consultas de destinos TOP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->dropIndex(['is_top']);
            $table->dropColumn('is_top');
        });
    }
};
