<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'security_deposit_refund_error_message')) {
                $column = $table->text('security_deposit_refund_error_message')->nullable();

                if (Schema::hasColumn('bookings', 'security_deposit_penalty_reason')) {
                    $column->after('security_deposit_penalty_reason');
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'security_deposit_refund_error_message')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('security_deposit_refund_error_message');
            });
        }
    }
};
