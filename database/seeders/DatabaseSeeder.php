<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['production', 'staging'])) {
            return; // BLOCK ALL SEEDING IN PRODUCTION
        }

        // only dev allowed
        $this->command->info('Starting database seeding...');
        $this->command->line('----------------------------');

        $this->call([
            LocationSeeder::class,
            UserSeeder::class,
            SettingsSeeder::class,
            CarSeeder::class,
            InsuranceSeeder::class,
        ]);

        $this->command->line('----------------------------');
        $this->command->info('Database seeding completed!');
    }
}
