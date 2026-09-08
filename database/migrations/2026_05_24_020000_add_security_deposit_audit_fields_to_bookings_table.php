<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $afterColumn = collect([
            'security_deposit_refund_id',
            'security_deposit_refunded_amount',
            'security_deposit_penalty_amount',
            'security_deposit_charged_amount',
            'security_deposit_status',
        ])->first(fn (string $column): bool => Schema::hasColumn('bookings', $column));

        Schema::table('bookings', function (Blueprint $table) use ($afterColumn) {
            if (!Schema::hasColumn('bookings', 'security_deposit_captured_by')) {
                $column = $table->foreignId('security_deposit_captured_by')->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                if ($afterColumn) {
                    $column->after($afterColumn);
                }
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_captured_at')) {
                $column = $table->timestamp('security_deposit_captured_at')->nullable();

                if (Schema::hasColumn('bookings', 'security_deposit_captured_by')) {
                    $column->after('security_deposit_captured_by');
                }
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_refunded_by')) {
                $column = $table->foreignId('security_deposit_refunded_by')->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                if (Schema::hasColumn('bookings', 'security_deposit_captured_at')) {
                    $column->after('security_deposit_captured_at');
                }
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_refunded_at')) {
                $column = $table->timestamp('security_deposit_refunded_at')->nullable();

                if (Schema::hasColumn('bookings', 'security_deposit_refunded_by')) {
                    $column->after('security_deposit_refunded_by');
                }
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_penalty_reason')) {
                $column = $table->text('security_deposit_penalty_reason')->nullable();

                if (Schema::hasColumn('bookings', 'security_deposit_refunded_at')) {
                    $column->after('security_deposit_refunded_at');
                }
            }
        });
    }

    public function down(): void
    {
        foreach (['security_deposit_captured_by', 'security_deposit_refunded_by'] as $column) {
            if (Schema::hasColumn('bookings', $column)) {
                Schema::table('bookings', function (Blueprint $table) use ($column) {
                    $table->dropConstrainedForeignId($column);
                });
            }
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('bookings', 'security_deposit_captured_at') ? 'security_deposit_captured_at' : null,
            Schema::hasColumn('bookings', 'security_deposit_refunded_at') ? 'security_deposit_refunded_at' : null,
            Schema::hasColumn('bookings', 'security_deposit_penalty_reason') ? 'security_deposit_penalty_reason' : null,
        ]));

        if ($columns !== []) {
            Schema::table('bookings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
