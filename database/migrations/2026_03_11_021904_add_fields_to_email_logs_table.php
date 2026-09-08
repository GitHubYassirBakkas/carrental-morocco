<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            // Add missing columns
            $table->foreignId('booking_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
            $table->string('type')->nullable()->after('subject'); // booking, payment, damage
            $table->timestamp('sent_at')->nullable()->after('status');

            // Rename 'content' to 'body' for consistency (optional)
            // $table->renameColumn('content', 'body');
        });
    }

    public function down(): void
    {
        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropForeign(['booking_id']);
            $table->dropColumn(['booking_id', 'type', 'sent_at']);
        });
    }
};
