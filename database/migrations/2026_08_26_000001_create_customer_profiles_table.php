<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('date_of_birth');
            $table->string('driving_license_number', 80);
            $table->string('driving_license_country', 100)->nullable();
            $table->date('driving_license_issue_date')->nullable();
            $table->date('driving_license_expiry_date')->nullable();
            $table->string('driving_license_front_path', 2048);
            $table->string('driving_license_back_path', 2048);
            $table->string('identity_front_path', 2048);
            $table->string('identity_back_path', 2048);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
