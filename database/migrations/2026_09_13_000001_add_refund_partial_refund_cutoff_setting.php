<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Setting::updateOrCreate(
            ['key' => 'refund_partial_refund_cutoff_hours'],
            [
                'value' => '24',
                'type' => 'number',
                'group' => 'refund',
                'label' => 'Partial Refund Until Pickup (Hours)',
                'description' => 'Scheduled pickup must be at least this many hours away for the configured partial refund',
                'autoload' => true,
                'is_public' => false,
            ]
        );

        Setting::where('key', 'refund_cancellation_window_hours')->update([
            'label' => 'Full Refund Before Pickup (Hours)',
            'description' => 'Scheduled pickup must be at least this many hours away for a full rental payment refund',
        ]);
    }

    public function down(): void
    {
        Setting::where('key', 'refund_partial_refund_cutoff_hours')->delete();
    }
};
