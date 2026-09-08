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
        $table->unsignedInteger('late_minutes')->default(0);
        $table->decimal('late_fee', 8, 2)->default(0);
    });
}

public function down(): void
{
    Schema::table('bookings', function (Blueprint $table) {
        $table->dropColumn(['late_minutes', 'late_fee']);
    });
}

};
