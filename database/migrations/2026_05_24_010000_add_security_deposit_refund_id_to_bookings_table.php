<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $afterColumn = collect([
            'security_deposit_refunded_amount',
            'security_deposit_penalty_amount',
            'security_deposit_charged_amount',
            'security_deposit_status',
        ])->first(fn (string $column): bool => Schema::hasColumn('bookings', $column));

        Schema::table('bookings', function (Blueprint $table) use ($afterColumn) {
            if (!Schema::hasColumn('bookings', 'security_deposit_refund_id')) {
                $column = $table->string('security_deposit_refund_id')->nullable();

                if ($afterColumn) {
                    $column->after($afterColumn);
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'security_deposit_refund_id')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('security_deposit_refund_id');
            });
        }
    }
};
