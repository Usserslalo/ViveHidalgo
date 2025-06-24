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
        Schema::table('users', function (Blueprint $table) {
            // Campos específicos para proveedores
            $table->string('company_name')->nullable()->after('name');
            $table->text('company_description')->nullable()->after('company_name');
            $table->string('website')->nullable()->after('company_description');
            $table->string('logo_path')->nullable()->after('website');
            $table->string('business_license_path')->nullable()->after('logo_path');
            $table->string('tax_id')->nullable()->after('business_license_path');
            $table->string('contact_person')->nullable()->after('tax_id');
            $table->string('contact_phone')->nullable()->after('contact_person');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->enum('business_type', ['hotel', 'restaurant', 'tour_operator', 'transport', 'activity', 'other'])->nullable()->after('contact_email');
            $table->json('business_hours')->nullable()->after('business_type');
            $table->boolean('is_verified_provider')->default(false)->after('business_hours');
            $table->timestamp('verified_at')->nullable()->after('is_verified_provider');
            $table->text('verification_notes')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_description',
                'website',
                'logo_path',
                'business_license_path',
                'tax_id',
                'contact_person',
                'contact_phone',
                'contact_email',
                'business_type',
                'business_hours',
                'is_verified_provider',
                'verified_at',
                'verification_notes',
            ]);
        });
    }
}; 