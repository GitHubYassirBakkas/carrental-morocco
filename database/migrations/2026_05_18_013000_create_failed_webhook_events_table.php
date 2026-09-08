<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('failed_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable()->index();
            $table->string('event_type')->nullable()->index();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('payment_intent_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->text('error_message');
            $table->longText('stack_trace')->nullable();
            $table->timestamp('failed_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_webhook_events');
    }
};
