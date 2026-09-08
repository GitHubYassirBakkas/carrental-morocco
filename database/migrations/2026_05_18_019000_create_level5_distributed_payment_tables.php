<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_stream', function (Blueprint $table) {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('event_name')->index();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('version')->nullable();
            $table->uuid('trace_id')->nullable()->index();
            $table->uuid('correlation_id')->nullable()->index();
            $table->string('source_event_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['booking_id', 'version']);
            $table->index(['booking_id', 'id']);
        });

        Schema::create('event_stream_cursors', function (Blueprint $table) {
            $table->id();
            $table->string('worker_name')->unique();
            $table->unsignedBigInteger('last_event_stream_id')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('worker_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('worker_name')->unique();
            $table->string('queue')->nullable()->index();
            $table->string('current_job')->nullable();
            $table->unsignedBigInteger('current_event_id')->nullable();
            $table->timestamp('heartbeat_at')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();
        });

        Schema::create('global_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->index();
            $table->string('idempotency_key');
            $table->string('status', 32)->default('processing')->index();
            $table->uuid('owner_token')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('global_idempotency_records');
        Schema::dropIfExists('worker_heartbeats');
        Schema::dropIfExists('event_stream_cursors');
        Schema::dropIfExists('event_stream');
    }
};
