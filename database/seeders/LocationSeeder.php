<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationSeeder extends Seeder
{
    public function run()
    {
        $locations = [
            // Meknès - Active location
            [
                'name' => 'Meknès Branch',
                'address' => 'Avenue Mohammed V, Meknès',
                'city' => 'Meknès',
                'country' => 'Morocco',
                'postal_code' => '50000',
                'latitude' => 33.8935,
                'longitude' => -5.5473,
                'phone' => '+212 5 35 52 00 00',
                'email' => 'meknes@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => true,
            ],
            
            // Coming Soon Locations
            [
                'name' => 'Casablanca Branch',
                'address' => 'Boulevard de la Corniche, Casablanca',
                'city' => 'Casablanca',
                'country' => 'Morocco',
                'postal_code' => '20000',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'phone' => '+212 5 22 22 00 00',
                'email' => 'casablanca@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Rabat Branch',
                'address' => 'Avenue Mohammed V, Rabat',
                'city' => 'Rabat',
                'country' => 'Morocco',
                'postal_code' => '10000',
                'latitude' => 34.0209,
                'longitude' => -6.8416,
                'phone' => '+212 5 37 20 00 00',
                'email' => 'rabat@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Marrakech Branch',
                'address' => 'Gueliz, Marrakech',
                'city' => 'Marrakech',
                'country' => 'Morocco',
                'postal_code' => '40000',
                'latitude' => 31.6295,
                'longitude' => -7.9811,
                'phone' => '+212 5 24 44 00 00',
                'email' => 'marrakech@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Fès Branch',
                'address' => 'Nouveau Quartier, Fès',
                'city' => 'Fès',
                'country' => 'Morocco',
                'postal_code' => '30000',
                'latitude' => 34.0181,
                'longitude' => -5.0078,
                'phone' => '+212 5 35 62 00 00',
                'email' => 'fes@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Tanger Branch',
                'address' => 'Boulevard Pasteur, Tanger',
                'city' => 'Tanger',
                'country' => 'Morocco',
                'postal_code' => '90000',
                'latitude' => 35.7595,
                'longitude' => -5.8340,
                'phone' => '+212 5 39 94 00 00',
                'email' => 'tanger@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Agadir Branch',
                'address' => 'Boulevard Mohammed V, Agadir',
                'city' => 'Agadir',
                'country' => 'Morocco',
                'postal_code' => '80000',
                'latitude' => 30.4278,
                'longitude' => -9.5981,
                'phone' => '+212 5 28 84 00 00',
                'email' => 'agadir@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Oujda Branch',
                'address' => 'Centre Ville, Oujda',
                'city' => 'Oujda',
                'country' => 'Morocco',
                'postal_code' => '60000',
                'latitude' => 34.6867,
                'longitude' => -1.9114,
                'phone' => '+212 5 36 68 00 00',
                'email' => 'oujda@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Tétouan Branch',
                'address' => 'Avenue Mohammed V, Tétouan',
                'city' => 'Tétouan',
                'country' => 'Morocco',
                'postal_code' => '93000',
                'latitude' => 35.5889,
                'longitude' => -5.3626,
                'phone' => '+212 5 39 96 00 00',
                'email' => 'tetouan@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
            
            [
                'name' => 'Essaouira Branch',
                'address' => 'Médina, Essaouira',
                'city' => 'Essaouira',
                'country' => 'Morocco',
                'postal_code' => '44000',
                'latitude' => 31.5085,
                'longitude' => -9.7595,
                'phone' => '+212 5 24 78 00 00',
                'email' => 'essaouira@carrental.ma',
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'is_active' => false,
            ],
        ];

        foreach ($locations as $location) {
            Location::create($location);
        }

        $this->command->info('✅ Successfully seeded 10 locations (1 active: Meknès, 9 coming soon)');
    }
}
