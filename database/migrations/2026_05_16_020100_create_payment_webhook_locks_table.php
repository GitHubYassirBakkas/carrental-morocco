<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_locks', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->timestamp('locked_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('owner_token', 64)->nullable();
            $table->primary('booking_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_locks');
    }
};
