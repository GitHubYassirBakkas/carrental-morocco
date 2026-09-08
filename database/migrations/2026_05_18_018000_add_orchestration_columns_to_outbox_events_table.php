<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox_events', function (Blueprint $table) {
            if (! Schema::hasColumn('outbox_events', 'trace_id')) {
                $table->uuid('trace_id')->nullable()->after('payload')->index();
            }

            if (! Schema::hasColumn('outbox_events', 'source_event_id')) {
                $table->string('source_event_id')->nullable()->after('trace_id')->index();
            }

            if (! Schema::hasColumn('outbox_events', 'attempts')) {
                $table->unsignedInteger('attempts')->default(0)->after('dispatched');
            }

            if (! Schema::hasColumn('outbox_events', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable()->after('attempts');
            }

            if (! Schema::hasColumn('outbox_events', 'error_message')) {
                $table->text('error_message')->nullable()->after('dispatched_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outbox_events', function (Blueprint $table) {
            foreach (['trace_id', 'source_event_id', 'attempts', 'dispatched_at', 'error_message'] as $column) {
                if (Schema::hasColumn('outbox_events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
