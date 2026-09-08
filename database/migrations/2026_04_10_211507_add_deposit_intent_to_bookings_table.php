<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('deposit_payment_intent_id')->nullable(); // Stripe PI for deposit
            $table->string('deposit_status')->default('pending');    // pending/held/released/charged
            $table->decimal('deposit_charged_amount', 10, 2)->default(0); // ila qta3na shi 7aja
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['deposit_payment_intent_id', 'deposit_status', 'deposit_charged_amount']);
        });
    }
};