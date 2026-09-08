<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->string('driving_license_number', 80)->nullable()->change();
            $table->string('driving_license_front_path', 2048)->nullable()->change();
            $table->string('driving_license_back_path', 2048)->nullable()->change();
            $table->string('identity_front_path', 2048)->nullable()->change();
            $table->string('identity_back_path', 2048)->nullable()->change();

            $table->string('driver_verification_status', 30)
                ->default('incomplete')
                ->index()
                ->after('identity_back_path');
            $table->text('driver_verification_rejection_reason')
                ->nullable()
                ->after('driver_verification_status');
            $table->timestamp('driver_verification_submitted_at')
                ->nullable()
                ->after('driver_verification_rejection_reason');
            $table->timestamp('driver_verified_at')
                ->nullable()
                ->after('driver_verification_submitted_at');
            $table->foreignId('driver_verified_by')
                ->nullable()
                ->after('driver_verified_at')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('customer_profiles')
            ->whereNotNull('date_of_birth')
            ->whereNotNull('driving_license_number')
            ->whereNotNull('driving_license_front_path')
            ->whereNotNull('driving_license_back_path')
            ->whereNotNull('identity_front_path')
            ->whereNotNull('identity_back_path')
            ->update([
                'driver_verification_status' => 'pending',
                'driver_verification_submitted_at' => DB::raw('COALESCE(updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('driver_verified_by');
            $table->dropColumn([
                'driver_verified_at',
                'driver_verification_submitted_at',
                'driver_verification_rejection_reason',
                'driver_verification_status',
            ]);

            $table->date('date_of_birth')->nullable(false)->change();
            $table->string('driving_license_number', 80)->nullable(false)->change();
            $table->string('driving_license_front_path', 2048)->nullable(false)->change();
            $table->string('driving_license_back_path', 2048)->nullable(false)->change();
            $table->string('identity_front_path', 2048)->nullable(false)->change();
            $table->string('identity_back_path', 2048)->nullable(false)->change();
        });
    }
};
