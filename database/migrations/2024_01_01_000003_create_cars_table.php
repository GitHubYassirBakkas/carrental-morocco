<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            // Basic info
            $table->string('brand');
            $table->string('model');
            $table->year('year');
            $table->string('type'); // SUV, Luxury, Sport...

            // Specs
            $table->enum('transmission', ['Automatic', 'Manual']);
            $table->enum('fuel_type', ['Petrol', 'Diesel', 'Hybrid', 'Electric']);
            $table->integer('seats');
            $table->integer('doors');
            $table->integer('luggage');

            // Pricing
            $table->decimal('price_per_day', 10, 2);

            // Media
            $table->string('image'); // single main image

            // Details
            $table->text('description')->nullable();
            $table->json('features')->nullable();

            // Availability
            $table->boolean('is_available')->default(true);

            // Relations
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            // Indexes
            $table->index(['brand', 'type']);
            $table->index('price_per_day');
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
