<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->decimal('discount_amount', 10, 2); // Actual discount applied
            $table->decimal('original_amount', 10, 2); // Before discount
            $table->decimal('final_amount', 10, 2);    // After discount
            $table->timestamps();
            
            $table->index(['coupon_id', 'user_id']);
            $table->index('booking_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');
    }
};
