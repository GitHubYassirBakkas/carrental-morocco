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
        Schema::table('bookings', function (Blueprint $table) {
        $table->unsignedTinyInteger('fuel_at_pickup_percent')->nullable();
        $table->unsignedTinyInteger('fuel_at_return_percent')->nullable();

        $table->unsignedTinyInteger('fuel_used')->default(0);
        $table->decimal('fuel_charge', 8, 2)->default(0);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
            Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'fuel_at_pickup_percent',
                'fuel_at_return_percent',
                'fuel_used',
                'fuel_charge',
            ]);
        });

    }
};
