<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Insurance;

class InsuranceSeeder extends Seeder
{
    public function run()
    {
        Insurance::insert([
            [
                'name' => 'Basic Insurance',
                'description' => 'Basic coverage with high deductible',
                'daily_rate' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Standard Insurance',
                'description' => 'Balanced coverage',
                'daily_rate' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Insurance',
                'description' => 'Full coverage, zero worries',
                'daily_rate' => 150,
                'is_active' => true,
            ],
        ]);
    }
}
