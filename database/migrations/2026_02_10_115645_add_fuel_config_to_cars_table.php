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
        Schema::table('cars', function (Blueprint $table) {
            $table->decimal('fuel_tank_capacity', 5, 2)->default(50); // liters
            $table->decimal('fuel_price_per_liter', 6, 2)->default(13); // MAD
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'fuel_tank_capacity',
                'fuel_price_per_liter',
            ]);
        });

    }
};
