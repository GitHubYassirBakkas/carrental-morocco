<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'advance_payment_status')) {
                $table->string('advance_payment_status')->default('pending')->after('id');
            }

            if (! Schema::hasColumn('bookings', 'advance_payment_due_at')) {
                $table->timestamp('advance_payment_due_at')->nullable()->after('advance_payment_status');
            }

            if (! Schema::hasColumn('bookings', 'advance_payment_paid_at')) {
                $table->timestamp('advance_payment_paid_at')->nullable()->after('advance_payment_due_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('bookings', 'advance_payment_paid_at') ? 'advance_payment_paid_at' : null,
            Schema::hasColumn('bookings', 'advance_payment_due_at') ? 'advance_payment_due_at' : null,
        ]));

        if ($columns !== []) {
            Schema::table('bookings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
