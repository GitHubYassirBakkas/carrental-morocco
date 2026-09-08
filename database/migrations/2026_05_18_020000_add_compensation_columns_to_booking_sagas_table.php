<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_sagas', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_sagas', 'compensation_status')) {
                $table->string('compensation_status', 32)->nullable()->after('status')->index();
            }

            if (!Schema::hasColumn('booking_sagas', 'compensation_payload')) {
                $table->json('compensation_payload')->nullable()->after('payload');
            }

            if (!Schema::hasColumn('booking_sagas', 'retry_count')) {
                $table->unsignedInteger('retry_count')->default(0)->after('compensation_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_sagas', function (Blueprint $table) {
            foreach (['compensation_status', 'compensation_payload', 'retry_count'] as $column) {
                if (Schema::hasColumn('booking_sagas', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
