<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('driver_name')->nullable()->after('notes');
            $table->string('driver_license_number', 50)->nullable()->after('driver_name');
            $table->string('additional_driver_name')->nullable()->after('driver_license_number');
            $table->string('additional_driver_license')->nullable()->after('additional_driver_name');
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('additional_driver_license');
            $table->boolean('deposit_paid')->default(false)->after('deposit_amount');
            $table->text('pickup_instructions')->nullable()->after('deposit_paid');
            $table->text('return_instructions')->nullable()->after('pickup_instructions');
            $table->foreignId('coupon_id')->nullable()->constrained()->onDelete('set null')->after('return_instructions');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('coupon_id');
        });
    }

    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn([
                'driver_name',
                'driver_license_number',
                'additional_driver_name',
                'additional_driver_license',
                'deposit_amount',
                'deposit_paid',
                'pickup_instructions',
                'return_instructions',
                'coupon_id',
                'discount_amount'
            ]);
        });
    }
};
