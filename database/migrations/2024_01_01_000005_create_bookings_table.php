<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('car_id')->constrained()->restrictOnDelete();
            $table->foreignId('insurance_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('pickup_location_id')->constrained('locations')->onDelete('restrict');
            $table->foreignId('return_location_id')->constrained('locations')->onDelete('restrict');
            
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            
            $table->decimal('daily_rate', 10, 2);
            $table->decimal('insurance_daily_rate', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            
            $table->enum('status', ['pending', 'confirmed', 'active', 'completed', 'cancelled'])->default('pending');
            
            $table->text('special_requests')->nullable();
            
            $table->timestamp('pickup_actual')->nullable();
            $table->timestamp('return_actual')->nullable();
            
            $table->integer('initial_mileage')->nullable();
            $table->integer('return_mileage')->nullable();
            
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better query performance
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookings');
    }
};
