<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            // Morocco insurance specifics
            $table->string('type')->default('basic')->after('name'); // basic, standard, premium
            $table->decimal('max_coverage', 10, 2)->default(0)->after('daily_rate');
            $table->decimal('deductible', 10, 2)->default(0)->after('max_coverage');
            $table->json('features')->nullable()->after('description');
            $table->integer('sort_order')->default(0)->after('is_active');

            // Rename excess_fee to deductible (optional - keep both if you want)
            // $table->renameColumn('excess_fee', 'deductible'); // Uncomment if you want to rename
        });
    }

    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'max_coverage',
                'deductible',
                'features',
                'sort_order',
            ]);
        });
    }
};
