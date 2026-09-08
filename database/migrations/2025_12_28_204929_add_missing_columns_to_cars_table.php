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
            if (!Schema::hasColumn('cars', 'gallery')) {
                $table->json('gallery')->nullable()->after('image');
            }
            
            // ✅ 2. Conditions de location (شروط الكراء)
            if (!Schema::hasColumn('cars', 'minimum_age')) {
                $table->integer('minimum_age')->default(21)->after('is_available');
            }

            if (!Schema::hasColumn('cars', 'fuel_policy')) {
                $table->text('fuel_policy')->nullable()->after('minimum_age');
            }

            if (!Schema::hasColumn('cars', 'cancellation_policy')) {
                $table->text('cancellation_policy')->nullable()->after('fuel_policy');
            }

            if (!Schema::hasColumn('cars', 'security_deposit_amount')) {
                $table->decimal('security_deposit_amount', 10, 2)->nullable()->after('cancellation_policy');
            }

            if (!Schema::hasColumn('cars', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('security_deposit_amount');
            }
        });
    }

    /**
     * ✅ Rollback - حذف الأعمدة
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('cars', 'gallery') ? 'gallery' : null,
            Schema::hasColumn('cars', 'minimum_age') ? 'minimum_age' : null,
            Schema::hasColumn('cars', 'fuel_policy') ? 'fuel_policy' : null,
            Schema::hasColumn('cars', 'cancellation_policy') ? 'cancellation_policy' : null,
            Schema::hasColumn('cars', 'security_deposit_amount') ? 'security_deposit_amount' : null,
            Schema::hasColumn('cars', 'required_documents') ? 'required_documents' : null,
        ]));

        if ($columns !== []) {
            Schema::table('cars', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
