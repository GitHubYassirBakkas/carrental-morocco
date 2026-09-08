<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'security_deposit_amount')) {
                $table->decimal('security_deposit_amount', 10, 2)->default(0);
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_intent_id')) {
                $table->string('security_deposit_intent_id')->nullable();
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_status')) {
                $table->string('security_deposit_status')->default('pending');
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_released_at')) {
                $table->timestamp('security_deposit_released_at')->nullable();
            }

            if (!Schema::hasColumn('bookings', 'security_deposit_charged_amount')) {
                $table->decimal('security_deposit_charged_amount', 10, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter([
            Schema::hasColumn('bookings', 'security_deposit_amount') ? 'security_deposit_amount' : null,
            Schema::hasColumn('bookings', 'security_deposit_intent_id') ? 'security_deposit_intent_id' : null,
            Schema::hasColumn('bookings', 'security_deposit_status') ? 'security_deposit_status' : null,
            Schema::hasColumn('bookings', 'security_deposit_released_at') ? 'security_deposit_released_at' : null,
            Schema::hasColumn('bookings', 'security_deposit_charged_amount') ? 'security_deposit_charged_amount' : null,
        ]));

        if ($columns !== []) {
            Schema::table('bookings', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
};
