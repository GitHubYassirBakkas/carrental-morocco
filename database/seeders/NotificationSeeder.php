<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No users found. Please create a user first.');

            return;
        }

        $notifications = [
            [
                'type' => 'booking_confirmed',
                'title' => 'Booking Confirmed',
                'message' => 'Your booking has been confirmed successfully. Get ready for your trip!',
                'is_read' => false,
            ],
            [
                'type' => 'admin_message',
                'title' => 'Welcome to CarRental Morocco',
                'message' => 'Thank you for joining CarRental Morocco. We look forward to serving you!',
                'is_read' => false,
            ],
            [
                'type' => 'promotion',
                'title' => 'Special Offer',
                'message' => 'Get 20% off on your next booking with code WELCOME20',
                'is_read' => true,
            ],
            [
                'type' => 'booking_reminder',
                'title' => 'Upcoming Rental',
                'message' => 'Your rental starts in 2 days. Don\'t forget to bring your documents.',
                'is_read' => false,
            ],
            [
                'type' => 'payment_received',
                'title' => 'Payment Received',
                'message' => 'Your payment of 500 MAD has been received successfully.',
                'is_read' => true,
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'is_read' => $notification['is_read'],
                'read_at' => $notification['is_read'] ? now() : null,
            ]);
        }

        $this->command->info('Successfully seeded '.count($notifications).' notifications for user: '.$user->name);
    }
}
