<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $statuses = [
        'pending',
        'partial',
        'paid',
        'refunded',
        'cancelled',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'status')) {
            return;
        }

        DB::table('invoices')
            ->whereIn('status', ['draft', 'unpaid'])
            ->update(['status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `invoices`
                MODIFY COLUMN `status`
                ENUM('pending', 'partial', 'paid', 'refunded', 'cancelled')
                DEFAULT 'pending'
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices') || ! Schema::hasColumn('invoices', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `invoices`
                MODIFY COLUMN `status`
                ENUM('pending', 'partial', 'paid', 'refunded', 'cancelled')
                DEFAULT 'pending'
            ");
        }
    }
};
