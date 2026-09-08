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
    Schema::create('booking_inspections', function (Blueprint $table) {
        $table->id();
        $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
        $table->enum('type', ['checkin', 'checkout']);
        $table->integer('mileage')->nullable();
        $table->integer('fuel_level')->nullable(); // % 0–100
        $table->boolean('has_damage')->default(false);
        $table->text('damage_notes')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users');
        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_inspections');
        
    }
};
