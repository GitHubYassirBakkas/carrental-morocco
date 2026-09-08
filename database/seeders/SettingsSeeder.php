<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            /*
            |--------------------------------------------------------------------------
            | GENERAL SETTINGS
            |--------------------------------------------------------------------------
            */
            [
                'key' => 'site_name',
                'value' => 'Car Rental Morocco',
                'type' => 'text',  // ← Changed from 'string'
                'group' => 'general',
                'label' => 'Website Name',
                'description' => 'Main website name displayed in header and title',
                'autoload' => true,
                'is_public' => true,
            ],
            [
                'key' => 'site_phone',
                'value' => '+212 5 35 52 00 00',
                'type' => 'text',  // ← Changed
                'group' => 'general',
                'label' => 'Website Phone',
                'description' => 'Public contact phone number',
                'autoload' => true,
                'is_public' => true,
            ],
            [
                'key' => 'site_email',
                'value' => 'contact@carrentalmorocco.com',
                'type' => 'text',  // ← Changed
                'group' => 'general',
                'label' => 'Website Email',
                'description' => 'Public contact email address',
                'autoload' => true,
                'is_public' => true,
            ],
            [
                'key' => 'site_address',
                'value' => 'Avenue Mohammed V, Meknès',
                'type' => 'textarea',  // ← This is OK
                'group' => 'general',
                'label' => 'Website Address',
                'description' => 'Company address displayed on website',
                'autoload' => true,
                'is_public' => true,
            ],

            /*
            |--------------------------------------------------------------------------
            | PAYMENT SETTINGS
            |--------------------------------------------------------------------------
            */
            [
                'key' => 'advance_payment_percentage',
                'value' => '30',
                'type' => 'number',  // ← This is OK
                'group' => 'payment',
                'label' => 'Advance Payment Percentage',
                'description' => 'Minimum advance payment required to confirm booking (%)',
                'autoload' => true,
            ],
            [
                'key' => 'tax_percentage',
                'value' => '0',
                'type' => 'number',  // ← Changed from 'float'
                'group' => 'payment',
                'label' => 'Tax Percentage (VAT)',
                'description' => 'Tax percentage applied to bookings (0 = no tax)',
                'autoload' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'MAD',
                'type' => 'text',  // ← Changed
                'group' => 'payment',
                'label' => 'Currency',
                'description' => 'Default currency used in system',
                'autoload' => true,
            ],
            [
                'key' => 'late_fee_per_hour',
                'value' => '50',
                'type' => 'number',  // ← Changed
                'group' => 'payment',
                'label' => 'Late Fee Per Hour',
                'description' => 'Late return fee charged per hour (MAD)',
            ],
            [
                'key' => 'late_grace_minutes',
                'value' => '60',
                'type' => 'number',
                'group' => 'payment',
                'label' => 'Late Grace Period (Minutes)',
                'description' => 'Free grace period before late fees start',
            ],
            [
                'key' => 'dropoff_fee',
                'value' => '200',
                'type' => 'number',
                'group' => 'payment',
                'label' => 'Different Dropoff Location Fee',
                'description' => 'Fee charged when pickup and dropoff locations are different (MAD)',
            ],
            [
                'key' => 'fuel_price_per_liter',
                'value' => '15',
                'type' => 'number',  // ← Changed
                'group' => 'payment',
                'label' => 'Fuel Price Per Liter',
                'description' => 'Fuel charge per liter if car not refueled',
            ],
            [
                'key' => 'fuel_price_per_percent',
                'value' => '5',
                'type' => 'number',
                'group' => 'payment',
                'label' => 'Fuel Price Per Tank Percent',
                'description' => 'Fuel charge per missing tank percentage point (MAD)',
            ],

            /*
            |--------------------------------------------------------------------------
            | BOOKING SETTINGS
            |--------------------------------------------------------------------------
            */
            [
                'key' => 'booking_min_days',
                'value' => '1',
                'type' => 'number',  // ← Changed from 'integer'
                'group' => 'booking',
                'label' => 'Minimum Rental Days',
                'description' => 'Minimum allowed rental duration',
                'autoload' => true,
            ],
            [
                'key' => 'booking_max_days',
                'value' => '30',
                'type' => 'number',  // ← Changed
                'group' => 'booking',
                'label' => 'Maximum Rental Days',
                'description' => 'Maximum allowed rental duration',
                'autoload' => true,
            ],
            [
                'key' => 'advance_payment_deadline_hours',
                'value' => '24',
                'type' => 'number',  // ← Changed
                'group' => 'booking',
                'label' => 'Advance Payment Deadline (Hours)',
                'description' => 'Hours allowed to pay the advance payment before auto-cancel',
                'autoload' => true,
            ],
            [
                'key' => 'min_driver_age',
                'value' => '21',
                'type' => 'number',  // ← Changed
                'group' => 'booking',
                'label' => 'Minimum Driver Age',
                'description' => 'Minimum age required to rent a car',
                'autoload' => true,
            ],
            [
                'key' => 'max_advance_booking_days',
                'value' => '90',
                'type' => 'number',  // ← Changed
                'group' => 'booking',
                'label' => 'Max Advance Booking (Days)',
                'description' => 'Maximum days in advance a booking can be made',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Successfully seeded application settings:');
        $this->command->info('- General settings: Site name, contact info');
        $this->command->info('- Payment settings: 20% tax rate, MAD currency');
        $this->command->info('- Booking settings: 1-30 days rental period, min driver age 21');
    }
}
