<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_sagas', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->unique();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('current_step')->default('started')->index();
            $table->string('status', 32)->default('running')->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->text('failure_root_cause')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'trace_id']);
        });

        Schema::create('booking_state_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('source')->nullable()->index();
            $table->string('source_event_id')->nullable()->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->boolean('accepted')->default(false)->index();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['booking_id', 'from_status', 'to_status']);
        });

        Schema::create('event_timelines', function (Blueprint $table) {
            $table->id();
            $table->uuid('trace_id')->index();
            $table->string('event_name')->index();
            $table->string('source_event_id')->nullable()->index();
            $table->string('aggregate_type')->nullable();
            $table->unsignedBigInteger('aggregate_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('saga_execution_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('saga_id')->nullable()->index();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->uuid('trace_id')->nullable()->index();
            $table->string('step')->index();
            $table->string('status', 32)->index();
            $table->json('context')->nullable();
            $table->text('failure_root_cause')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('event_replay_log', function (Blueprint $table) {
            $table->id();
            $table->string('event_source')->default('outbox_events')->index();
            $table->unsignedBigInteger('event_id')->index();
            $table->uuid('trace_id')->nullable()->index();
            $table->string('status', 32)->default('started')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_replay_log');
        Schema::dropIfExists('saga_execution_logs');
        Schema::dropIfExists('event_timelines');
        Schema::dropIfExists('booking_state_transitions');
        Schema::dropIfExists('booking_sagas');
    }
};
