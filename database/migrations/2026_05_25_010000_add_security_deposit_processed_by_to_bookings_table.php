<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'security_deposit_processed_by')) {
                $column = $table->foreignId('security_deposit_processed_by')->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                if (Schema::hasColumn('bookings', 'security_deposit_refund_error_message')) {
                    $column->after('security_deposit_refund_error_message');
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'security_deposit_processed_by')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('security_deposit_processed_by');
            });
        }
    }
};
