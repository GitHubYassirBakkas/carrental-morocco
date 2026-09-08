<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();

            $table->unsignedTinyInteger('rating'); // 1-5 stars
            $table->text('comment')->nullable();

            // Additional review aspects
            $table->unsignedTinyInteger('cleanliness_rating')->nullable();
            $table->unsignedTinyInteger('comfort_rating')->nullable();
            $table->unsignedTinyInteger('service_rating')->nullable();
            $table->unsignedTinyInteger('value_rating')->nullable();

            $table->boolean('is_approved')->default(false);
            $table->text('admin_comment')->nullable();

            $table->timestamps();

            // Ensure one review per booking
            $table->unique(['user_id', 'car_id', 'booking_id']);

            // Indexes for better query performance
            $table->index(['car_id', 'is_approved']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
