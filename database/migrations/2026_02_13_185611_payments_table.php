<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('payments', function (Blueprint $table) {
        $table->id();

        $table->foreignId('invoice_id')
              ->constrained()
              ->restrictOnDelete();

        $table->foreignId('user_id')
              ->constrained()
              ->restrictOnDelete();

        $table->decimal('amount', 10, 2);

        $table->enum('method', ['cash', 'card', 'online', 'bank_transfer','stripe'])
              ->default('cash');

        $table->enum('type', ['payment', 'refund', 'security_deposit_charge'])->default('payment');

        $table->enum('status', ['pending', 'completed', 'failed'])
              ->default('pending');

        $table->string('transaction_id')->nullable();
        $table->index('transaction_id');

        $table->timestamp('paid_at')->nullable();
        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index('status');
        $table->index('type');
        $table->index(['invoice_id', 'status']);


    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
