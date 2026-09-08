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
        Schema::table('booking_damages', function (Blueprint $table) {
            $table->json('photos')
                  ->nullable()
                  ->after('description')
                  ->comment('Array of damage photo paths');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_damages', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};