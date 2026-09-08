<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@carrental.ma',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create test users
        $testUsers = [
            [
                'name' => 'Mohammed',
                'email' => 'mohammed@test.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fatima',
                'email' => 'fatima@test.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Youssef',
                'email' => 'youssef@test.com',
                'password' => Hash::make('user123'),
                'role' => 'user',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        User::insert($testUsers);

        $this->command->info('Successfully created users:');
        $this->command->info('Admin: admin@carrental.ma / admin123');
        $this->command->info('Test users: mohammed@test.com / user123, fatima@test.com / user123, youssef@test.com / user123');
    }
}
