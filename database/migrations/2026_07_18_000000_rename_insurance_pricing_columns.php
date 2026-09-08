<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('insurances')) {
            Schema::table('insurances', function (Blueprint $table) {
                if (Schema::hasColumn('insurances', 'daily_rate') && !Schema::hasColumn('insurances', 'fixed_price')) {
                    $table->renameColumn('daily_rate', 'fixed_price');
                }
            });
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'daily_rate') && !Schema::hasColumn('bookings', 'rental_price_per_day')) {
                    $table->renameColumn('daily_rate', 'rental_price_per_day');
                }

                if (Schema::hasColumn('bookings', 'insurance_daily_rate') && !Schema::hasColumn('bookings', 'insurance_fixed_price')) {
                    $table->renameColumn('insurance_daily_rate', 'insurance_fixed_price');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {
                if (Schema::hasColumn('bookings', 'rental_price_per_day') && !Schema::hasColumn('bookings', 'daily_rate')) {
                    $table->renameColumn('rental_price_per_day', 'daily_rate');
                }

                if (Schema::hasColumn('bookings', 'insurance_fixed_price') && !Schema::hasColumn('bookings', 'insurance_daily_rate')) {
                    $table->renameColumn('insurance_fixed_price', 'insurance_daily_rate');
                }
            });
        }

        if (Schema::hasTable('insurances')) {
            Schema::table('insurances', function (Blueprint $table) {
                if (Schema::hasColumn('insurances', 'fixed_price') && !Schema::hasColumn('insurances', 'daily_rate')) {
                    $table->renameColumn('fixed_price', 'daily_rate');
                }
            });
        }
    }
};
