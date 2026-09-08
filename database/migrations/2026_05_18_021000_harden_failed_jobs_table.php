<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        Schema::table('failed_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('failed_jobs', 'uuid')) {
                $table->string('uuid')->nullable()->after('id');
            }

            if (! Schema::hasColumn('failed_jobs', 'connection')) {
                $table->text('connection')->nullable()->after('uuid');
            }

            if (! Schema::hasColumn('failed_jobs', 'queue')) {
                $table->text('queue')->nullable()->after('connection');
            }

            if (! Schema::hasColumn('failed_jobs', 'payload')) {
                $table->longText('payload')->nullable()->after('queue');
            }

            if (! Schema::hasColumn('failed_jobs', 'exception')) {
                $table->longText('exception')->nullable()->after('payload');
            }

            if (! Schema::hasColumn('failed_jobs', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->useCurrent()->after('exception');
            }

            if (! Schema::hasColumn('failed_jobs', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('failed_at');
            }
        });

        DB::table('failed_jobs')
            ->whereNull('uuid')
            ->orderBy('id')
            ->lazyById()
            ->each(function ($failedJob) {
                DB::table('failed_jobs')
                    ->where('id', $failedJob->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        DB::table('failed_jobs')
            ->whereNull('created_at')
            ->update(['created_at' => DB::raw('COALESCE(failed_at, CURRENT_TIMESTAMP)')]);
    }

    public function down(): void
    {
        // Intentionally no-op: this migration repairs framework-owned queue tables.
    }
};
