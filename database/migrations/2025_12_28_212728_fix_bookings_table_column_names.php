<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ✅ تعديل اسم العمود ليتوافق مع Model
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // إعادة تسمية العمود
            $table->renameColumn('return_location_id', 'dropoff_location_id');
        });
    }

    /**
     * ✅ الرجوع للوضع السابق
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->renameColumn('dropoff_location_id', 'return_location_id');
        });
    }
};