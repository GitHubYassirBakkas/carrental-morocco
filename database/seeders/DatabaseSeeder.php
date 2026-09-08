<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Starting database seeding...');
        $this->command->line('----------------------------');

        $this->call([
            LocationSeeder::class,
            UserSeeder::class,
            SettingsSeeder::class,
            CarSeeder::class,
        ]);
$this->call(InsuranceSeeder::class);

        $this->command->line('----------------------------');
        $this->command->info('Database seeding completed!');
    }
}
