<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('verification_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->string('type'); // e.g., 'email_verification', 'password_reset', 'account_verification'
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional data that might be needed for the token
            $table->json('payload')->nullable();
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index(['token', 'type', 'expires_at', 'used_at']);
            $table->index(['email', 'type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('verification_tokens');
    }
};
