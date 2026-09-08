<?php

namespace Database\Seeders;

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
                'fixed_price' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Standard Insurance',
                'description' => 'Balanced coverage',
                'fixed_price' => 90,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Insurance',
                'description' => 'Full coverage, zero worries',
                'fixed_price' => 150,
                'is_active' => true,
            ],
        ]);
    }
}
