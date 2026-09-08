<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->after('user_id');
            }
            if (!Schema::hasColumn('invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('subtotal');
            }
        });
    }

    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount_amount']);
        });
    }
};