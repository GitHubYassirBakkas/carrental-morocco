<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if ($this->usesSQLite()) {
            return;
        }

        foreach ($this->protectedForeignKeys() as $foreignKey) {
            $this->rebuildForeignKey(
                $foreignKey['table'],
                $foreignKey['column'],
                $foreignKey['references'],
                $foreignKey['on_delete'],
            );
        }
    }

    public function down(): void
    {
        // Intentionally do not restore destructive cascades for historical data.
    }

    private function rebuildForeignKey(string $tableName, string $columnName, string $references, string $onDelete): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, $columnName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columnName) {
            $table->dropForeign([$columnName]);
        });

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $references, $onDelete) {
            $foreign = $table->foreign($columnName)->references('id')->on($references);

            match ($onDelete) {
                'set null' => $foreign->nullOnDelete(),
                default => $foreign->restrictOnDelete(),
            };
        });
    }

    private function protectedForeignKeys(): array
    {
        return [
            ['table' => 'cars', 'column' => 'location_id', 'references' => 'locations', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'car_id', 'references' => 'cars', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'insurance_id', 'references' => 'insurances', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'coupon_id', 'references' => 'coupons', 'on_delete' => 'restrict'],
            ['table' => 'reviews', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'reviews', 'column' => 'car_id', 'references' => 'cars', 'on_delete' => 'restrict'],
            ['table' => 'reviews', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'booking_inspections', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'booking_photos', 'column' => 'booking_inspection_id', 'references' => 'booking_inspections', 'on_delete' => 'restrict'],
            ['table' => 'booking_damages', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'invoices', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'invoices', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'payments', 'column' => 'invoice_id', 'references' => 'invoices', 'on_delete' => 'restrict'],
            ['table' => 'payments', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'coupon_usages', 'column' => 'coupon_id', 'references' => 'coupons', 'on_delete' => 'restrict'],
            ['table' => 'coupon_usages', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'coupon_usages', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'coupons', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'set null'],
            ['table' => 'tickets', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'ticket_messages', 'column' => 'ticket_id', 'references' => 'tickets', 'on_delete' => 'restrict'],
            ['table' => 'ticket_messages', 'column' => 'user_id', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'payment_event_audits', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'failed_webhook_events', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'email_logs', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'booking_sagas', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'booking_state_transitions', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'event_stream', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'saga_execution_logs', 'column' => 'booking_id', 'references' => 'bookings', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'security_deposit_captured_by', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'security_deposit_refunded_by', 'references' => 'users', 'on_delete' => 'restrict'],
            ['table' => 'bookings', 'column' => 'security_deposit_processed_by', 'references' => 'users', 'on_delete' => 'restrict'],
        ];
    }

    private function usesSQLite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }
};
