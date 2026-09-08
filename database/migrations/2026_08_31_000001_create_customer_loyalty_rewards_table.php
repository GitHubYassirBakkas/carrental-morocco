<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('milestone');
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('qualifying_booking_id')->constrained('bookings')->restrictOnDelete();
            $table->timestamp('awarded_at');
            $table->timestamps();

            $table->unique(['user_id', 'milestone']);
            $table->unique('coupon_id');
            $table->unique('qualifying_booking_id');
            $table->index('awarded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_loyalty_rewards');
    }
};
