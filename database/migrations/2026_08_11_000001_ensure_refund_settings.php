<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure refund settings exist
        $refundSettings = [
            [
                'key' => 'refund_free_cancellation_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'refund',
                'label' => 'Free Cancellation',
                'description' => 'Allow customers to receive a full refund when cancellation is made within the configured free-cancellation period',
                'autoload' => true,
                'is_public' => false,
            ],
            [
                'key' => 'refund_cancellation_window_hours',
                'value' => '48',
                'type' => 'number',
                'group' => 'refund',
                'label' => 'Cancellation Window (Hours)',
                'description' => 'Number of hours before pickup during which free cancellation is allowed',
                'autoload' => true,
                'is_public' => false,
            ],
            [
                'key' => 'refund_partial_percentage',
                'value' => '50',
                'type' => 'number',
                'group' => 'refund',
                'label' => 'Partial Refund Percentage',
                'description' => 'Percentage of the eligible rental amount refunded when the partial-refund policy applies (0-100)',
                'autoload' => true,
                'is_public' => false,
            ],
            [
                'key' => 'refund_no_refund_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'refund',
                'label' => 'No Refund',
                'description' => 'Enable the no-refund outcome when cancellation falls outside the configured refund conditions',
                'autoload' => true,
                'is_public' => false,
            ],
            [
                'key' => 'refund_default_method',
                'value' => 'cash',
                'type' => 'text',
                'group' => 'refund',
                'label' => 'Default Refund Method',
                'description' => 'Preferred refund method for cancellations (cash or card)',
                'autoload' => true,
                'is_public' => false,
            ],
        ];

        foreach ($refundSettings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::whereIn('key', [
            'refund_free_cancellation_enabled',
            'refund_cancellation_window_hours',
            'refund_partial_percentage',
            'refund_no_refund_enabled',
            'refund_default_method',
        ])->delete();
    }
};
