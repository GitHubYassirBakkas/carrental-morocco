<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_webhook_locks')) {
            Schema::table('payment_webhook_locks', function (Blueprint $table) {
                if (! Schema::hasColumn('payment_webhook_locks', 'owner_token')) {
                    $table->string('owner_token', 64)->nullable()->after('expires_at');
                }
            });

            if (! $this->indexExists('payment_webhook_locks', 'payment_webhook_locks_expires_at_index')) {
                Schema::table('payment_webhook_locks', function (Blueprint $table) {
                    $table->index('expires_at');
                });
            }
        }

        if (Schema::hasTable('payment_event_audits')) {
            Schema::table('payment_event_audits', function (Blueprint $table) {
                if (! Schema::hasColumn('payment_event_audits', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable()->after('payload');
                }

                if (! Schema::hasColumn('payment_event_audits', 'outcome')) {
                    $table->string('outcome')->nullable()->after('processed_at');
                }

                if (! Schema::hasColumn('payment_event_audits', 'error_message')) {
                    $table->text('error_message')->nullable()->after('outcome');
                }
            });

            foreach ([
                'payment_event_audits_booking_id_index' => 'booking_id',
                'payment_event_audits_processed_at_index' => 'processed_at',
                'payment_event_audits_outcome_index' => 'outcome',
            ] as $index => $column) {
                if (! $this->indexExists('payment_event_audits', $index) && Schema::hasColumn('payment_event_audits', $column)) {
                    Schema::table('payment_event_audits', function (Blueprint $table) use ($column) {
                        $table->index($column);
                    });
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_event_audits')) {
            Schema::table('payment_event_audits', function (Blueprint $table) {
                foreach (['error_message', 'outcome', 'processed_at'] as $column) {
                    if (Schema::hasColumn('payment_event_audits', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('payment_webhook_locks') && Schema::hasColumn('payment_webhook_locks', 'owner_token')) {
            Schema::table('payment_webhook_locks', function (Blueprint $table) {
                $table->dropColumn('owner_token');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$table}')"))
                ->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        return false;
    }
};
