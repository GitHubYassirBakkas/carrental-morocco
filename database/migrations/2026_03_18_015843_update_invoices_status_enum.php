<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Simply add 'pending' to existing ENUM
        DB::statement("
            ALTER TABLE `invoices` 
            MODIFY COLUMN `status` 
            ENUM('pending', 'unpaid', 'paid', 'cancelled', 'refunded') 
            DEFAULT 'pending'
        ");
    }

    public function down()
    {
        // Revert back
        DB::statement("
            ALTER TABLE `invoices` 
            MODIFY COLUMN `status` 
            ENUM('unpaid', 'paid') 
            DEFAULT 'unpaid'
        ");
    }
};