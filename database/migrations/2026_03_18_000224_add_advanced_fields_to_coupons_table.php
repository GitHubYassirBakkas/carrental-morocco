<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('coupons', function (Blueprint $table) {
            // User-specific coupon (NULL = everyone can use)
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();

            // Coupon category
            $table->enum('category', [
                'welcome',      // First-time customer
                'loyalty',      // Repeat customer rewards
                'seasonal',     // Holiday/seasonal
                'referral',     // Referral program
                'retention',    // Win-back inactive customers
                'corporate',    // Business accounts
                'apology',       // Service recovery
            ])->default('seasonal')->after('code');

            // Auto-apply conditions
            $table->integer('min_bookings')->nullable()->after('min_booking_amount');
            $table->decimal('min_total_spent', 10, 2)->nullable()->after('min_bookings');

            // Usage restrictions
            $table->integer('max_uses_per_user')->default(1)->after('max_uses');

            // Car type restrictions (optional)
            $table->json('allowed_car_types')->nullable()->after('max_uses_per_user');

            // Indexes
            $table->index('user_id');
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category']);
            $table->dropColumn([
                'user_id',
                'category',
                'min_bookings',
                'min_total_spent',
                'max_uses_per_user',
                'allowed_car_types',
            ]);
        });
    }
};
