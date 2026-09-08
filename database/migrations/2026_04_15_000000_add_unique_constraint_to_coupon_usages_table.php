<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->unique(
                ['coupon_id', 'booking_id', 'user_id'],
                'coupon_usages_coupon_booking_user_unique'
            );
        });
    }

    public function down()
    {
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropUnique('coupon_usages_coupon_booking_user_unique');
        });
    }
};
