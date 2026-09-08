<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('
            ALTER TABLE notifications
            MODIFY COLUMN type VARCHAR(100) NOT NULL;
        ');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE notifications
            MODIFY COLUMN type ENUM(
                'booking_confirmed',
                'booking_cancelled',
                'booking_reminder',
                'payment_received',
                'payment_failed',
                'admin_message',
                'review_reminder',
                'promotion'
            ) NOT NULL;
        ");
    }
};
