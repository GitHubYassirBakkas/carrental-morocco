<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_event_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->string('payment_intent_id')->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->string('outcome')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'event_type']);
            $table->index('booking_id');
            $table->index('payment_intent_id');
            $table->index('processed_at');
            $table->index('outcome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_event_audits');
    }
};
