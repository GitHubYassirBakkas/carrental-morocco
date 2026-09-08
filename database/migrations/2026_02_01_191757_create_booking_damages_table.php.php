<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_damages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('stage', ['checkin','checkout']);

            $table->string('part'); // door, bumper, mirror...
            $table->string('type'); // scratch, dent, broken
            $table->text('description')->nullable();

            $table->decimal('estimated_cost', 10, 2)->default(0);
            $table->boolean('is_chargeable')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_damages');
    }
};
