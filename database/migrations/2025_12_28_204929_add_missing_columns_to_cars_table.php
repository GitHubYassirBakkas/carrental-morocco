<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // ✅ 1. Galerie d'images (صور إضافية)
            $table->json('gallery')->nullable()->after('image');
            
            // ✅ 2. Conditions de location (شروط الكراء)
            $table->integer('minimum_age')->default(21)->after('is_available');
            $table->text('fuel_policy')->nullable()->after('minimum_age');
            $table->text('cancellation_policy')->nullable()->after('fuel_policy');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('cancellation_policy');
            $table->json('required_documents')->nullable()->after('deposit_amount');
        });
    }

    /**
     * ✅ Rollback - حذف الأعمدة
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'gallery',
                'minimum_age',
                'fuel_policy',
                'cancellation_policy',
                'deposit_amount',
                'required_documents'
            ]);
        });
    }
};