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
            $table->float('average_rating')->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('destinos', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'reviews_count']);
        });
    }
};
