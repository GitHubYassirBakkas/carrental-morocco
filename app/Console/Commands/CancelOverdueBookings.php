<?php

namespace App\Console\Commands;

use App\Services\BookingService;
use Illuminate\Console\Command;

class CancelOverdueBookings extends Command
{
    protected $signature = 'bookings:cancel-overdue';

    protected $description = 'Cancel bookings with overdue advance payments';

    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        parent::__construct();
        $this->bookingService = $bookingService;
    }

    public function handle()
    {
        $cancelledCount = $this->bookingService->cancelOverdueBookings();

        $this->info("Cancelled {$cancelledCount} overdue bookings");

        return Command::SUCCESS;
    }
}
