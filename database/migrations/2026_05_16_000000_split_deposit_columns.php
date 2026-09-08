<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->splitCarSecurityDeposit();
        $this->splitBookingAdvancePayment();
        $this->splitBookingSecurityDeposit();
        $this->renamePaymentType();
    }

    public function down(): void
    {
        // Intentionally irreversible: the split removes an ambiguous domain name.
    }

    private function splitCarSecurityDeposit(): void
    {
        if (! Schema::hasTable('cars')) {
            return;
        }

        if (
            ! Schema::hasColumn('cars', 'deposit_amount') &&
            Schema::hasColumn('cars', 'security_deposit_amount')
        ) {
            return;
        }

        if (Schema::hasColumn('cars', 'deposit_amount') && ! Schema::hasColumn('cars', 'security_deposit_amount')) {
            Schema::table('cars', function (Blueprint $table) {
                $table->decimal('security_deposit_amount', 10, 2)->nullable()->after('cancellation_policy');
            });

            DB::table('cars')->update([
                'security_deposit_amount' => DB::raw('deposit_amount'),
            ]);
        }

        if (Schema::hasColumn('cars', 'deposit_amount')) {
            Schema::table('cars', function (Blueprint $table) {
                $table->dropColumn('deposit_amount');
            });
        }
    }

    private function splitBookingAdvancePayment(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        $hasOldAdvancePaymentColumns = Schema::hasColumn('bookings', 'deposit_amount')
            || Schema::hasColumn('bookings', 'deposit_paid')
            || Schema::hasColumn('bookings', 'deposit_paid_at')
            || Schema::hasColumn('bookings', 'deposit_due_at');

        if (
            ! $hasOldAdvancePaymentColumns &&
            Schema::hasColumn('bookings', 'advance_payment_amount') &&
            Schema::hasColumn('bookings', 'advance_payment_status') &&
            Schema::hasColumn('bookings', 'advance_payment_paid_at') &&
            Schema::hasColumn('bookings', 'advance_payment_due_at')
        ) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'advance_payment_amount')) {
                $table->decimal('advance_payment_amount', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('bookings', 'advance_payment_status')) {
                $table->string('advance_payment_status')->default('pending');
            }

            if (! Schema::hasColumn('bookings', 'advance_payment_paid_at')) {
                $table->timestamp('advance_payment_paid_at')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'advance_payment_due_at')) {
                $table->timestamp('advance_payment_due_at')->nullable();
            }
        });

        if (Schema::hasColumn('bookings', 'deposit_amount')) {
            DB::table('bookings')->update([
                'advance_payment_amount' => DB::raw('deposit_amount'),
            ]);
        }

        if (Schema::hasColumn('bookings', 'deposit_paid')) {
            DB::table('bookings')
                ->where('deposit_paid', true)
                ->update(['advance_payment_status' => 'paid']);

            DB::table('bookings')
                ->where('deposit_paid', false)
                ->update(['advance_payment_status' => 'pending']);
        }

        if (Schema::hasColumn('bookings', 'deposit_paid_at')) {
            DB::table('bookings')->update([
                'advance_payment_paid_at' => DB::raw('deposit_paid_at'),
            ]);
        }

        if (Schema::hasColumn('bookings', 'deposit_due_at')) {
            DB::table('bookings')->update([
                'advance_payment_due_at' => DB::raw('deposit_due_at'),
            ]);
        }

        $oldColumns = array_values(array_filter([
            Schema::hasColumn('bookings', 'deposit_amount') ? 'deposit_amount' : null,
            Schema::hasColumn('bookings', 'deposit_paid') ? 'deposit_paid' : null,
            Schema::hasColumn('bookings', 'deposit_paid_at') ? 'deposit_paid_at' : null,
            Schema::hasColumn('bookings', 'deposit_due_at') ? 'deposit_due_at' : null,
        ]));

        if ($oldColumns) {
            Schema::table('bookings', function (Blueprint $table) use ($oldColumns) {
                $table->dropColumn($oldColumns);
            });
        }
    }

    private function splitBookingSecurityDeposit(): void
    {
        if (! Schema::hasTable('bookings')) {
            return;
        }

        $hasOldSecurityDepositColumns = Schema::hasColumn('bookings', 'deposit_payment_intent_id')
            || Schema::hasColumn('bookings', 'deposit_status')
            || Schema::hasColumn('bookings', 'deposit_charged_amount');

        if (
            ! $hasOldSecurityDepositColumns &&
            Schema::hasColumn('bookings', 'security_deposit_amount') &&
            Schema::hasColumn('bookings', 'security_deposit_intent_id') &&
            Schema::hasColumn('bookings', 'security_deposit_status') &&
            Schema::hasColumn('bookings', 'security_deposit_released_at') &&
            Schema::hasColumn('bookings', 'security_deposit_charged_amount')
        ) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'security_deposit_amount')) {
                $table->decimal('security_deposit_amount', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('bookings', 'security_deposit_intent_id')) {
                $table->string('security_deposit_intent_id')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'security_deposit_status')) {
                $table->string('security_deposit_status')->default('pending');
            }

            if (! Schema::hasColumn('bookings', 'security_deposit_released_at')) {
                $table->timestamp('security_deposit_released_at')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'security_deposit_charged_amount')) {
                $table->decimal('security_deposit_charged_amount', 10, 2)->default(0);
            }
        });

        if (Schema::hasColumn('bookings', 'deposit_payment_intent_id')) {
            DB::table('bookings')->update([
                'security_deposit_intent_id' => DB::raw('deposit_payment_intent_id'),
            ]);
        }

        if (Schema::hasColumn('bookings', 'deposit_status')) {
            DB::table('bookings')->update([
                'security_deposit_status' => DB::raw('deposit_status'),
            ]);
        }

        if (Schema::hasColumn('bookings', 'deposit_charged_amount')) {
            DB::table('bookings')->update([
                'security_deposit_charged_amount' => DB::raw('deposit_charged_amount'),
            ]);
        }

        $oldColumns = array_values(array_filter([
            Schema::hasColumn('bookings', 'deposit_payment_intent_id') ? 'deposit_payment_intent_id' : null,
            Schema::hasColumn('bookings', 'deposit_status') ? 'deposit_status' : null,
            Schema::hasColumn('bookings', 'deposit_charged_amount') ? 'deposit_charged_amount' : null,
        ]));

        if ($oldColumns) {
            Schema::table('bookings', function (Blueprint $table) use ($oldColumns) {
                $table->dropColumn($oldColumns);
            });
        }
    }

    private function renamePaymentType(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'type')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `payments`
                MODIFY `type`
                ENUM('payment', 'refund', 'deposit_charge', 'security_deposit_charge')
                DEFAULT 'payment'
            ");
        }

        DB::table('payments')
            ->where('type', 'deposit_charge')
            ->update(['type' => 'security_deposit_charge']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `payments`
                MODIFY `type`
                ENUM('payment', 'refund', 'security_deposit_charge')
                DEFAULT 'payment'
            ");
        }
    }
};
