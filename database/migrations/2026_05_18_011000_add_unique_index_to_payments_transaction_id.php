<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'transaction_id')) {
            return;
        }

        $duplicate = DB::table('payments')
            ->select('transaction_id')
            ->whereNotNull('transaction_id')
            ->groupBy('transaction_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new RuntimeException(
                'Cannot add unique payments.transaction_id index while duplicate transaction IDs exist: '.
                $duplicate->transaction_id
            );
        }

        if ($this->indexExists('payments', 'payments_transaction_id_unique')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'payments_transaction_id_index')) {
                $table->dropIndex('payments_transaction_id_index');
            }

            $table->unique('transaction_id', 'payments_transaction_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'transaction_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexExists('payments', 'payments_transaction_id_unique')) {
                $table->dropUnique('payments_transaction_id_unique');
            }

            if (! $this->indexExists('payments', 'payments_transaction_id_index')) {
                $table->index('transaction_id', 'payments_transaction_id_index');
            }
        });
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
