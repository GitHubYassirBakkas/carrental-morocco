<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'security_deposit_capturable_amount')) {
                $table->decimal('security_deposit_capturable_amount', 10, 2)->default(0)
                    ->after('security_deposit_amount');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('bookings', 'security_deposit_capturable_amount')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropColumn('security_deposit_capturable_amount');
            });
        }
    }
};
