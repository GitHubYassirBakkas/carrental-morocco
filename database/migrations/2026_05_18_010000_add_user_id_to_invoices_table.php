<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices') || Schema::hasColumn('invoices', 'user_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('booking_id')
                ->constrained()
                ->restrictOnDelete();
        });

        DB::table('invoices')->update([
            'user_id' => DB::raw('(select bookings.user_id from bookings where bookings.id = invoices.booking_id)'),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices') || !Schema::hasColumn('invoices', 'user_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
